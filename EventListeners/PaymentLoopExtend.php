<?php

namespace PaymentCondition\EventListeners;

use PaymentCondition\Service\PaymentConditionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Loop\LoopExtendsBuildModelCriteriaEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\ModuleQuery;

class PaymentLoopExtend implements EventSubscriberInterface
{
    protected $request;

    public function __construct(
        private PaymentConditionService $paymentConditionService,
        RequestStack $requestStack
    )
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function paymentDeliveryCondition(LoopExtendsBuildModelCriteriaEvent $event)
    {
        /** @var ModuleQuery $moduleQuery */
        $moduleQuery = $event->getModelCriteria();
        $this->paymentConditionService->filterByDeliveryCondition($moduleQuery);
    }

    public function paymentCustomerFamilyCondition(LoopExtendsBuildModelCriteriaEvent $event)
    {
        /** @var ModuleQuery $moduleQuery */
        $moduleQuery = $event->getModelCriteria();
        $this->paymentConditionService->filterByCustomerFamilyCondition($moduleQuery);
    }

    public function paymentAreaCondition(LoopExtendsBuildModelCriteriaEvent $event)
    {
        /** @var ModuleQuery $moduleQuery */
        $moduleQuery = $event->getModelCriteria();
        $this->paymentConditionService->filterByAreaCondition($moduleQuery);
    }

    public function paymentCustomerCondition(LoopExtendsBuildModelCriteriaEvent $event)
    {
        /** @var ModuleQuery $moduleQuery */
        $moduleQuery = $event->getModelCriteria();
        $this->paymentConditionService->filterByCustomerCondition($moduleQuery);
    }

    public static function getSubscribedEvents(): array
    {
        $events = [];

        $events[TheliaEvents::getLoopExtendsEvent(TheliaEvents::LOOP_EXTENDS_BUILD_MODEL_CRITERIA, 'payment')][] = ['paymentDeliveryCondition', '64'];
        $events[TheliaEvents::getLoopExtendsEvent(TheliaEvents::LOOP_EXTENDS_BUILD_MODEL_CRITERIA, 'payment')][] = ['paymentAreaCondition', '62'];

        $customFamilyModuleIsActive = ModuleQuery::create()
            ->filterByCode('CustomerFamily')
            ->filterByActivate(1)
            ->findOne();

        if (null !== $customFamilyModuleIsActive) {
            $events[TheliaEvents::getLoopExtendsEvent(TheliaEvents::LOOP_EXTENDS_BUILD_MODEL_CRITERIA, 'payment')][] = ['paymentCustomerFamilyCondition', '63'];

        }

        $events[TheliaEvents::getLoopExtendsEvent(TheliaEvents::LOOP_EXTENDS_BUILD_MODEL_CRITERIA, 'payment')][] = ['paymentCustomerCondition', '60'];

        return $events;
    }
}
