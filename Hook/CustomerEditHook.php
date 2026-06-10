<?php

namespace PaymentCondition\Hook;

use PaymentCondition\Model\PaymentCustomerConditionQuery;
use PaymentCondition\Model\PaymentCustomerModuleConditionQuery;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

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

        $event->add($this->render('PaymentCondition/customer_edit_hook.html.twig', compact('paymentCustomerCondition', 'allowedModules')));
    }
}
