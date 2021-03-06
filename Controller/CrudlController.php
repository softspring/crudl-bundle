<?php

namespace Softspring\CrudlBundle\Controller;

use Jhg\DoctrinePagination\ORM\PaginatedRepositoryInterface;
use Softspring\CoreBundle\Event\FormEvent;
use Softspring\CoreBundle\Event\GetResponseEventInterface;
use Softspring\CoreBundle\Event\GetResponseRequestEvent;
use Softspring\CrudlBundle\Event\FilterEvent;
use Softspring\CrudlBundle\Event\GetResponseEntityEvent;
use Softspring\CrudlBundle\Event\GetResponseEntityExceptionEvent;
use Softspring\CrudlBundle\Event\GetResponseFormEvent;
use Softspring\CrudlBundle\Form\EntityCreateFormInterface;
use Softspring\CrudlBundle\Form\EntityDeleteFormInterface;
use Softspring\CrudlBundle\Form\EntityListFilterFormInterface;
use Softspring\CrudlBundle\Form\EntityUpdateFormInterface;
use Softspring\CrudlBundle\Manager\CrudlEntityManagerInterface;
use Softspring\CoreBundle\Controller\AbstractController;
use Softspring\CoreBundle\Event\ViewEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Entity CRUDL controller (CRUD+listing)
 */
class CrudlController extends AbstractController
{
    /**
     * @var CrudlEntityManagerInterface
     */
    protected $manager;

    /**
     * @var EntityListFilterFormInterface|null
     */
    protected $listFilterForm;

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

    /**
     * @var array
     */
    protected $config;

