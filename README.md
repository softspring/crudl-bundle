
[![Latest Stable Version](https://poser.pugx.org/softspring/crudl-bundle/v/stable.svg)](https://packagist.org/packages/softspring/crudl-bundle)
[![Latest Unstable Version](https://poser.pugx.org/softspring/crudl-bundle/v/unstable.svg)](https://packagist.org/packages/softspring/crudl-bundle)
[![License](https://poser.pugx.org/softspring/crudl-bundle/license.svg)](https://packagist.org/packages/softspring/crudl-bundle)
[![Total Downloads](https://poser.pugx.org/softspring/crudl-bundle/downloads)](https://packagist.org/packages/softspring/crudl-bundle)
[![Build status](https://travis-ci.com/softspring/crudl-bundle.svg?branch=master)](https://travis-ci.com/softspring/crudl-bundle)

This tool aims to provide an easy CRUD+List (CRUDL from now on) feature for any doctrine entity you want to manage.

# Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## Applications that use or not Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require softspring/crudl-bundle
```

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
- Softspring\CrudlBundle\Event\GetResponseFormEvent

# Configure 

## Create your Manager

It's recommended to create an interface, especially if you are creating a bundle and you
 want to allow extending it.

```php
namespace App\Manager;

use Softspring\CrudlBundle\Manager\CrudlEntityManagerInterface;

interface ProductManagerInterface extends CrudlEntityManagerInterface
{

}
```

Create the manager:

```php
namespace App\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Softspring\CrudlBundle\Manager\CrudlEntityManagerTrait;
use App\Entity\Product;

class ProductManager implements ProductManagerInterface
{
    use CrudlEntityManagerTrait;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getTargetClass(): string
    {
        return Product::class;
    }
}
```

You can also extend the Softspring\CrudlBundle\Manager\DefaultCrudlEntityManager

```php
namespace App\Manager;

use Softspring\CrudlBundle\Manager\DefaultCrudlEntityManager;

class ProductManager extends DefaultCrudlEntityManager implements ProductManagerInterface
{

}
```

and configure the service: 

```yaml
services:
    App\Manager\ProductManagerInterface:
      class: App\Manager\ProductManager
      arguments:
        $targetClass: 'App\Entity\Product'
```

**Using Doctrine target entities**

If you are creating a model provider bundle, probably you will want to extend your model. 

CRUDL supports it in its managers.

```yaml
doctrine:
    orm:
        resolve_target_entities:
            My\Bundle\Model\ExampleInterface: App\Entity\Example
```

This will be the service provided by your bundle:

```yaml
services:
    My\Bundle\Manager\ExampleManagerInterface:
      class: My\Bundle\Manager\ExampleManager
      arguments:
        $targetClass: 'My\Bundle\Model\ExampleInterface'
```

The manager will return the following values:

- getTargetClass() => My\Bundle\Model\ExampleInterface
- getEntityClass() => App\Entity\Example

## Configure your controller

You need to configure your controller as a service.

The controller requires 6 arguments:

- The manager implementing Softspring\CrudlBundle\Manager\CrudlEntityManagerInterface
- listFilterForm or null
- createForm or null
- updateForm or null
- deleteForm or null
- config: an array with controller configuration

```yaml
services:
  product.controller:
    class: Softspring\CrudlBundle\Controller\CrudlController
    public: true
    calls:
      - { method: setContainer, arguments: ['@service_container'] }
    arguments:
      $manager: '@App\Manager\ProductManagerInterface'
      $createForm: '@App\Form\Admin\ProductCreateFormInterface'
      $updateForm: null
      $deleteForm: null
      $listFilterForm: '@App\Form\Admin\ProductListFilterFormInterface'
      $config:
        ...
```

### Controller configuration

```yaml
    $config:
      entity_attribute: 'product'
```

This is used for route attribute and view data passing. 

If no entity_attribute is set, 'entity' name will be used.
      
#### Create action configuration

This is the list action configuration reference:

```yaml
    $config:
        create:
            is_granted: 'ROLE_ADMIN_PRODUCT_CREATE'
            success_redirect_to: 'app_admin_product_list'
            view: 'admin/products/create.html.twig'
            initialize_event_name: 'product_admin.create.initialize'
            form_init_event_name: 'product_admin.create.form_init'
            form_valid_event_name: 'product_admin.create.form_valid'
            success_event_name: 'product_admin.create.success'
            form_invalid_event_name: 'product_admin.create.form_invalid'
            view_event_name: 'product_admin.create.view'
```

Main fields:

- **is_granted**: (optional) role name to check at the begining
- **view**: (required) the view path for rendering list
- **success_redirect_to**: (optional) route name to redirect o success

Events configuration:

- **initialize_event_name**: (optional) event dispatched after checking is_granded and before form processing.
  Dispatches Softspring\CoreBundle\Event\GetResponseRequestEvent object
  It allows, for example, to redirect on custom situation.
- **form_init_event_name**: (optional) event dispatched after form creation but before process it
  Dispatches Softspring\CrudlBundle\Event\GetResponseEntityEvent object
  It allows to modify form.
- **form_valid_event_name**: (optional) dispatched on form submitted and valid
  Dispatches Softspring\CrudlBundle\Event\GetResponseFormEvent object
  It allows to modify model before saving it.
- **success_event_name**: (optional)
  Dispatches Softspring\CrudlBundle\Event\GetResponseEntityEvent object
  It allows to make changes after changes are applied, or redirect.
- **form_invalid_event_name**: (optional) dispatched on form submitted and invalid
  Dispatches Softspring\CrudlBundle\Event\GetResponseFormEvent object
  It allows to process form errors.
- **view_event_name**: (optional)
  Dispatches Softspring\CoreBundle\Event\ViewEvent object
  Allows data adding for the view.

#### Read action configuration

This is the list action configuration reference:

```yaml
    $config:
        read:
          is_granted: 'ROLE_ADMIN_PRODUCT_READ'
            param_converter_key: 'id'
            view: 'admin/products/read.html.twig'
            initialize_event_name: 'product_admin.read.initialize'
            view_event_name: 'product_admin.read.view'
```

Main fields:

- **view**: (required) the view path for rendering list
- **param_converter_key**: (optional) field used for quering, default value is 'id'

Events configuration:

- **initialize_event_name**: (optional) event dispatched after checking is_granded and before form processing.
  Dispatches Softspring\CoreBundle\Event\GetResponseRequestEvent object
  It allows, for example, to redirect on custom situation.
- **view_event_name**: (optional)
  Dispatches Softspring\CoreBundle\Event\ViewEvent object
  Allows data adding for the view.
  
#### Update action configuration

This is the list action configuration reference:

```yaml
    $config:
        update:
            is_granted: 'ROLE_ADMIN_PRODUCT_UPDATE'
            success_redirect_to: 'app_admin_product_list'
            view: 'admin/products/update.html.twig'
            initialize_event_name: 'product_admin.update.initialize'
            form_init_event_name: 'product_admin.update.form_init'
            form_valid_event_name: 'product_admin.update.form_valid'
            success_event_name: 'product_admin.update.success'
            form_invalid_event_name: 'product_admin.update.form_invalid'
            view_event_name: 'product_admin.update.view'
```

Main fields:

- **is_granted**: (optional) role name to check at the begining
- **view**: (required) the view path for rendering list
- **success_redirect_to**: (optional) route name to redirect o success

Events configuration:

- **initialize_event_name**: (optional) event dispatched after checking is_granded and before form processing.
  Dispatches Softspring\CoreBundle\Event\GetResponseRequestEvent object
  It allows, for example, to redirect on custom situation.
- **form_init_event_name**: (optional) event dispatched after form creation but before process it
  Dispatches Softspring\CrudlBundle\Event\GetResponseEntityEvent object
  It allows to modify form.
- **form_valid_event_name**: (optional) dispatched on form submitted and valid
  Dispatches Softspring\CrudlBundle\Event\GetResponseFormEvent object
  It allows to modify model before saving it.
- **success_event_name**: (optional)
  Dispatches Softspring\CrudlBundle\Event\GetResponseEntityEvent object
  It allows to make changes after changes are applied, or redirect.
- **form_invalid_event_name**: (optional) dispatched on form submitted and invalid
  Dispatches Softspring\CrudlBundle\Event\GetResponseFormEvent object
  It allows to process form errors.
- **view_event_name**: (optional)
  Dispatches Softspring\CoreBundle\Event\ViewEvent object
  Allows data adding for the view.
  
#### Delete action configuration

This is the list action configuration reference:

```yaml
    $config:
      delete:
        is_granted: 'ROLE_ADMIN_PRODUCT_DELETE'
        success_redirect_to: 'app_admin_product_list'
        view: 'admin/products/delete.html.twig'
        initialize_event_name: 'product_admin.delete.initialize'
        form_init_event_name: 'product_admin.delete.form_init'
        form_valid_event_name: 'product_admin.delete.form_valid'
        success_event_name: 'product_admin.delete.success'
        form_invalid_event_name: 'product_admin.delete.form_invalid'
        view_event_name: 'product_admin.delete.view'
```

Main fields:

- **is_granted**: (optional) role name to check at the begining
- **view**: (required) the view path for rendering list
- **success_redirect_to**: (optional) route name to redirect o success

Events configuration:

- **initialize_event_name**: (optional) event dispatched after checking is_granded and before form processing.
  Dispatches Softspring\CoreBundle\Event\GetResponseRequestEvent object
  It allows, for example, to redirect on custom situation.
- **form_init_event_name**: (optional) event dispatched after form creation but before process it
  Dispatches Softspring\CrudlBundle\Event\GetResponseEntityEvent object
  It allows to modify form.
- **form_valid_event_name**: (optional) dispatched on form submitted and valid
  Dispatches Softspring\CrudlBundle\Event\GetResponseFormEvent object
  It allows to modify model before saving it.
- **success_event_name**: (optional)
  Dispatches Softspring\CrudlBundle\Event\GetResponseEntityEvent object
  It allows to make changes after changes are applied, or redirect.
- **form_invalid_event_name**: (optional) dispatched on form submitted and invalid
  Dispatches Softspring\CrudlBundle\Event\GetResponseFormEvent object
  It allows to process form errors.
- **view_event_name**: (optional)
  Dispatches Softspring\CoreBundle\Event\ViewEvent object
  Allows data adding for the view.
  
#### List action configuration

This is the list action configuration reference:

```yaml
    $config:
        list:
            is_granted: 'ROLE_ADMIN_PRODUCT_LIST'
            read_route: 'app_admin_product_details'
            view: 'admin/products/list.html.twig'
            view_page: 'admin/products/list-page.html.twig'
            initialize_event_name: 'product_admin.list.initialize'
            filter_event_name: 'product_admin.list.filter'
            view_event_name: !php/const App\Events::ADMIN_PRODUCT_LIST_VIEW
            default_order_sort: <default_order_sort>
```

Main fields:

- **is_granted**: (optional) role name to check at the begining
- **read_route**: (optional) route name to read action, used to pass it to view
- **view**: (required) the view path for rendering list
- **view_page**: (optional) the view path for ajax requests to return only page results

Events configuration:

- **initialize_event_name**: (optional) event dispatched after checking is_granded and before form processing.
  Dispatches Softspring\CoreBundle\Event\GetResponseRequestEvent object
  It allows, for example, to redirect on custom situation.
- **filter_event_name**: (optional) event dispatched after form processing and before quering.
  Dispatches Softspring\CrudlBundle\Event\FilterEvent object
  With this event you are able to modify quering criteria or other data before quering.
- **view_event_name**: (optional)
  Dispatches Softspring\CoreBundle\Event\ViewEvent object
  Allows data adding for the view. 

Other fields:

- **default_order_sort**: (optional) is used in case no list filter form configured.

### Routing configuration

Now you need to configure the routes. 

Remembrer, none of them is mandatory, you can configure just the ones you need.

```yaml
# config/routes/admin_product.yaml

app_admin_product_list:
    controller: product.controller::list
    path: /

app_admin_product_create:
    controller: product.controller::create
    path: /create

app_admin_product_update:
    controller: product.controller::update
    path: /{product}/update

app_admin_product_delete:
    controller: product.controller::delete
    path: /{product}/delete

app_admin_product_read:
    controller: product.controller::read
    path: /{product}
```

```yaml
# config/routes.yaml

app_admin_product_routes:
    resource: 'routes/admin_product.yaml'
    prefix: "/admin/product"
```
