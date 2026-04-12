<?php

namespace PaymentCondition\EventListeners;

use PaymentCondition\Service\PaymentConditionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Payment\IsValidPaymentEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\ModuleQuery;

class PaymentListener implements EventSubscriberInterface
{
    public function __construct(private readonly PaymentConditionService $paymentConditionService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
             TheliaEvents::MODULE_PAYMENT_IS_VALID => ['applyPaymentCondition', 128],
        ];
    }

    public function applyPaymentCondition(IsValidPaymentEvent $event)
    {
        $moduleQuery = ModuleQuery::create()
            ->filterByCode($event->getModule()->getCode());

        $customFamilyModuleIsActive = ModuleQuery::create()
            ->filterByCode('CustomerFamily')
            ->filterByActivate(1)
            ->findOne();

        if (null !== $customFamilyModuleIsActive) {
            $this->paymentConditionService->filterByCustomerFamilyCondition($moduleQuery);
        }


        $this->paymentConditionService->filterByAreaCondition($moduleQuery);
        $this->paymentConditionService->filterByDeliveryCondition($moduleQuery);
        $this->paymentConditionService->filterByCustomerCondition($moduleQuery);

        $module = $moduleQuery->findOne();


        if (empty($module)) {
            $event->setValidModule(false);
        }
    }

}