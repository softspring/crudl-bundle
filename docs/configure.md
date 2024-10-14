
```yaml
# config/packages/sfs_crudl.yaml
sfs_crudl:
    controllers:
        clients:
            entity_manager: 'App\Manager\ClientsManager'
            actions:
                list_clients:
                    action: 'list'
                    is_granted: 'CLIENTS_LIST'
                    initialize_event_name: client_list_initialize
                    filter_event_name: client_list_filter
                    view_event_name: client_list_view
                    view: 'app/client/list.html.twig'
                    view_page: 'app/client/list_page.html.twig'
                    filter_form: 'App\Form\ClientListFilterForm'
```

```yaml
# config/routes/clients.yaml

# option 1, using the configuration action
clients_list_option1:
    controller: sfs_crudl.controller.client
    path: /clients-option-1
    defaults:
        configKey: 'list_clients'

# option 2, using the specific method
clients_list_option2:
    controller: sfs_crudl.controller.client::list
    path: /clients-option-2
    defaults:
        configKey: 'list_clients'

# option 3, using the action name
clients_list_option3:
    controller: sfs_crudl.controller.client::list_clients
    path: /clients-option-3

```