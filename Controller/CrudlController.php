<?php

namespace Softspring\CrudlBundle\Controller;

use Jhg\DoctrinePagination\ORM\PaginatedRepositoryInterface;
use Softspring\CrudlBundle\Event\GetResponseEntityEvent;
use Softspring\CrudlBundle\Event\GetResponseFormEvent;
use Softspring\CrudlBundle\Form\EntityCreateFormInterface;
use Softspring\CrudlBundle\Form\EntityDeleteFormInterface;
use Softspring\CrudlBundle\Form\EntityListFilterFormInterface;
use Softspring\CrudlBundle\Form\EntityUpdateFormInterface;
use Softspring\CrudlBundle\Manager\CrudlEntityManagerInterface;
use Softspring\CoreBundle\Controller\AbstractController;
use Softspring\CoreBundle\Event\GetResponseEvent;
use Softspring\CoreBundle\Event\ViewEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var EntityListFilterFormInterface|null
     */
    protected $listFilterForm;

    /**
     * @var EntityCreateFormInterface|null
     */
    protected $createForm;

    /**
     * @var EntityUpdateFormInterface|null
     */
    protected $updateForm;

    /**
     * @var EntityDeleteFormInterface|null
     */
    protected $deleteForm;

    /**
     * @var array
     */
    protected $config;

    /**
     * EntityController constructor.
     * @param CrudlEntityManagerInterface $manager
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityListFilterFormInterface|null $listFilterForm
     * @param EntityCreateFormInterface|null $createForm
     * @param EntityUpdateFormInterface|null $updateForm
     * @param EntityDeleteFormInterface|null $deleteForm
     * @param array $config
     */
    public function __construct(CrudlEntityManagerInterface $manager, EventDispatcherInterface $eventDispatcher, ?EntityListFilterFormInterface $listFilterForm = null, ?EntityCreateFormInterface $createForm = null, ?EntityUpdateFormInterface $updateForm = null, ?EntityDeleteFormInterface $deleteForm = null, array $config = [])
    {
        $this->manager = $manager;
        $this->eventDispatcher = $eventDispatcher;
        $this->listFilterForm = $listFilterForm;
        $this->createForm = $createForm;
        $this->updateForm = $updateForm;
        $this->deleteForm = $deleteForm;
        $this->config = $config;
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

        if (!$this->createForm instanceof EntityCreateFormInterface) {
            throw new \InvalidArgumentException(sprintf('Create form must be an instance of %s', EntityCreateFormInterface::class));
        }

        $newEntity = $this->manager->createEntity();

        if (isset($this->config['create']['initialize_event_name'])) {
            if ($response = $this->dispatchGetResponse($this->config['create']['initialize_event_name'], new GetResponseEntityEvent($newEntity, $request))) {
                return $response;
            }
        }

        $form = $this->createForm(get_class($this->createForm), $newEntity, ['method' => 'POST'])->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if (isset($this->config['create']['form_valid_event_name'])) {
                    if ($response = $this->dispatchGetResponse($this->config['create']['form_valid_event_name'], new GetResponseFormEvent($form, $request))) {
                        return $response;
                    }
                }

                $this->manager->saveEntity($newEntity);

                if (isset($this->config['create']['success_event_name'])) {
                    if ($response = $this->dispatchGetResponse($this->config['create']['success_event_name'], new GetResponseEntityEvent($newEntity, $request))) {
                        return $response;
                    }
                }

                return $this->redirect(!empty($this->config['create']['success_redirect_to']) ? $this->generateUrl($this->config['create']['success_redirect_to']) : '/');
            } else {
                if (isset($this->config['create']['form_invalid_event_name'])) {
                    if ($response = $this->dispatchGetResponse($this->config['create']['form_invalid_event_name'], new GetResponseFormEvent($form, $request))) {
                        return $response;
                    }
                }
            }
        }

        // show view
        $viewData = new \ArrayObject([
            'form' => $form->createView(),
        ]);

        if (isset($this->config['create']['view_event_name'])) {
            $this->eventDispatcher->dispatch(new ViewEvent($viewData), $this->config['create']['view_event_name']);
        }

        return $this->render($this->config['create']['view'], $viewData->getArrayCopy());
    }

    /**
     * @param string $entity
     * @param Request $request
     * @return Response
     */
    public function read(string $entity, Request $request): Response
    {
        if (empty($this->config['read'])) {
            throw new \InvalidArgumentException('Read action configuration is empty');
        }

        // convert entity
        $entity = $this->manager->getRepository()->findOneBy([$this->config['read']['param_converter_key']??'id'=>$entity]);

        if (!empty($this->config['read']['is_granted'])) {
            $this->denyAccessUnlessGranted($this->config['read']['is_granted'], $entity, sprintf('Access denied, user is not %s.', $this->config['read']['is_granted']));
        }

        if (!empty($this->config['read']['initialize_event_name']) && $response = $this->dispatchGetResponse($this->config['read']['initialize_event_name'], new GetResponseEvent($request))) {
            return $response;
        }

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        // show view
        $viewData = new \ArrayObject([
            'entity' => $entity,
        ]);

        if (isset($this->config['read']['view_event_name'])) {
            $this->eventDispatcher->dispatch(new ViewEvent($viewData), $this->config['read']['view_event_name']);
        }

        return $this->render($this->config['read']['view'], $viewData->getArrayCopy());
    }

    /**
     * @param string $entity
     * @param Request $request
     * @return Response
     */
    public function update(string $entity, Request $request): Response
    {
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

        if (!$this->updateForm instanceof EntityUpdateFormInterface) {
            throw new \InvalidArgumentException(sprintf('Update form must be an instance of %s', EntityUpdateFormInterface::class));
        }

        if (isset($this->config['update']['initialize_event_name'])) {
            if ($response = $this->dispatchGetResponse($this->config['update']['initialize_event_name'], new GetResponseEntityEvent($entity, $request))) {
                return $response;
            }
        }

        $form = $this->createForm(get_class($this->updateForm), $entity, ['method' => 'POST'])->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if (isset($this->config['update']['form_valid_event_name'])) {
                    if ($response = $this->dispatchGetResponse($this->config['update']['form_valid_event_name'], new GetResponseFormEvent($form, $request))) {
                        return $response;
                    }
                }

                $this->manager->saveEntity($entity);

                if (isset($this->config['update']['success_event_name'])) {
                    if ($response = $this->dispatchGetResponse($this->config['update']['success_event_name'], new GetResponseEntityEvent($entity, $request))) {
                        return $response;
                    }
                }

                return $this->redirect(!empty($this->config['update']['success_redirect_to']) ? $this->generateUrl($this->config['update']['success_redirect_to']) : '/');
            } else {
                if (isset($this->config['update']['form_invalid_event_name'])) {
                    if ($response = $this->dispatchGetResponse($this->config['update']['form_invalid_event_name'], new GetResponseFormEvent($form, $request))) {
                        return $response;
                    }
                }
            }
        }

        // show view
        $viewData = new \ArrayObject([
            'form' => $form->createView(),
            'entity' => $entity,
        ]);

        if (isset($this->config['update']['view_event_name'])) {
            $this->eventDispatcher->dispatch(new ViewEvent($viewData), $this->config['update']['view_event_name']);
        }

        return $this->render($this->config['update']['view'], $viewData->getArrayCopy());
    }

    /**
     * @param string $entity
     * @param Request $request
     * @return Response
     */
    public function delete(string $entity, Request $request): Response
    {
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

        if (!$this->deleteForm instanceof EntityDeleteFormInterface) {
            throw new \InvalidArgumentException(sprintf('Delete form must be an instance of %s', EntityDeleteFormInterface::class));
        }

        if (isset($this->config['delete']['initialize_event_name'])) {
            if ($response = $this->dispatchGetResponse($this->config['delete']['initialize_event_name'], new GetResponseEntityEvent($entity, $request))) {
                return $response;
            }
        }

        $form = $this->createForm(get_class($this->deleteForm), $entity, ['method' => 'POST'])->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if (isset($this->config['delete']['form_valid_event_name'])) {
                    if ($response = $this->dispatchGetResponse($this->config['delete']['form_valid_event_name'], new GetResponseFormEvent($form, $request))) {
                        return $response;
                    }
                }

                $this->remove($entity, null, true);

                if (isset($this->config['delete']['success_event_name'])) {
                    if ($response = $this->dispatchGetResponse($this->config['delete']['success_event_name'], new GetResponseEntityEvent($entity, $request))) {
                        return $response;
                    }
                }

                return $this->redirect(!empty($this->config['delete']['success_redirect_to']) ? $this->generateUrl($this->config['delete']['success_redirect_to']) : '/');
            } else {
                if (isset($this->config['delete']['form_invalid_event_name'])) {
                    if ($response = $this->dispatchGetResponse($this->config['delete']['form_invalid_event_name'], new GetResponseFormEvent($form, $request))) {
                        return $response;
                    }
                }
            }
        }

        // show view
        $viewData = new \ArrayObject([
            'form' => $form->createView(),
            'entity' => $entity,
        ]);

        if (isset($this->config['delete']['view_event_name'])) {
            $this->eventDispatcher->dispatch(new ViewEvent($viewData), $this->config['delete']['view_event_name']);
        }

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

        if (!empty($this->config['list']['initialize_event_name']) && $response = $this->dispatchGetResponse($this->config['list']['initialize_event_name'], new GetResponseEvent($request))) {
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

            // filter form
            $form = $this->createForm(get_class($this->listFilterForm))->handleRequest($request);
            $filters = $form->isSubmitted() && $form->isValid() ? array_filter($form->getData()) : [];
        } else {
            $page = 1;
            $rpp = null;
            $orderSort = $this->config['list']['default_order_sort'] ?? null;
            $form = null;
            $filters = [];
        }

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

        if (isset($this->config['list']['view_event_name'])) {
            $this->eventDispatcher->dispatch(new ViewEvent($viewData), $this->config['list']['view_event_name']);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render($this->config['list']['view_page'], $viewData->getArrayCopy());
        } else {
            return $this->render($this->config['list']['view'], $viewData->getArrayCopy());
        }
    }
}