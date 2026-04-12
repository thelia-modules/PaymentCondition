<?php

namespace PaymentCondition\Controller;

use Thelia\Controller\Admin\BaseAdminController;
use Symfony\Component\Routing\Attribute\Route;

/**
 */
class AdminController extends BaseAdminController
{
    /**
     * @Route("", name="view")
     */
    #[Route('/admin/module/PaymentCondition', name: 'payment_condition_admin_config_')]
    public function viewAction()
    {
        return $this->render('payment-condition/configuration');
    }
}