    /**
     * EntityController constructor.
     * @param CrudlEntityManagerInterface $manager
     * @param EntityListFilterFormInterface|null $listFilterForm
     * @param EntityCreateFormInterface|string|null $createForm
     * @param EntityUpdateFormInterface|string|null $updateForm
     * @param EntityDeleteFormInterface|string|null $deleteForm
     * @param array $config
     */
    public function __construct(CrudlEntityManagerInterface $manager, ?EntityListFilterFormInterface $listFilterForm = null, $createForm = null, $updateForm = null, $deleteForm = null, array $config = [])
    {
        $this->manager = $manager;
        $this->listFilterForm = $listFilterForm;
        $this->createForm = $createForm;
        $this->updateForm = $updateForm;
        $this->deleteForm = $deleteForm;
        $this->config = $config;
        $this->config['entity_attribute'] = $this->config['entity_attribute'] ?? 'entity';
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        if (empty($this->config['create'])) {
            throw new \InvalidArgumentException('Create action configuration is empty');
        }

        if (!empty($this->config['create']['is_granted'])) {
            $this->denyAccessUnlessGranted($this->config['create']['is_granted'], null, sprintf('Access denied, user is not %s.', $this->config['create']['is_granted']));
        }

        if (!$this->createForm instanceof EntityCreateFormInterface && !is_string($this->createForm)) {
            throw new \InvalidArgumentException(sprintf('Create form must be an instance of %s or a class name', EntityCreateFormInterface::class));
        }

        $newEntity = $this->manager->createEntity();

        if ($response = $this->dispatchGetResponseFromConfig('create', 'initialize_event_name', new GetResponseEntityEvent($newEntity, $request))) {
            return $response;
        }

        if ($this->createForm instanceof EntityCreateFormInterface && method_exists($this->createForm, 'formOptions')) {
            $formOptions = $this->createForm->formOptions($newEntity, $request);
        } else {
            $formOptions = ['method' => 'POST'];
        }

        $formClassName = $this->createForm instanceof EntityCreateFormInterface ? get_class($this->createForm) : $this->createForm;

        $form = $this->createForm($formClassName, $newEntity, $formOptions)->handleRequest($request);

        $this->dispatchFromConfig('create', 'form_init_event_name', new FormEvent($form, $request));

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($response = $this->dispatchGetResponseFromConfig('create', 'form_valid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }

                $this->manager->saveEntity($newEntity);

                if ($response = $this->dispatchGetResponseFromConfig('create', 'success_event_name', new GetResponseEntityEvent($newEntity, $request))) {
                    return $response;
                }

                return $this->redirect(!empty($this->config['create']['success_redirect_to']) ? $this->generateUrl($this->config['create']['success_redirect_to']) : '/');
            } else {
                if ($response = $this->dispatchGetResponseFromConfig('create', 'form_invalid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }
            }
        }

        // show view
        $viewData = new \ArrayObject([
            $this->config['entity_attribute'] => $newEntity,
            'form' => $form->createView(),
        ]);

        $this->dispatchFromConfig('create', 'view_event_name', new ViewEvent($viewData));

        return $this->render($this->config['create']['view'], $viewData->getArrayCopy());
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function read(Request $request): Response
    {
        $entity = $request->attributes->get($this->config['entity_attribute']);

        if (empty($this->config['read'])) {
            throw new \InvalidArgumentException('Read action configuration is empty');
        }

        // convert entity
        $entity = $this->manager->getRepository()->findOneBy([$this->config['read']['param_converter_key']??'id'=>$entity]);

        if (!empty($this->config['read']['is_granted'])) {
            $this->denyAccessUnlessGranted($this->config['read']['is_granted'], $entity, sprintf('Access denied, user is not %s.', $this->config['read']['is_granted']));
        }

        if ($response = $this->dispatchGetResponseFromConfig('read', 'initialize_event_name', new GetResponseEntityEvent($entity, $request))) {
            return $response;
        }

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        $deleteForm = $this->getDeleteForm($entity, $request);

        // show view
        $viewData = new \ArrayObject([
            $this->config['entity_attribute'] => $entity,
            'deleteForm' => $deleteForm ? $deleteForm->createView() : null,
        ]);

        $this->dispatchFromConfig('read', 'view_event_name', new ViewEvent($viewData));

        return $this->render($this->config['read']['view'], $viewData->getArrayCopy());
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function update(Request $request): Response
    {
        $entity = $request->attributes->get($this->config['entity_attribute']);

        if (empty($this->config['update'])) {
            throw new \InvalidArgumentException('Update action configuration is empty');
        }

        $entity = $this->manager->getRepository()->findOneBy([$this->config['update']['param_converter_key']??'id'=>$entity]);

        if (!empty($this->config['update']['is_granted'])) {
            $this->denyAccessUnlessGranted($this->config['update']['is_granted'], $entity, sprintf('Access denied, user is not %s.', $this->config['update']['is_granted']));
        }

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        if (!$this->updateForm instanceof EntityUpdateFormInterface && !is_string($this->updateForm)) {
            throw new \InvalidArgumentException(sprintf('Update form must be an instance of %s or a class name', EntityUpdateFormInterface::class));
        }

        if ($response = $this->dispatchGetResponseFromConfig('update', 'initialize_event_name', new GetResponseEntityEvent($entity, $request))) {
            return $response;
        }

        if ($this->updateForm instanceof EntityUpdateFormInterface && method_exists($this->updateForm, 'formOptions')) {
            $formOptions = $this->updateForm->formOptions($entity, $request);
        } else {
            $formOptions = ['method' => 'POST'];
        }

        $formClassName = $this->updateForm instanceof EntityUpdateFormInterface ? get_class($this->updateForm) : $this->updateForm;

        $form = $this->createForm($formClassName, $entity, $formOptions)->handleRequest($request);

        $this->dispatchFromConfig('update', 'form_init_event_name', new FormEvent($form, $request));

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($response = $this->dispatchGetResponseFromConfig('update', 'form_valid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }

                $this->manager->saveEntity($entity);

                if ($response = $this->dispatchGetResponseFromConfig('update', 'success_event_name', new GetResponseEntityEvent($entity, $request))) {
                    return $response;
                }

                return $this->redirect(!empty($this->config['update']['success_redirect_to']) ? $this->generateUrl($this->config['update']['success_redirect_to']) : '/');
            } else {
                if ($response = $this->dispatchGetResponseFromConfig('update', 'form_invalid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }
            }
        }

        // show view
        $viewData = new \ArrayObject([
            'form' => $form->createView(),
            $this->config['entity_attribute'] => $entity,
        ]);

        $this->dispatchFromConfig('update', 'view_event_name', new ViewEvent($viewData));

        return $this->render($this->config['update']['view'], $viewData->getArrayCopy());
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $entity = $request->attributes->get($this->config['entity_attribute']);

        if (empty($this->config['delete'])) {
            throw new \InvalidArgumentException('Delete action configuration is empty');
        }

        $entity = $this->manager->getRepository()->findOneBy([$this->config['delete']['param_converter_key']??'id'=>$entity]);

        if (!empty($this->config['delete']['is_granted'])) {
            $this->denyAccessUnlessGranted($this->config['delete']['is_granted'], $entity, sprintf('Access denied, user is not %s.', $this->config['delete']['is_granted']));
        }

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        if (!$this->deleteForm instanceof EntityDeleteFormInterface && !is_string($this->deleteForm)) {
            throw new \InvalidArgumentException(sprintf('Delete form must be an instance of %s or a class name', EntityDeleteFormInterface::class));
        }

        if ($response = $this->dispatchGetResponseFromConfig('delete', 'initialize_event_name', new GetResponseEntityEvent($entity, $request))) {
            return $response;
        }

        $form = $this->getDeleteForm($entity, $request)->handleRequest($request);

        $this->dispatchFromConfig('delete', 'form_init_event_name', new FormEvent($form, $request));

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($response = $this->dispatchGetResponseFromConfig('delete', 'form_valid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }

                try {
                    $this->manager->deleteEntity($entity);

                    if ($response = $this->dispatchGetResponseFromConfig('delete', 'success_event_name', new GetResponseEntityEvent($entity, $request))) {
                        return $response;
                    }

                    return $this->redirect(!empty($this->config['delete']['success_redirect_to']) ? $this->generateUrl($this->config['delete']['success_redirect_to']) : '/');
                } catch (\Exception $e) {
                    if ($response = $this->dispatchGetResponseFromConfig('delete', 'delete_exception_event_name', new GetResponseEntityExceptionEvent($entity, $request, $e))) {
                        return $response;
                    }
                }
            } else {
                if ($response = $this->dispatchGetResponseFromConfig('delete', 'form_invalid_event_name', new GetResponseFormEvent($form, $request))) {
                    return $response;
                }
            }
        }

        // show view
        $viewData = new \ArrayObject([
            'form' => $form->createView(),
            $this->config['entity_attribute'] => $entity,
        ]);

        $this->dispatchFromConfig('delete', 'view_event_name', new ViewEvent($viewData));

        return $this->render($this->config['delete']['view'], $viewData->getArrayCopy());
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function list(Request $request): Response
    {
        if (empty($this->config['list'])) {
            throw new \InvalidArgumentException('List action configuration is empty');
        }

        if (!empty($this->config['list']['is_granted'])) {
            $this->denyAccessUnlessGranted($this->config['list']['is_granted'], null, sprintf('Access denied, user is not %s.', $this->config['list']['is_granted']));
        }

        if ($response = $this->dispatchGetResponseFromConfig('list', 'initialize_event_name', new GetResponseRequestEvent($request))) {
            return $response;
        }

        $repo = $this->manager->getRepository();

        if ($this->listFilterForm) {
            if (!$this->listFilterForm instanceof EntityListFilterFormInterface) {
                throw new \InvalidArgumentException(sprintf('List filter form must be an instance of %s', EntityListFilterFormInterface::class));
            }

            // additional fields for pagination and sorting
            $page = $this->listFilterForm->getPage($request);
            $rpp = $this->listFilterForm->getRpp($request);
            $orderSort = $this->listFilterForm->getOrder($request);

            $formClassName = get_class($this->listFilterForm);

            // filter form
            $form = $this->createForm($formClassName)->handleRequest($request);
            $filters = $form->isSubmitted() && $form->isValid() ? array_filter($form->getData()) : [];
        } else {
            $page = 1;
            $rpp = null;
            $orderSort = $this->config['list']['default_order_sort'] ?? [];
            $form = null;
            $filters = [];
        }

        $this->dispatchFromConfig('list', 'filter_event_name', $filterEvent = new FilterEvent($filters, $orderSort, $page, $rpp));
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
            'read_route' => $this->config['list']['read_route'] ?? null,
        ]);

        $this->dispatchFromConfig('list', 'view_event_name', new ViewEvent($viewData));

        if ($request->isXmlHttpRequest()) {
            return $this->render($this->config['list']['view_page'], $viewData->getArrayCopy());
        } else {
            return $this->render($this->config['list']['view'], $viewData->getArrayCopy());
        }
    }

    /**
     * @param object $entity
     *
     * @return FormInterface|null
     */
    protected function getDeleteForm($entity, Request $request): ?FormInterface
    {
        if (!$this->deleteForm) {
            return null;
        }

        if (!$this->deleteForm instanceof EntityDeleteFormInterface && !is_string($this->deleteForm)) {
            throw new \InvalidArgumentException(sprintf('Delete form must be an instance of %s or a class name', EntityDeleteFormInterface::class));
        }

        if ($this->deleteForm instanceof EntityDeleteFormInterface && method_exists($this->deleteForm, 'formOptions')) {
            $formOptions = $this->deleteForm->formOptions($entity, $request);
        } else {
            $formOptions = ['method' => 'POST'];
        }

        $formClassName = $this->deleteForm instanceof EntityDeleteFormInterface ? get_class($this->deleteForm) : $this->deleteForm;

        return $this->createForm($formClassName, $entity, $formOptions);
    }

    /**
     * @param string                    $configBlock
     * @param string                    $eventNameKey
     * @param GetResponseEventInterface $event
     *
     * @return Response|null
     */
    protected function dispatchGetResponseFromConfig(string $configBlock, string $eventNameKey, GetResponseEventInterface $event): ?Response
    {
        if (isset($this->config[$configBlock][$eventNameKey])) {
            if ($response = $this->dispatchGetResponse($this->config[$configBlock][$eventNameKey], $event)) {
                return $response;
            }
        }

        return null;
    }

    /**
     * @param string $configBlock
     * @param string $eventNameKey
     * @param Event  $event
     */
    protected function dispatchFromConfig(string $configBlock, string $eventNameKey, Event $event): void
    {
        if (isset($this->config[$configBlock][$eventNameKey])) {
            $this->dispatch($this->config[$configBlock][$eventNameKey], $event);
        }
    }
}