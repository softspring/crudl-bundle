<?php

namespace Softspring\CrudlBundle\Controller;

use Jhg\DoctrinePagination\ORM\PaginatedRepositoryInterface;
use Softspring\CoreBundle\Controller\Traits\DispatchGetResponseTrait;
use Softspring\CoreBundle\Event\FormEvent;
use Softspring\CoreBundle\Event\GetResponseEventInterface;
use Softspring\CoreBundle\Event\GetResponseRequestEvent;
use Softspring\CoreBundle\Event\ViewEvent;
use Softspring\CrudlBundle\Event\FilterEvent;
use Softspring\CrudlBundle\Event\GetResponseEntityEvent;
use Softspring\CrudlBundle\Event\GetResponseEntityExceptionEvent;
use Softspring\CrudlBundle\Event\GetResponseFormEvent;
use Softspring\CrudlBundle\Form\EntityCreateFormInterface;
use Softspring\CrudlBundle\Form\EntityDeleteFormInterface;
use Softspring\CrudlBundle\Form\EntityListFilterFormInterface;
use Softspring\CrudlBundle\Form\EntityUpdateFormInterface;
use Softspring\CrudlBundle\Manager\CrudlEntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Entity CRUDL controller (CRUD+listing).
 */
class CrudlController extends AbstractController
{
    use DispatchGetResponseTrait;

    protected CrudlEntityManagerInterface $manager;

    protected ?EntityListFilterFormInterface $listFilterForm;

    /**
     * @var EntityCreateFormInterface|string|null
     */
    protected $createForm;

    /**
     * @var EntityUpdateFormInterface|string|null
     */
    protected $updateForm;

    /**
     * @var EntityDeleteFormInterface|string|null
     */
    protected $deleteForm;

    protected array $config;

    protected EventDispatcherInterface $eventDispatcher;

    /**
     * EntityController constructor.
     *
     * @param EntityCreateFormInterface|string|null $createForm
     * @param EntityUpdateFormInterface|string|null $updateForm
     * @param EntityDeleteFormInterface|string|null $deleteForm
     */
    public function __construct(CrudlEntityManagerInterface $manager, EventDispatcherInterface $eventDispatcher, ?EntityListFilterFormInterface $listFilterForm = null, $createForm = null, $updateForm = null, $deleteForm = null, array $config = [])
    {
        $this->manager = $manager;
        $this->eventDispatcher = $eventDispatcher;
        $this->listFilterForm = $listFilterForm;
        $this->createForm = $createForm;
        $this->updateForm = $updateForm;
        $this->deleteForm = $deleteForm;
        $this->config = $config;
        $this->config['entity_attribute'] = $this->config['entity_attribute'] ?? 'entity';
    }

