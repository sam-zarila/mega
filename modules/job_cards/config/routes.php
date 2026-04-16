<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['admin/job_cards']                            = 'job_cards/index';
$route['admin/job_cards/view/(:num)']                = 'job_cards/view/$1';
$route['admin/job_cards/create']                     = 'job_cards/create';
$route['admin/job_cards/create/(:num)']              = 'job_cards/create/$1';
$route['admin/job_cards/update_status']              = 'job_cards/update_status';
$route['admin/job_cards/update_notes']               = 'job_cards/update_notes';
$route['admin/job_cards/create_material_issue/(:num)'] = 'job_cards/create_material_issue/$1';
$route['admin/job_cards/get_material_issue/(:num)']  = 'job_cards/get_material_issue/$1';
$route['admin/job_cards/acknowledge_department']     = 'job_cards/acknowledge_department';
$route['admin/job_cards/get_timeline/(:num)']        = 'job_cards/get_timeline/$1';
$route['admin/job_cards/pdf/(:num)']                 = 'job_cards/pdf/$1';
$route['admin/job_cards/delete/(:num)']              = 'job_cards/delete/$1';
$route['admin/job_cards/table']                      = 'job_cards/table';
$route['admin/job_cards/search_inventory_for_issue'] = 'job_cards/search_inventory_for_issue';
$route['admin/job_cards/get_item_stock']             = 'job_cards/get_item_stock';
$route['admin/job_cards/settings']                   = 'job_cards/settings';
