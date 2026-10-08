<?php

namespace PaymentCondition\Controller;

use PaymentCondition\Model\PaymentAreaCondition;
use PaymentCondition\Model\PaymentAreaConditionQuery;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Model\AreaQuery;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;
use Thelia\Tools\TokenProvider;

class AreaConditionController extends BaseAdminController
{
    #[Route('/admin/module/paymentcondition/area', name: 'payment_condition_area_condition_view', methods: ['GET'])]
    public function viewAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'PaymentCondition', AccessManager::VIEW)) {
            return $response;
        }

        $areaPaymentConditionArray = [];

        $paymentModules = ModuleQuery::create()
            ->filterByType(BaseModule::PAYMENT_MODULE_TYPE)
            ->find();

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
            'areaPaymentCondition' => $areaPaymentConditionArray,
        ]);
    }

    #[Route('/admin/module/paymentcondition/area', name: 'payment_condition_area_condition_save', methods: ['POST'])]
    public function saveAction(TokenProvider $tokenProvider)
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'PaymentCondition', AccessManager::UPDATE)) {
            return $response;
        }

        $request = $this->requestStack->getCurrentRequest();

        $tokenProvider->checkToken((string) $request->request->get('_token'));

        try {
            $paymentId = $request->request->get('paymentId');
            $areaId = $request->request->get('areaId');
            $isValid = $request->request->get('isValid') === 'true' ? 1 : 0;

            $paymentArea = PaymentAreaConditionQuery::create()
                ->filterByPaymentModuleId($paymentId)
                ->filterByAreaId($areaId)
                ->findOneOrCreate();

            $paymentArea->setIsValid($isValid)
                ->save();
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }

        return new JsonResponse('Success');
    }
}
