<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['admin/billing/finance_inbox']                    = 'billing/finance_inbox';
$route['admin/billing/create_from_dn/(:num)']            = 'billing/create_from_dn/$1';
$route['admin/billing/create_proforma']                  = 'billing/create_proforma';
$route['admin/billing/create_proforma/(:num)']           = 'billing/create_proforma/$1';
$route['admin/billing/convert_proforma/(:num)']          = 'billing/convert_proforma_to_invoice/$1';
$route['admin/billing/record_payment/(:num)']            = 'billing/record_payment/$1';
$route['admin/billing/approve_payment/(:num)']          = 'billing/approve_payment/$1';
$route['admin/billing/create_credit_note/(:num)']        = 'billing/create_credit_note/$1';
$route['admin/billing/approve_cn/(:num)']                = 'billing/approve_cn/$1';
$route['admin/billing/reject_cn/(:num)']                 = 'billing/reject_cn/$1';
$route['admin/billing/get_invoice_billing_tab/(:num)']   = 'billing/get_invoice_billing_tab/$1';
$route['admin/billing/get_balance_due']                  = 'billing/get_balance_due';
$route['admin/billing/settings']                         = 'billing/settings';
