<?php

namespace PaymentCondition\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;
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

        $event->add($this->render($this->resolveTemplateName('PaymentCondition/module_configuration'), [
            'customerFamilyEnabled' => $customerFamilyEnabled,
        ]));
    }

    /**
     * Append the current parser extension so the same hook serves the Smarty (default)
     * and Twig (default-twig) back-office templates: Smarty -> ".html", Twig -> ".html.twig".
     */
    private function resolveTemplateName(string $baseName): string
    {
        $extension = ParserResolver::getCurrentParser()?->getFileExtension() ?? 'html';

        return $baseName.'.'.$extension;
    }
}
