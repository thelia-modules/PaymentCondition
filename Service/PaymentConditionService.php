<?php

namespace PaymentCondition\Service;

use CustomerFamily\Model\CustomerCustomerFamilyQuery;
use CustomerFamily\Model\CustomerFamilyQuery;
use PaymentCondition\Model\Map\PaymentAreaConditionTableMap;
use PaymentCondition\Model\Map\PaymentCustomerFamilyConditionTableMap;
use PaymentCondition\Model\Map\PaymentCustomerModuleConditionTableMap;
use PaymentCondition\Model\Map\PaymentDeliveryConditionTableMap;
use PaymentCondition\Model\PaymentAreaConditionQuery;
use PaymentCondition\Model\PaymentCustomerConditionQuery;
use PaymentCondition\Model\PaymentCustomerFamilyConditionQuery;
use PaymentCondition\Model\PaymentDeliveryConditionQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Domain\Cart\Service\CartRetriever;
use Thelia\Model\AddressQuery;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Map\CountryAreaTableMap;
use Thelia\Model\Map\ModuleTableMap;
use Thelia\Model\ModuleQuery;
use Thelia\Model\Order;
use Thelia\Tools\Version\Version;

class PaymentConditionService
{
    public function __construct(
        private RequestStack $requestStack,
        private CartRetriever $cartRetriever,
    )
    {
    }

    public function filterByDeliveryCondition(ModuleQuery $query)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return;
        }

        /** @var Order $order */
        $order = $request->getSession()->get('thelia.order');

        if (null === $order) {
            return;
        }

        $paymentDeliveryCondition = PaymentDeliveryConditionQuery::create()
            ->filterByIsValid(1)
            ->findOne();

        // If no condition valid set, don't filter
        if (null === $paymentDeliveryCondition) {
            return;
        }

        $deliveryModuleId = $order->getDeliveryModuleId();

        // After 2.6, the order is set later
        if (Version::test(ConfigQuery::read('thelia_version'), '2.6', false, ">=")) {
            $deliveryModuleId = $this->cartRetriever->fromSession()?->getDeliveryModuleId();
        }

        if (null === $deliveryModuleId) {
            return;
        }

        $join = new Join();
        $join->addExplicitCondition(
            ModuleTableMap::TABLE_NAME,
            'ID',
            null,
            PaymentDeliveryConditionTableMap::TABLE_NAME,
            'PAYMENT_MODULE_ID',
            null
        );

        $join->setJoinType(Criteria::JOIN);
        $query->addJoinObject($join, 'payment_delivery_condition_join')
            ->addJoinCondition('payment_delivery_condition_join', PaymentDeliveryConditionTableMap::COL_DELIVERY_MODULE_ID . ' = ' . $deliveryModuleId)
            ->addJoinCondition('payment_delivery_condition_join', PaymentDeliveryConditionTableMap::COL_IS_VALID . ' = 1');
    }

    public function filterByCustomerFamilyCondition(ModuleQuery $query)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return null;
        }

        $session = $request->getSession();

        $customer = $session->getCustomerUser();

        if (null === $customer) {
            return null;
        }

        $deliveryCustomerFamilyCondition = PaymentCustomerFamilyConditionQuery::create()
            ->filterByIsValid(1)
            ->findOne();


        // If no condition valid set, don't filter
        if (null === $deliveryCustomerFamilyCondition) {
            return;
        }

        $customerCustomerFamily = CustomerCustomerFamilyQuery::create()
            ->findOneByCustomerId($customer->getId());

        $customerFamily = $customerCustomerFamily?->getCustomerFamily() ??
            CustomerFamilyQuery::create()->filterByIsDefault(true)->findOne();


        // If no customer family set, disable all modules
        if (null === $customerFamily) {
            $query->filterById(-1);
            return;
        }

        $join = new Join();
        $join->addExplicitCondition(
            ModuleTableMap::TABLE_NAME,
            'ID',
            null,
            PaymentCustomerFamilyConditionTableMap::TABLE_NAME,
            'PAYMENT_MODULE_ID',
            null
        );

        $join->setJoinType(Criteria::JOIN);
        $query->addJoinObject($join, 'payment_customer_family_condition_join')
            ->addJoinCondition('payment_customer_family_condition_join', PaymentCustomerFamilyConditionTableMap::COL_CUSTOMER_FAMILY_ID.' = '.$customerFamily->getId())
            ->addJoinCondition('payment_customer_family_condition_join', PaymentCustomerFamilyConditionTableMap::COL_IS_VALID . ' = 1');
    }

    public function filterByAreaCondition(ModuleQuery $query)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return;
        }

        /** @var Order $order */
        $order = $request->getSession()->get('thelia.order');

        if (null === $order) {
            return;
        }

        $paymentAreaCondition = PaymentAreaConditionQuery::create()
            ->filterByIsValid(1)
            ->findOne();

        // If no condition valid set, don't filter
        if (null === $paymentAreaCondition) {
            return;
        }

        $chosenDeliveryAddressId = $order->getChoosenDeliveryAddress();

        if (null === $chosenDeliveryAddressId) {
            return;
        }

        $chosenDeliveryAddress = AddressQuery::create()->findOneById($chosenDeliveryAddressId);

        if (null === $chosenDeliveryAddress) {
            return;
        }

        $chosenCountryId = $chosenDeliveryAddress->getCountryId();

        $sqlQuery = ModuleTableMap::COL_ID . ' IN (SELECT '.PaymentAreaConditionTableMap::COL_PAYMENT_MODULE_ID.' FROM '.PaymentAreaConditionTableMap::TABLE_NAME
            .' WHERE '.PaymentAreaConditionTableMap::COL_IS_VALID.' = 1 AND area_id IN'
            .' (SELECT '.CountryAreaTableMap::COL_AREA_ID.' FROM '.CountryAreaTableMap::TABLE_NAME.' WHERE '.CountryAreaTableMap::COL_COUNTRY_ID.' = '.$chosenCountryId.')'
            .')';

        $query->add(ModuleTableMap::COL_ID, $sqlQuery, Criteria::CUSTOM);
    }

    public function filterByCustomerCondition(ModuleQuery $query)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        $customer = $session->getCustomerUser();

        if (null === $customer) {
            return;
        }

        $paymentCustomerCondition = PaymentCustomerConditionQuery::create()
            ->findOneByCustomerId($customer->getId());

        if (null === $paymentCustomerCondition || !$paymentCustomerCondition->getModuleRestrictionActive()) {
            return;
        }

        // Join customer module condition to know if the payment module is valid
        $paymentModuleConditionJoin = new Join();
        $paymentModuleConditionJoin->addExplicitCondition(
            ModuleTableMap::TABLE_NAME,
            'ID',
            null,
            PaymentCustomerModuleConditionTableMap::TABLE_NAME,
            'PAYMENT_MODULE_ID',
            null
        );

        $paymentModuleConditionJoin->setJoinType(Criteria::JOIN);
        $query->addJoinObject($paymentModuleConditionJoin, 'payment_customer_module_condition_join')
            ->addJoinCondition('payment_customer_module_condition_join', PaymentCustomerModuleConditionTableMap::COL_CUSTOMER_ID.' = '.$customer->getId())
            ->addJoinCondition('payment_customer_module_condition_join', PaymentCustomerModuleConditionTableMap::COL_IS_VALID.' = 1');
    }
}
