<?php

namespace PaymentCondition\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\ModuleQuery;

class ConfigurationHook extends BaseHook
{
    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfiguration'],
            ],
        ];
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $customerFamilyModule = ModuleQuery::create()->findOneByCode('CustomerFamily');
        $customerFamilyEnabled = null !== $customerFamilyModule && 0 !== $customerFamilyModule->getActivate();

        $event->add($this->render('PaymentCondition/module_configuration.html.twig', [
            'customerFamilyEnabled' => $customerFamilyEnabled,
        ]));
    }
}
