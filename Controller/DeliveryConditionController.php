<?php

namespace PaymentCondition\Controller;

use PaymentCondition\Model\PaymentDeliveryCondition;
use PaymentCondition\Model\PaymentDeliveryConditionQuery;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;

class DeliveryConditionController extends BaseAdminController
{
    #[Route('/admin/module/paymentcondition/delivery', name: 'payment_condition_delivery_condition_view', methods: ['GET'])]
    public function viewAction()
    {
        $paymentDeliveryConditionArray = [];

        $paymentModules = ModuleQuery::create()
            ->filterByType(BaseModule::PAYMENT_MODULE_TYPE)
            ->find();

        $deliveryModules = ModuleQuery::create()
            ->filterByType(BaseModule::DELIVERY_MODULE_TYPE)
            ->find();

        $paymentDeliveryConditions = PaymentDeliveryConditionQuery::create()
            ->find();

        if (null !== $paymentDeliveryConditions) {
            /** @var PaymentDeliveryCondition $paymentDeliveryCondition */
            foreach ($paymentDeliveryConditions as $paymentDeliveryCondition) {
                $paymentDeliveryConditionArray[$paymentDeliveryCondition->getPaymentModuleId()][$paymentDeliveryCondition->getDeliveryModuleId()] = $paymentDeliveryCondition->getIsValid();
            }
        }

        return $this->render('payment-condition/delivery', [
            'paymentModules' => $paymentModules,
            'deliveryModules' => $deliveryModules,
            'paymentDeliveryCondition' => $paymentDeliveryConditionArray,
        ]);
    }

    #[Route('/admin/module/paymentcondition/delivery', name: 'payment_condition_delivery_condition_save', methods: ['POST'])]
    public function saveAction()
    {
        $request = $this->requestStack->getCurrentRequest();

        try {
            $paymentId = $request->request->get('paymentId');
            $deliveryId = $request->request->get('deliveryId');
            $isValid = $request->request->get('isValid') === 'true' ? 1 : 0;

            $paymentDelivery = PaymentDeliveryConditionQuery::create()
                ->filterByPaymentModuleId($paymentId)
                ->filterByDeliveryModuleId($deliveryId)
                ->findOneOrCreate();

            $paymentDelivery->setIsValid($isValid)
                ->save();
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }

        return new JsonResponse('Success');
    }
}
