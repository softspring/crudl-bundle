
# Structure

The CRUDL is based on a Manager, a Controller, some forms and a lot of events.

## Manager

The Manager will take care of entity management, acting as a factory of entities and doing the doctrine calls.

Every CRUDL manager must implements Softspring\CrudlBundle\Manager\CrudlEntityManagerInterface interface, witch defines:

- getTargetClass()
- getEntityClass()
- getEntityClassReflection()
- getRepository()
- createEntity()
- saveEntity()
- deleteEntity()

## Controller

The CRUDL controller performs the following actions:

- create
- read
- update
- delete
- list

None of those actions are required, you will be able to enable just one or more of them.

## Forms

Controller actions (all except read action) requires a form to work. These forms must
extend the provided interfaces:

- Softspring\CrudlBundle\Form\EntityCreateFormInterface
- Softspring\CrudlBundle\Form\EntityDeleteFormInterface
- Softspring\CrudlBundle\Form\EntityListFilterFormInterface
- Softspring\CrudlBundle\Form\EntityUpdateFormInterface

## Events

Every form action dispatches a lot of events, that allows to extend functionality, checking
values, security, adding view data, or anything we need to do into the action flow.

For example, create action dispatches following events:

- initialize event: before doing anything after creating a new entity
- form_init event: after form creation
- form_valid event: on successful submit and before performing flush
- success event: on successful submit and after performing flush
- form_invalid event: on failure submit
- view event: after everything has been processed, and before creating view

Each of those events, dispatch an object of next classes:

- Softspring\CoreBundle\Event\FormEvent
- Softspring\CoreBundle\Event\GetResponseRequestEvent
- Softspring\CoreBundle\Event\ViewEvent
- Softspring\CrudlBundle\Event\FilterEvent
- Softspring\CrudlBundle\Event\GetResponseEntityEvent
- Softspring\CrudlBundle\Event\GetResponseEntityExceptionEvent
- Softspring\CrudlBundle\Event\GetResponseFormEvent