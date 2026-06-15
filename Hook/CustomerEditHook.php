<?php

namespace PaymentCondition\Hook;

use PaymentCondition\Model\PaymentCustomerConditionQuery;
use PaymentCondition\Model\PaymentCustomerModuleConditionQuery;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;

class CustomerEditHook extends BaseHook
{
    public static function getSubscribedHooks(): array
    {
        return [
            'customer.edit' => [
                ['type' => 'back', 'method' => 'onCustomerEdit'],
            ],
        ];
    }

    public function onCustomerEdit(HookRenderEvent $event): void
    {
        $customerId = $event->getArgument('customer_id');

        $paymentCustomerCondition = PaymentCustomerConditionQuery::create()
            ->findOneByCustomerId($customerId);

        $paymentCustomerModuleConditions = PaymentCustomerModuleConditionQuery::create()
            ->findByCustomerId($customerId);

        $allowedModules = [];
        foreach ($paymentCustomerModuleConditions as $paymentCustomerModuleCondition) {
            if ($paymentCustomerModuleCondition->getIsValid()) {
                $allowedModules[] = $paymentCustomerModuleCondition->getModule();
            }
        }

        $event->add($this->render($this->resolveTemplateName('PaymentCondition/customer_edit_hook'), compact('paymentCustomerCondition', 'allowedModules')));
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
