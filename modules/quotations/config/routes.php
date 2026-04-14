<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['admin/quotations']                        = 'quotations/index';
$route['admin/quotations/create']                 = 'quotations/create';
$route['admin/quotations/create/(:num)']          = 'quotations/create/$1';
$route['admin/quotations/ajax_create']            = 'quotations/ajax_create';
$route['admin/quotations/save_builder']           = 'quotations/save_builder';
$route['admin/quotations/save_line_order']        = 'quotations/save_line_order';
$route['admin/quotations/edit/(:num)']            = 'quotations/edit/$1';
$route['admin/quotations/view/(:num)']            = 'quotations/view/$1';
$route['admin/quotations/pdf/(:num)']             = 'quotations/pdf/$1';
$route['admin/quotations/submit/(:num)']          = 'quotations/submit_for_approval/$1';
$route['admin/quotations/revise/(:num)']          = 'quotations/create_revision/$1';
$route['admin/quotations/send_email/(:num)']      = 'quotations/send_email/$1';
$route['admin/quotations/settings']               = 'quotations/settings';
$route['admin/quotations/save_settings']          = 'quotations/save_settings';
$route['admin/quotations/save_line']              = 'quotations/save_line';
$route['admin/quotations/delete_line']            = 'quotations/delete_line';
$route['admin/quotations/get_item/(:num)']        = 'quotations/get_inventory_item/$1';
$route['admin/quotations/search_inventory']       = 'quotations/search_inventory';
$route['admin/quotations/get_totals/(:num)']      = 'quotations/get_tab_totals/$1';
