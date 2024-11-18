<?php

namespace PaymentCondition\Controller;

use CustomerFamily\Model\CustomerFamily;
use CustomerFamily\Model\CustomerFamilyQuery;
use PaymentCondition\Model\PaymentCustomerFamilyCondition;
use PaymentCondition\Model\PaymentCustomerFamilyConditionQuery;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Model\LangQuery;
use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/paymentcondition/customerfamily", name="payment_condition_customer_family_condition_")
 */
class CustomerFamilyConditionController extends BaseAdminController
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * @Route("", name="view", methods="GET")
     */
    public function viewAction()
    {
        $customerFamilyPaymentsModules = [];
        $moduleCodes = [];
        $familyCodes = [];

        $paymentModules = ModuleQuery::create()
            ->findByCategory('payment');

        $customerFamilies = CustomerFamilyQuery::create()
            ->find();

        $currentLocale = $this->requestStack->getSession()->getAdminEditionLang()->getLocale();
        if (!$currentLocale){
            $currentLocale = LangQuery::create()->filterByByDefault(true)->findOne()->getLocale();
        }
        /** @var Module $paymentModule */
        foreach ($paymentModules as $paymentModule) {
            $moduleCodes[$paymentModule->getId()] = $paymentModule->getCode();

            /** @var CustomerFamily $customerFamily */
            foreach ($customerFamilies as $customerFamily) {
                $customerFamilyPaymentsModules[$customerFamily->getId()][$paymentModule->getId()] = 0;
                $familyCodes[$customerFamily->getId()] = $customerFamily->setLocale($currentLocale)->getTitle(). ' ('.$customerFamily->getCode().')';
            }
        }

        $customerFamilyPayments = PaymentCustomerFamilyConditionQuery::create()
            ->find();

        if (null !== $customerFamilyPayments) {
            /** @var PaymentCustomerFamilyCondition $customerFamilyPayment */
            foreach ($customerFamilyPayments as $customerFamilyPayment) {
                $customerFamilyPaymentsModules[$customerFamilyPayment->getCustomerFamilyId()][$customerFamilyPayment->getPaymentModuleId()] = $customerFamilyPayment->getIsValid();
            }
        }

        return $this->render('payment-condition/customer_family', [
            "module_codes" => $moduleCodes,
            "family_codes" => $familyCodes,
            "paymentFamilyCondition" =>$customerFamilyPaymentsModules
        ]);
    }

    /**
     * @Route("", name="save", methods="POST")
     */
    public function saveAction(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();

        try {
            $moduleId = $request->request->get("moduleId");
            $customerFamilyId = $request->request->get("customerFamilyId");
            $isValid = $request->request->get("isValid") == "true" ? 1 : 0;

            $paymentCustomerFamily = PaymentCustomerFamilyConditionQuery::create()
                ->filterByPaymentModuleId($moduleId)
                ->filterByCustomerFamilyId($customerFamilyId)
                ->findOneOrCreate();

            $paymentCustomerFamily->setIsValid($isValid)
                ->save();

        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
        return new JsonResponse("Success");
    }
}