    /**
     * @param string|EntityCreateFormInterface|null $createForm
     */
    public function create(Request $request, $createForm = null, array $config = []): Response
    {
        $createForm = $createForm ?: $this->createForm;
        $config = array_replace_recursive($this->config['create'] ?? [], $config);

        if (empty($config)) {
            throw new \InvalidArgumentException('Create action configuration is empty');
        }

        if (!empty($config['is_granted'])) {
            $this->denyAccessUnlessGranted($config['is_granted'], null, sprintf('Access denied, user is not %s.', $config['is_granted']));
        }

        if (!$createForm instanceof EntityCreateFormInterface && !is_string($createForm)) {
            throw new \InvalidArgumentException(sprintf('Create form must be an instance of %s or a class name', EntityCreateFormInterface::class));
        }

        $newEntity = $this->manager->createEntity();

        if ($response = $this->dispatchGetResponseFromConfig($config, 'initialize_event_name', new GetResponseEntityEvent($newEntity, $request))) {
            return $response;
        }

        if ($createForm instanceof EntityCreateFormInterface && method_exists($createForm, 'formOptions')) {
            $formOptions = $createForm->formOptions($newEntity, $request);
        } else {
            $formOptions = ['method' => 'POST'];
        }

        $formClassName = $createForm instanceof EntityCreateFormInterface ? get_class($createForm) : $createForm;

        $form = $this->createForm($formClassName, $newEntity, $formOptions)->handleRequest($request);

        $this->dispatchFromConfig($config, 'form_init_event_name', new FormEvent($form, $request));

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($response = $this->dispatchGetResponseFromConfig($config, 'form_valid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }

                $this->manager->saveEntity($newEntity);

                if ($response = $this->dispatchGetResponseFromConfig($config, 'success_event_name', new GetResponseEntityEvent($newEntity, $request))) {
                    return $response;
                }

                return $this->redirect(!empty($config['success_redirect_to']) ? $this->generateUrl($config['success_redirect_to']) : '/');
            } else {
                if ($response = $this->dispatchGetResponseFromConfig($config, 'form_invalid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }
            }
        }

        // show view
        $viewData = new \ArrayObject([
            $this->config['entity_attribute'] => $newEntity,
            'form' => $form->createView(),
        ]);

        $this->dispatchFromConfig($config, 'view_event_name', new ViewEvent($viewData));

        return $this->render($config['view'], $viewData->getArrayCopy());
    }

    public function read(Request $request, array $config = []): Response
    {
        $config = array_replace_recursive($this->config['read'] ?? [], $config);

        $entity = $request->attributes->get($this->config['entity_attribute']);

        if (empty($config)) {
            throw new \InvalidArgumentException('Read action configuration is empty');
        }

        // convert entity
        $entity = $this->manager->getRepository()->findOneBy([$config['param_converter_key'] ?? 'id' => $entity]);

        if (!empty($config['is_granted'])) {
            $this->denyAccessUnlessGranted($config['is_granted'], $entity, sprintf('Access denied, user is not %s.', $config['is_granted']));
        }

        if ($response = $this->dispatchGetResponseFromConfig($config, 'initialize_event_name', new GetResponseEntityEvent($entity, $request))) {
            return $response;
        }

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        $deleteForm = $this->getDeleteForm($entity, $request, $this->deleteForm);

        // show view
        $viewData = new \ArrayObject([
            $this->config['entity_attribute'] => $entity,
            'deleteForm' => $deleteForm ? $deleteForm->createView() : null,
        ]);

        $this->dispatchFromConfig($config, 'view_event_name', new ViewEvent($viewData));

        return $this->render($config['view'], $viewData->getArrayCopy());
    }

    /**
     * @param string|EntityCreateFormInterface|null $updateForm
     */
    public function update(Request $request, $updateForm = null, array $config = []): Response
    {
        $updateForm = $updateForm ?: $this->updateForm;
        $config = array_replace_recursive($this->config['update'] ?? [], $config);

        $entity = $request->attributes->get($this->config['entity_attribute']);

        if (empty($config)) {
            throw new \InvalidArgumentException('Update action configuration is empty');
        }

        $entity = $this->manager->getRepository()->findOneBy([$config['param_converter_key'] ?? 'id' => $entity]);

        if (!empty($config['is_granted'])) {
            $this->denyAccessUnlessGranted($config['is_granted'], $entity, sprintf('Access denied, user is not %s.', $config['is_granted']));
        }

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        if (!$updateForm instanceof EntityUpdateFormInterface && !is_string($updateForm)) {
            throw new \InvalidArgumentException(sprintf('Update form must be an instance of %s or a class name', EntityUpdateFormInterface::class));
        }

        if ($response = $this->dispatchGetResponseFromConfig($config, 'initialize_event_name', new GetResponseEntityEvent($entity, $request))) {
            return $response;
        }

        if ($updateForm instanceof EntityUpdateFormInterface && method_exists($updateForm, 'formOptions')) {
            $formOptions = $updateForm->formOptions($entity, $request);
        } else {
            $formOptions = ['method' => 'POST'];
        }

        $formClassName = $updateForm instanceof EntityUpdateFormInterface ? get_class($updateForm) : $updateForm;

        $form = $this->createForm($formClassName, $entity, $formOptions)->handleRequest($request);

        $this->dispatchFromConfig($config, 'form_init_event_name', new FormEvent($form, $request));

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($response = $this->dispatchGetResponseFromConfig($config, 'form_valid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }

                $this->manager->saveEntity($entity);

                if ($response = $this->dispatchGetResponseFromConfig($config, 'success_event_name', new GetResponseEntityEvent($entity, $request))) {
                    return $response;
                }

                return $this->redirect(!empty($config['success_redirect_to']) ? $this->generateUrl($config['success_redirect_to']) : '/');
            } else {
                if ($response = $this->dispatchGetResponseFromConfig($config, 'form_invalid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }
            }
        }

        // show view
        $viewData = new \ArrayObject([
            'form' => $form->createView(),
            $this->config['entity_attribute'] => $entity,
        ]);

        $this->dispatchFromConfig($config, 'view_event_name', new ViewEvent($viewData));

        return $this->render($config['view'], $viewData->getArrayCopy());
    }

    /**
     * @param string|EntityCreateFormInterface|null $deleteForm
     */
    public function delete(Request $request, $deleteForm = null, array $config = []): Response
    {
        $deleteForm = $deleteForm ?: $this->deleteForm;
        $config = array_replace_recursive($this->config['delete'] ?? [], $config);

        $entity = $request->attributes->get($this->config['entity_attribute']);

        if (empty($config)) {
            throw new \InvalidArgumentException('Delete action configuration is empty');
        }

        $entity = $this->manager->getRepository()->findOneBy([$config['param_converter_key'] ?? 'id' => $entity]);

        if (!empty($config['is_granted'])) {
            $this->denyAccessUnlessGranted($config['is_granted'], $entity, sprintf('Access denied, user is not %s.', $config['is_granted']));
        }

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        if (!$deleteForm instanceof EntityDeleteFormInterface && !is_string($deleteForm)) {
            throw new \InvalidArgumentException(sprintf('Delete form must be an instance of %s or a class name', EntityDeleteFormInterface::class));
        }

        if ($response = $this->dispatchGetResponseFromConfig($config, 'initialize_event_name', new GetResponseEntityEvent($entity, $request))) {
            return $response;
        }

        $form = $this->getDeleteForm($entity, $request, $deleteForm)->handleRequest($request);

        $this->dispatchFromConfig($config, 'form_init_event_name', new FormEvent($form, $request));

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($response = $this->dispatchGetResponseFromConfig($config, 'form_valid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }

                try {
                    $this->manager->deleteEntity($entity);

                    if ($response = $this->dispatchGetResponseFromConfig($config, 'success_event_name', new GetResponseEntityEvent($entity, $request))) {
                        return $response;
                    }

                    return $this->redirect(!empty($config['success_redirect_to']) ? $this->generateUrl($config['success_redirect_to']) : '/');
                } catch (\Exception $e) {
                    if ($response = $this->dispatchGetResponseFromConfig($config, 'delete_exception_event_name', new GetResponseEntityExceptionEvent($entity, $request, $e))) {
                        return $response;
                    }
                }
            } else {
                if ($response = $this->dispatchGetResponseFromConfig($config, 'form_invalid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }
            }
        }

        // show view
        $viewData = new \ArrayObject([
            'form' => $form->createView(),
            $this->config['entity_attribute'] => $entity,
        ]);

        $this->dispatchFromConfig($config, 'view_event_name', new ViewEvent($viewData));

        return $this->render($config['view'], $viewData->getArrayCopy());
    }

    /**
     * @param string|EntityListFilterFormInterface|null $listFilterForm
     */
    public function list(Request $request, $listFilterForm = null, array $config = []): Response
    {
        $listFilterForm = $listFilterForm ?: $this->listFilterForm;
        $config = array_replace_recursive($this->config['list'] ?? [], $config);

        if (empty($config)) {
            throw new \InvalidArgumentException('List action configuration is empty');
        }

        if (!empty($config['is_granted'])) {
            $this->denyAccessUnlessGranted($config['is_granted'], null, sprintf('Access denied, user is not %s.', $config['is_granted']));
        }

        if ($response = $this->dispatchGetResponseFromConfig($config, 'initialize_event_name', new GetResponseRequestEvent($request))) {
            return $response;
        }

        $repo = $this->manager->getRepository();

        if ($listFilterForm) {
            if (!$listFilterForm instanceof EntityListFilterFormInterface) {
                throw new \InvalidArgumentException(sprintf('List filter form must be an instance of %s', EntityListFilterFormInterface::class));
            }

            // additional fields for pagination and sorting
            $page = $listFilterForm->getPage($request);
            $rpp = $listFilterForm->getRpp($request);
            $orderSort = $listFilterForm->getOrder($request);

            $formClassName = get_class($listFilterForm);

            // filter form
            $form = $this->createForm($formClassName)->handleRequest($request);
            $filters = $form->isSubmitted() && $form->isValid() ? array_filter($form->getData()) : [];
        } else {
            $page = 1;
            $rpp = 10000;
            $orderSort = $config['default_order_sort'] ?? [];
            $form = null;
            $filters = [];
        }

        $this->dispatchFromConfig($config, 'filter_event_name', $filterEvent = new FilterEvent($filters, $orderSort, $page, $rpp));
        $filters = $filterEvent->getFilters();
        $orderSort = $filterEvent->getOrderSort();
        $page = $filterEvent->getPage();
        $rpp = $filterEvent->getRpp();

        // get results
        if ($repo instanceof PaginatedRepositoryInterface) {
            $entities = $repo->findPageBy($page, $rpp, $filters, $orderSort);
        } else {
            $entities = $repo->findBy($filters, $orderSort, $rpp, ($page - 1) * $rpp);
        }

        // show view
        $viewData = new \ArrayObject([
            'entities' => $entities,
            'filterForm' => $form instanceof FormInterface ? $form->createView() : null,
            'read_route' => $config['read_route'] ?? null,
        ]);

        $this->dispatchFromConfig($config, 'view_event_name', new ViewEvent($viewData));

        if ($request->isXmlHttpRequest()) {
            return $this->render($config['view_page'], $viewData->getArrayCopy());
        } else {
            return $this->render($config['view'], $viewData->getArrayCopy());
        }
    }

    /**
     * @param $entity
     */
    protected function getDeleteForm($entity, Request $request, ?EntityDeleteFormInterface $deleteForm): ?FormInterface
    {
        if (!$deleteForm) {
            return null;
        }

        if (!$deleteForm instanceof EntityDeleteFormInterface && !is_string($deleteForm)) {
            throw new \InvalidArgumentException(sprintf('Delete form must be an instance of %s or a class name', EntityDeleteFormInterface::class));
        }

        if ($deleteForm instanceof EntityDeleteFormInterface && method_exists($deleteForm, 'formOptions')) {
            $formOptions = $deleteForm->formOptions($entity, $request);
        } else {
            $formOptions = ['method' => 'POST'];
        }

        $formClassName = $deleteForm instanceof EntityDeleteFormInterface ? get_class($deleteForm) : $this->deleteForm;

        return $this->createForm($formClassName, $entity, $formOptions);
    }

    protected function dispatchGetResponseFromConfig(array $config, string $eventNameKey, GetResponseEventInterface $event): ?Response
    {
        if (isset($config[$eventNameKey])) {
            if ($response = $this->dispatchGetResponse($config[$eventNameKey], $event)) {
                return $response;
            }
        }

        return null;
    }

    protected function dispatchFromConfig(array $config, string $eventNameKey, Event $event): void
    {
        if (isset($config[$eventNameKey])) {
            $this->dispatch($config[$eventNameKey], $event);
        }
    }
}
