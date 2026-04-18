<?php

defined('BASEPATH') or exit('No direct script access allowed');

$lang['inv_mgr_module']          = 'Inventory Management';
$lang['inv_mgr_items']           = 'Stock Master';
$lang['inv_mgr_add_item']        = 'Add New Item';
$lang['inv_mgr_grn']             = 'Goods Receipt (GRN)';
$lang['inv_mgr_grn_add']         = 'Receive Stock';
$lang['inv_mgr_adjustments']     = 'Stock Adjustments';
$lang['inv_mgr_movements']       = 'Stock Movements';
$lang['inv_mgr_stock_report']    = 'Stock Valuation Report';
$lang['item_code']               = 'Item Code';
$lang['item_name']               = 'Item Name';
$lang['item_category']           = 'Category';
$lang['item_unit']               = 'Unit of Measure';
$lang['item_wac']                = 'WAC (Weighted Avg Cost)';
$lang['item_sell_price']         = 'Selling Price';
$lang['item_reorder_level']      = 'Reorder Level';
$lang['item_total_stock']        = 'Total Stock';
$lang['item_blantyre_stock']     = 'Blantyre Stock';
$lang['item_lilongwe_stock']     = 'Lilongwe Stock';
$lang['item_opening_qty']        = 'Opening Stock Quantity';
$lang['item_opening_warehouse']  = 'Opening Stock Warehouse';
$lang['grn_ref']                 = 'GRN Reference';
$lang['grn_supplier']            = 'Supplier';
$lang['grn_supplier_ref']        = 'Supplier Invoice/Reference';
$lang['grn_warehouse']           = 'Receiving Warehouse';
$lang['grn_date_received']       = 'Date Received';
$lang['grn_qty_receiving']       = 'Qty Receiving';
$lang['grn_unit_price']          = 'Unit Price (Cost)';
$lang['grn_line_total']          = 'Line Total';
$lang['grn_new_wac_preview']     = 'New WAC (Preview)';
$lang['grn_wac_updated']         = 'WAC recalculated for all received items';
$lang['issue_warehouse']         = 'Issue From Warehouse';
$lang['issue_qty_to_issue']      = 'Qty to Issue';
$lang['issue_wac_per_unit']      = 'WAC / Unit';
$lang['issue_total_cost']        = 'Total Issue Cost';
$lang['issue_from_quotation']    = 'Items from Approved Quotation';
$lang['issue_additional']        = 'Additional Items from Inventory';
$lang['issue_validate_stock']    = 'Validate Stock';
$lang['issue_confirm']           = 'Confirm Issue Materials';
$lang['issue_not_linked']        = 'Not linked to inventory';
$lang['issue_success']           = 'Materials issued successfully. Stock deducted.';
$lang['issue_insufficient']      = 'Insufficient stock for one or more items';
$lang['wac_formula']             = 'WAC = (Current Value + New Receipt Value) / (Current Qty + New Qty)';
$lang['low_stock_alert']         = 'items are at or below reorder level';
$lang['adj_write_off']           = 'Write-Off (Shortage / Damage)';
$lang['adj_write_up']            = 'Write-Up (Surplus / Found)';
$lang['adj_approval_required']   = 'Adjustment requires Store Manager approval';
$lang['adj_posted']              = 'Adjustment posted. Stock quantities and movements updated.';

// Legacy key (module slug) — keep for compatibility with Perfex / existing references
$lang['inventory_mgr']           = 'Inventory Management';
