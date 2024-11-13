<?php

namespace Softspring\CrudlBundle\Controller;

use BadMethodCallException;
use Softspring\Component\CrudlController\Config\Configuration;
use Softspring\Component\CrudlController\Controller\CrudlController as BaseCrudlController;
use Softspring\CrudlBundle\Helper\FormActionActionHelper;
use Softspring\CrudlBundle\Helper\ListActionHelper;
use Softspring\CrudlBundle\Helper\TransitionActionHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CrudlController extends BaseCrudlController
{
    public function __invoke(Request $request, string $configKey, array $config = []): Response
    {
        $action = $request->attributes->get('_action', $this->configs[$configKey]['action']);

        return $this->$action($request, configKey: $configKey, config: $config);
    }

    public function __call(string $configKey, array $arguments)
    {
        if (!isset($this->configs[$configKey])) {
            throw new BadMethodCallException(sprintf('Method %s not found in %s', $configKey, static::class));
        }

        $request = $arguments[0];

        $action = $this->configs[$configKey]['action'];

        return $this->$action($request, configKey: $configKey, config: []);
    }

    protected function buildListActionHelper(Request $request, array $config, string $configKey): ListActionHelper
    {
        $helper = new ListActionHelper($this->manager, $this->eventDispatcher, $this->twig, $this->authorizationChecker, $this->router, $this->formFactory);
        $helper->setConfig(Configuration::listAction($configKey, $this->configs, $config));
        $helper->setRequest($request);

        return $helper;
    }

    protected function buildCreateActionHelper(Request $request, array $config, string $configKey): FormActionActionHelper
    {
        $helper = new FormActionActionHelper($this->manager, $this->eventDispatcher, $this->twig, $this->authorizationChecker, $this->router, $this->formFactory);
        $helper->setConfig(Configuration::createAction($configKey, $this->configs, $config));
        $helper->setRequest($request);

        return $helper;
    }

    protected function buildUpdateActionHelper(Request $request, array $config, string $configKey): FormActionActionHelper
    {
        $helper = new FormActionActionHelper($this->manager, $this->eventDispatcher, $this->twig, $this->authorizationChecker, $this->router, $this->formFactory);
        $helper->setConfig(Configuration::updateAction($configKey, $this->configs, $config));
        $helper->setRequest($request);

        return $helper;
    }

    protected function buildTransitionActionHelper(Request $request, array $config, string $configKey): TransitionActionHelper
    {
        $helper = new TransitionActionHelper($this->manager, $this->eventDispatcher, $this->twig, $this->authorizationChecker, $this->router, $this->formFactory, $this->registry);
        $helper->setConfig(Configuration::transitionAction($configKey, $this->configs, $config));
        $helper->setRequest($request);

        return $helper;
    }
}
