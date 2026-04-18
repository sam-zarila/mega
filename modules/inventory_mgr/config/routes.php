<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Item master
$route['admin/inventory_mgr/items']                  = 'inventory_mgr/items';
$route['admin/inventory_mgr/add_item']               = 'inventory_mgr/add_item';
$route['admin/inventory_mgr/edit_item/(:num)']      = 'inventory_mgr/edit_item/$1';
$route['admin/inventory_mgr/view_item/(:num)']      = 'inventory_mgr/view_item/$1';
$route['admin/inventory_mgr/delete_item/(:num)']    = 'inventory_mgr/delete_item/$1';
$route['admin/inventory_mgr/search_items_ajax']     = 'inventory_mgr/search_items_ajax';
$route['admin/inventory_mgr/get_item_info']         = 'inventory_mgr/get_item_info';
// Item form helpers (AJAX)
$route['admin/inventory_mgr/ajax_next_item_code']   = 'inventory_mgr/ajax_next_item_code';
$route['admin/inventory_mgr/quick_add_category']    = 'inventory_mgr/quick_add_category';
$route['admin/inventory_mgr/quick_add_unit']        = 'inventory_mgr/quick_add_unit';
// GRN
$route['admin/inventory_mgr/grn']                   = 'inventory_mgr/grn';
$route['admin/inventory_mgr/add_grn']               = 'inventory_mgr/add_grn';
$route['admin/inventory_mgr/view_grn/(:num)']       = 'inventory_mgr/view_grn/$1';
// Material issue (job card integration)
$route['admin/inventory_mgr/issue_form/(:num)']     = 'inventory_mgr/issue_form/$1';
$route['admin/inventory_mgr/process_issue/(:num)']  = 'inventory_mgr/process_issue/$1';
$route['admin/inventory_mgr/confirm_issue']         = 'inventory_mgr/confirm_issue';
// Adjustments
$route['admin/inventory_mgr/adjustments']           = 'inventory_mgr/adjustments';
$route['admin/inventory_mgr/add_adjustment']        = 'inventory_mgr/add_adjustment';
$route['admin/inventory_mgr/view_adjustment/(:num)'] = 'inventory_mgr/view_adjustment/$1';
$route['admin/inventory_mgr/approve_adjustment_action/(:num)'] = 'inventory_mgr/approve_adjustment_action/$1';
$route['admin/inventory_mgr/post_adjustment_action/(:num)']     = 'inventory_mgr/post_adjustment_action/$1';
$route['admin/inventory_mgr/approve_adj/(:num)']               = 'inventory_mgr/approve_adjustment_action/$1';
$route['admin/inventory_mgr/post_adj/(:num)']                  = 'inventory_mgr/post_adjustment_action/$1';
// Movements + reports
$route['admin/inventory_mgr/movements']             = 'inventory_mgr/movements';
$route['admin/inventory_mgr/stock_report']          = 'inventory_mgr/stock_report';
