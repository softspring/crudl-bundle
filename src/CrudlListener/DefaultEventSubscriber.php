<?php

namespace Softspring\CrudlBundle\CrudlListener;

use Softspring\Component\CrudlController\Event\ViewEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DefaultEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sfs_crudl.default.list.view' => [['onListViewAddData', 0]],
        ];
    }

    public function onListViewAddData(ViewEvent $event): void
    {
        if (empty($event->getData()['table_columns'])) {
            $manager = $event->getManager();
            $reflection = $manager->getEntityClassReflection();
            $columns = $reflection->getProperties();

            foreach ($columns as $column) {
                $event->getData()['table_columns'][$column->getName()] = [
                    'label' => $column->getName(),
                    'sortable' => false,
                ];
            }
        }
    }
}
