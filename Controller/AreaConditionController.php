<?php

namespace PaymentCondition\Controller;

use PaymentCondition\Model\PaymentAreaCondition;
use PaymentCondition\Model\PaymentAreaConditionQuery;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Model\AreaQuery;
use Thelia\Model\ModuleQuery;
use Symfony\Component\Routing\Attribute\Route;

/**
 */
class AreaConditionController extends BaseAdminController
{
    /**
     * @Route("", name="view", methods="GET")
     */
    #[Route('/admin/module/paymentcondition/area', name: 'payment_condition_area_condition_')]
    public function viewAction()
    {
        $areaPaymentConditionArray = [];

        $paymentModules = ModuleQuery::create()
            ->findByCategory('payment');

        $shippingAreas = AreaQuery::create()->find();

        $paymentAreaConditions = PaymentAreaConditionQuery::create()
            ->find();

        if (null !== $paymentAreaConditions) {
            /** @var PaymentAreaCondition $paymentAreaCondition */
            foreach ($paymentAreaConditions as $paymentAreaCondition) {
                $areaPaymentConditionArray[$paymentAreaCondition->getPaymentModuleId()][$paymentAreaCondition->getAreaId()] = $paymentAreaCondition->getIsValid();
            }
        }

        return $this->render('payment-condition/shipping_area', [
            'paymentModules' => $paymentModules,
            'shippingAreas' => $shippingAreas,
            "areaPaymentCondition" => $areaPaymentConditionArray
        ]);
    }

    /**
     */
    #[Route(', name=', name: 'save', methods: ['POST'])]
    public function saveAction(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();

        try {
            $paymentId = $request->request->get("paymentId");
            $areaId = $request->request->get("areaId");
            $isValid = $request->request->get("isValid") == "true" ? 1 : 0;

            $paymentArea = PaymentAreaConditionQuery::create()
                ->filterByPaymentModuleId($paymentId)
                ->filterByAreaId($areaId)
                ->findOneOrCreate();

            $paymentArea->setIsValid($isValid)
                ->save();

        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
        return new JsonResponse("Success");
    }
}
