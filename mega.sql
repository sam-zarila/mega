-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2026 at 03:25 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mega`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblaccount_planning`
--

CREATE TABLE `tblaccount_planning` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `vision` varchar(255) DEFAULT NULL,
  `mission` varchar(255) DEFAULT NULL,
  `lead_generation` varchar(45) DEFAULT NULL,
  `current_service_know_pmax` varchar(45) DEFAULT NULL,
  `current_service_facebook` varchar(45) DEFAULT NULL,
  `current_service_sem` varchar(45) DEFAULT NULL,
  `objectives` varchar(255) DEFAULT NULL,
  `threat` varchar(255) DEFAULT NULL,
  `opportunity` varchar(255) DEFAULT NULL,
  `criteria_to_success` varchar(255) DEFAULT NULL,
  `constraints` varchar(255) DEFAULT NULL,
  `data_tree` longtext DEFAULT NULL,
  `latest_update` date DEFAULT NULL,
  `new_update` date DEFAULT NULL,
  `product` varchar(255) DEFAULT NULL,
  `sale_channel_online` varchar(255) DEFAULT NULL,
  `sale_channel_offline` varchar(255) DEFAULT NULL,
  `revenue_next_year` varchar(255) DEFAULT NULL,
  `wallet_share` varchar(255) DEFAULT NULL,
  `client_status` varchar(255) DEFAULT NULL,
  `bcg_model` varchar(255) DEFAULT NULL,
  `margin` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblaccount_planning_current_service`
--

CREATE TABLE `tblaccount_planning_current_service` (
  `id` int(11) NOT NULL,
  `account_planning_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `potential` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblaccount_planning_financial`
--

CREATE TABLE `tblaccount_planning_financial` (
  `id` int(11) NOT NULL,
  `account_planning_id` int(11) NOT NULL,
  `year` varchar(45) DEFAULT NULL,
  `revenue` varchar(255) DEFAULT NULL,
  `sales_spent` varchar(255) DEFAULT NULL,
  `traffic` varchar(255) DEFAULT NULL,
  `loss` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblaccount_planning_items`
--

CREATE TABLE `tblaccount_planning_items` (
  `id` int(11) NOT NULL,
  `account_planning_id` int(11) NOT NULL,
  `objective_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `datecreated` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblaccount_planning_marketing_activities`
--

CREATE TABLE `tblaccount_planning_marketing_activities` (
  `id` int(11) NOT NULL,
  `account_planning_id` int(11) NOT NULL,
  `item` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblaccount_planning_objective`
--

CREATE TABLE `tblaccount_planning_objective` (
  `id` int(11) NOT NULL,
  `account_planning_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `datecreated` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblaccount_planning_service_ability_offering`
--

CREATE TABLE `tblaccount_planning_service_ability_offering` (
  `id` int(11) NOT NULL,
  `account_planning_id` int(11) NOT NULL,
  `service` varchar(255) DEFAULT NULL,
  `potential` varchar(255) DEFAULT NULL,
  `scale` varchar(255) DEFAULT NULL,
  `convert` varchar(255) DEFAULT NULL,
  `prioritization` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblaccount_planning_task`
--

CREATE TABLE `tblaccount_planning_task` (
  `id` int(11) NOT NULL,
  `items_id` int(11) NOT NULL,
  `account_planning_id` int(11) DEFAULT NULL,
  `action_needed` varchar(255) NOT NULL,
  `prioritization` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `objective` varchar(255) DEFAULT NULL,
  `item` varchar(255) DEFAULT NULL,
  `convert_to_task` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblaccount_planning_team`
--

CREATE TABLE `tblaccount_planning_team` (
  `id` int(11) NOT NULL,
  `account_planning_id` int(11) NOT NULL,
  `rel_id` varchar(45) NOT NULL,
  `rel_type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_accounts`
--

CREATE TABLE `tblacc_accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `number` varchar(45) DEFAULT NULL,
  `parent_account` int(11) DEFAULT NULL,
  `account_type_id` int(11) NOT NULL,
  `account_detail_type_id` int(11) NOT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  `balance_as_of` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `default_account` int(11) NOT NULL DEFAULT 0,
  `active` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblacc_accounts`
--

INSERT INTO `tblacc_accounts` (`id`, `name`, `key_name`, `number`, `parent_account`, `account_type_id`, `account_detail_type_id`, `balance`, `balance_as_of`, `description`, `default_account`, `active`) VALUES
(1, '', 'acc_accounts_receivable', NULL, NULL, 1, 1, NULL, NULL, NULL, 1, 1),
(2, '', 'acc_accrued_holiday_payable', NULL, NULL, 9, 61, NULL, NULL, NULL, 1, 1),
(3, '', 'acc_accrued_liabilities', NULL, NULL, 8, 44, NULL, NULL, NULL, 1, 1),
(4, '', 'acc_accrued_non_current_liabilities', NULL, NULL, 9, 62, NULL, NULL, NULL, 1, 1),
(5, '', 'acc_accumulated_depreciation_on_property_plant_and_equipment', NULL, NULL, 4, 22, NULL, NULL, NULL, 1, 1),
(6, '', 'acc_allowance_for_bad_debts', NULL, NULL, 2, 2, NULL, NULL, NULL, 1, 1),
(7, '', 'acc_amortisation_expense', NULL, NULL, 14, 106, NULL, NULL, NULL, 1, 1),
(8, '', 'acc_assets_held_for_sale', NULL, NULL, 5, 32, NULL, NULL, NULL, 1, 1),
(9, '', 'acc_available_for_sale_assets_short_term', NULL, NULL, 2, 3, NULL, NULL, NULL, 1, 1),
(10, '', 'acc_bad_debts', NULL, NULL, 14, 108, NULL, NULL, NULL, 1, 1),
(11, '', 'acc_bank_charges', NULL, NULL, 14, 109, NULL, NULL, NULL, 1, 1),
(12, '', 'acc_billable_expense_income', NULL, NULL, 11, 89, NULL, NULL, NULL, 1, 1),
(13, '', 'acc_cash_and_cash_equivalents', NULL, NULL, 3, 15, NULL, NULL, NULL, 1, 1),
(14, '', 'acc_change_in_inventory_cos', NULL, NULL, 13, 100, NULL, NULL, NULL, 1, 1),
(15, '', 'acc_commissions_and_fees', NULL, NULL, 14, 111, NULL, NULL, NULL, 1, 1),
(16, '', 'acc_cost_of_sales', NULL, NULL, 13, 104, NULL, NULL, NULL, 1, 1),
(17, '', 'acc_deferred_tax_assets', NULL, NULL, 5, 33, NULL, NULL, NULL, 1, 1),
(18, '', 'acc_direct_labour_cos', NULL, NULL, 13, 100, NULL, NULL, NULL, 1, 1),
(19, '', 'acc_discounts_given_cos', NULL, NULL, 13, 100, NULL, NULL, NULL, 1, 1),
(20, '', 'acc_dividend_disbursed', NULL, NULL, 10, 69, NULL, NULL, NULL, 1, 1),
(21, '', 'acc_dividend_income', NULL, NULL, 12, 92, NULL, NULL, NULL, 1, 1),
(22, '', 'acc_dividends_payable', NULL, NULL, 8, 48, NULL, NULL, NULL, 1, 1),
(23, '', 'acc_dues_and_subscriptions', NULL, NULL, 14, 113, NULL, NULL, NULL, 1, 1),
(24, '', 'acc_equipment_rental', NULL, NULL, 14, 114, NULL, NULL, NULL, 1, 1),
(25, '', 'acc_equity_in_earnings_of_subsidiaries', NULL, NULL, 10, 70, NULL, NULL, NULL, 1, 1),
(26, '', 'acc_freight_and_delivery_cos', NULL, NULL, 13, 100, NULL, NULL, NULL, 1, 1),
(27, '', 'acc_goodwill', NULL, NULL, 5, 34, NULL, NULL, NULL, 1, 1),
(28, '', 'acc_income_tax_expense', NULL, NULL, 14, 116, NULL, NULL, NULL, 1, 1),
(29, '', 'acc_income_tax_payable', NULL, NULL, 8, 50, NULL, NULL, NULL, 1, 1),
(30, '', 'acc_insurance_disability', NULL, NULL, 14, 117, NULL, NULL, NULL, 1, 1),
(31, '', 'acc_insurance_general', NULL, NULL, 14, 117, NULL, NULL, NULL, 1, 1),
(32, '', 'acc_insurance_liability', NULL, NULL, 14, 117, NULL, NULL, NULL, 1, 1),
(33, '', 'acc_intangibles', NULL, NULL, 5, 35, NULL, NULL, NULL, 1, 1),
(34, '', 'acc_interest_expense', NULL, NULL, 14, 118, NULL, NULL, NULL, 1, 1),
(35, '', 'acc_interest_income', NULL, NULL, 12, 93, NULL, NULL, NULL, 1, 1),
(36, '', 'acc_inventory', NULL, NULL, 2, 5, NULL, NULL, NULL, 1, 1),
(37, '', 'acc_inventory_asset', NULL, NULL, 2, 5, NULL, NULL, NULL, 1, 1),
(38, '', 'acc_legal_and_professional_fees', NULL, NULL, 14, 119, NULL, NULL, NULL, 1, 1),
(39, '', 'acc_liabilities_related_to_assets_held_for_sale', NULL, NULL, 9, 63, NULL, NULL, NULL, 1, 1),
(40, '', 'acc_long_term_debt', NULL, NULL, 9, 64, NULL, NULL, NULL, 1, 1),
(41, '', 'acc_long_term_investments', NULL, NULL, 5, 38, NULL, NULL, NULL, 1, 1),
(42, '', 'acc_loss_on_discontinued_operations_net_of_tax', NULL, NULL, 14, 120, NULL, NULL, NULL, 1, 1),
(43, '', 'acc_loss_on_disposal_of_assets', NULL, NULL, 12, 94, NULL, NULL, NULL, 1, 1),
(44, '', 'acc_management_compensation', NULL, NULL, 14, 121, NULL, NULL, NULL, 1, 1),
(45, '', 'acc_materials_cos', NULL, NULL, 13, 100, NULL, NULL, NULL, 1, 1),
(46, '', 'acc_meals_and_entertainment', NULL, NULL, 14, 122, NULL, NULL, NULL, 1, 1),
(47, '', 'acc_office_expenses', NULL, NULL, 14, 123, NULL, NULL, NULL, 1, 1),
(48, '', 'acc_other_cos', NULL, NULL, 13, 100, NULL, NULL, NULL, 1, 1),
(49, '', 'acc_other_comprehensive_income', NULL, NULL, 10, 73, NULL, NULL, NULL, 1, 1),
(50, '', 'acc_other_general_and_administrative_expenses', NULL, NULL, 14, 123, NULL, NULL, NULL, 1, 1),
(51, '', 'acc_other_operating_income_expenses', NULL, NULL, 12, 97, NULL, NULL, NULL, 1, 1),
(52, '', 'acc_other_selling_expenses', NULL, NULL, 14, 125, NULL, NULL, NULL, 1, 1),
(53, '', 'acc_other_type_of_expenses_advertising_expenses', NULL, NULL, 14, 105, NULL, NULL, NULL, 1, 1),
(54, '', 'acc_overhead_cos', NULL, NULL, 13, 100, NULL, NULL, NULL, 1, 1),
(55, '', 'acc_payroll_clearing', NULL, NULL, 8, 55, NULL, NULL, NULL, 1, 1),
(56, '', 'acc_payroll_expenses', NULL, NULL, 14, 126, NULL, NULL, NULL, 1, 1),
(57, '', 'acc_payroll_liabilities', NULL, NULL, 8, 56, NULL, NULL, NULL, 1, 1),
(58, '', 'acc_prepaid_expenses', NULL, NULL, 2, 11, NULL, NULL, NULL, 1, 1),
(59, '', 'acc_property_plant_and_equipment', NULL, NULL, 4, 26, NULL, NULL, NULL, 1, 1),
(60, '', 'acc_purchases', NULL, NULL, 14, 130, NULL, NULL, NULL, 1, 1),
(61, '', 'acc_reconciliation_discrepancies', NULL, NULL, 15, 139, NULL, NULL, NULL, 1, 1),
(62, '', 'acc_rent_or_lease_payments', NULL, NULL, 14, 127, NULL, NULL, NULL, 1, 1),
(63, '', 'acc_repair_and_maintenance', NULL, NULL, 14, 128, NULL, NULL, NULL, 1, 1),
(64, '', 'acc_retained_earnings', NULL, NULL, 10, 80, NULL, NULL, NULL, 1, 1),
(65, '', 'acc_revenue_general', NULL, NULL, 11, 86, NULL, NULL, NULL, 1, 1),
(66, '', 'acc_sales', NULL, NULL, 11, 89, NULL, NULL, NULL, 1, 1),
(67, '', 'acc_sales_retail', NULL, NULL, 11, 87, NULL, NULL, NULL, 1, 1),
(68, '', 'acc_sales_wholesale', NULL, NULL, 11, 88, NULL, NULL, NULL, 1, 1),
(69, '', 'acc_sales_of_product_income', NULL, NULL, 11, 89, NULL, NULL, NULL, 1, 1),
(70, '', 'acc_share_capital', NULL, NULL, 10, 81, NULL, NULL, NULL, 1, 1),
(71, '', 'acc_shipping_and_delivery_expense', NULL, NULL, 14, 129, NULL, NULL, NULL, 1, 1),
(72, '', 'acc_short_term_debit', NULL, NULL, 8, 54, NULL, NULL, NULL, 1, 1),
(73, '', 'acc_stationery_and_printing', NULL, NULL, 14, 123, NULL, NULL, NULL, 1, 1),
(74, '', 'acc_subcontractors_cos', NULL, NULL, 13, 100, NULL, NULL, NULL, 1, 1),
(75, '', 'acc_supplies', NULL, NULL, 14, 130, NULL, NULL, NULL, 1, 1),
(76, '', 'acc_travel_expenses_general_and_admin_expenses', NULL, NULL, 14, 132, NULL, NULL, NULL, 1, 1),
(77, '', 'acc_travel_expenses_selling_expense', NULL, NULL, 14, 133, NULL, NULL, NULL, 1, 1),
(78, '', 'acc_unapplied_cash_payment_income', NULL, NULL, 11, 91, NULL, NULL, NULL, 1, 1),
(79, '', 'acc_uncategorised_asset', NULL, NULL, 2, 10, NULL, NULL, NULL, 1, 1),
(80, '', 'acc_uncategorised_expense', NULL, NULL, 14, 124, NULL, NULL, NULL, 1, 1),
(81, '', 'acc_uncategorised_income', NULL, NULL, 11, 89, NULL, NULL, NULL, 1, 1),
(82, '', 'acc_undeposited_funds', NULL, NULL, 2, 13, NULL, NULL, NULL, 1, 1),
(83, '', 'acc_unrealised_loss_on_securities_net_of_tax', NULL, NULL, 12, 99, NULL, NULL, NULL, 1, 1),
(84, '', 'acc_utilities', NULL, NULL, 14, 135, NULL, NULL, NULL, 1, 1),
(85, '', 'acc_wage_expenses', NULL, NULL, 14, 126, NULL, NULL, NULL, 1, 1),
(86, '', 'acc_credit_card', NULL, NULL, 7, 43, NULL, NULL, NULL, 1, 1),
(87, '', 'acc_accounts_payable', NULL, NULL, 6, 42, NULL, NULL, NULL, 1, 1),
(88, '', 'acc_opening_balance_equity', NULL, NULL, 10, 71, NULL, NULL, NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_account_history`
--

CREATE TABLE `tblacc_account_history` (
  `id` int(11) NOT NULL,
  `account` int(11) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `rel_id` int(11) DEFAULT NULL,
  `rel_type` varchar(45) DEFAULT NULL,
  `datecreated` datetime DEFAULT NULL,
  `addedfrom` int(11) DEFAULT NULL,
  `customer` int(11) DEFAULT NULL,
  `reconcile` int(11) NOT NULL DEFAULT 0,
  `split` int(11) NOT NULL DEFAULT 0,
  `item` int(11) DEFAULT NULL,
  `paid` int(1) NOT NULL DEFAULT 0,
  `date` date DEFAULT NULL,
  `tax` int(11) DEFAULT NULL,
  `payslip_type` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_account_type_details`
--

CREATE TABLE `tblacc_account_type_details` (
  `id` int(11) NOT NULL,
  `account_type_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `statement_of_cash_flows` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_banking_rules`
--

CREATE TABLE `tblacc_banking_rules` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `transaction` varchar(45) DEFAULT NULL,
  `following` varchar(45) DEFAULT NULL,
  `then` varchar(45) DEFAULT NULL,
  `payment_account` int(11) DEFAULT NULL,
  `deposit_to` int(11) DEFAULT NULL,
  `auto_add` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_banking_rule_details`
--

CREATE TABLE `tblacc_banking_rule_details` (
  `id` int(11) NOT NULL,
  `rule_id` int(11) NOT NULL,
  `type` varchar(45) DEFAULT NULL,
  `subtype` varchar(45) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  `subtype_amount` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_budgets`
--

CREATE TABLE `tblacc_budgets` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `data_source` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_budget_details`
--

CREATE TABLE `tblacc_budget_details` (
  `id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `account` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_expense_category_mappings`
--

CREATE TABLE `tblacc_expense_category_mappings` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `payment_account` int(11) NOT NULL DEFAULT 0,
  `deposit_to` int(11) NOT NULL DEFAULT 0,
  `preferred_payment_method` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_item_automatics`
--

CREATE TABLE `tblacc_item_automatics` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `inventory_asset_account` int(11) NOT NULL DEFAULT 0,
  `income_account` int(11) NOT NULL DEFAULT 0,
  `expense_account` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_journal_entries`
--

CREATE TABLE `tblacc_journal_entries` (
  `id` int(11) NOT NULL,
  `number` varchar(45) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `journal_date` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `datecreated` datetime DEFAULT NULL,
  `addedfrom` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_payment_mode_mappings`
--

CREATE TABLE `tblacc_payment_mode_mappings` (
  `id` int(11) NOT NULL,
  `payment_mode_id` int(11) NOT NULL,
  `payment_account` int(11) NOT NULL DEFAULT 0,
  `deposit_to` int(11) NOT NULL DEFAULT 0,
  `expense_payment_account` int(11) NOT NULL DEFAULT 0,
  `expense_deposit_to` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_reconciles`
--

CREATE TABLE `tblacc_reconciles` (
  `id` int(11) NOT NULL,
  `account` int(11) NOT NULL,
  `beginning_balance` decimal(15,2) NOT NULL,
  `ending_balance` decimal(15,2) NOT NULL,
  `ending_date` date NOT NULL,
  `expense_date` date DEFAULT NULL,
  `service_charge` decimal(15,2) DEFAULT NULL,
  `expense_account` int(11) DEFAULT NULL,
  `income_date` date DEFAULT NULL,
  `interest_earned` decimal(15,2) DEFAULT NULL,
  `income_account` int(11) DEFAULT NULL,
  `finish` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_tax_mappings`
--

CREATE TABLE `tblacc_tax_mappings` (
  `id` int(11) NOT NULL,
  `tax_id` int(11) NOT NULL,
  `payment_account` int(11) NOT NULL DEFAULT 0,
  `deposit_to` int(11) NOT NULL DEFAULT 0,
  `expense_payment_account` int(11) NOT NULL DEFAULT 0,
  `expense_deposit_to` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_transaction_bankings`
--

CREATE TABLE `tblacc_transaction_bankings` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `withdrawals` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deposits` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payee` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `datecreated` datetime DEFAULT NULL,
  `addedfrom` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblacc_transfers`
--

CREATE TABLE `tblacc_transfers` (
  `id` int(11) NOT NULL,
  `transfer_funds_from` int(11) NOT NULL,
  `transfer_funds_to` int(11) NOT NULL,
  `transfer_amount` decimal(15,2) DEFAULT NULL,
  `date` varchar(45) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `datecreated` datetime DEFAULT NULL,
  `addedfrom` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblactivity_log`
--

CREATE TABLE `tblactivity_log` (
  `id` int(11) NOT NULL,
  `description` longtext NOT NULL,
  `date` datetime NOT NULL,
  `staffid` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblactivity_log`
--

INSERT INTO `tblactivity_log` (`id`, `description`, `date`, `staffid`) VALUES
(1, 'User Successfully Logged In [User Id: 1, Is Staff Member: Yes, IP: ::1]', '2026-04-12 11:23:46', 'Mega Signs Ltd'),
(2, 'New Role Added [ID: 2.General Manager ]', '2026-04-12 14:09:24', 'Mega Signs Ltd'),
(3, 'New Role Added [ID: 3.System Administrator]', '2026-04-12 14:09:52', 'Mega Signs Ltd'),
(4, 'New Role Added [ID: 4.Finance Manager]', '2026-04-12 14:10:13', 'Mega Signs Ltd'),
(5, 'New Role Added [ID: 5.Sales Manager]', '2026-04-12 14:10:29', 'Mega Signs Ltd'),
(6, 'New Role Added [ID: 6.Sales Executive]', '2026-04-12 14:10:48', 'Mega Signs Ltd'),
(7, 'New Role Added [ID: 7.Sales Representative]', '2026-04-12 14:11:05', 'Mega Signs Ltd'),
(8, 'New Role Added [ID: 8.Store Manager]', '2026-04-12 14:11:21', 'Mega Signs Ltd'),
(9, 'New Role Added [ID: 9.Storekeeper/Stores Clerk, Studio/Production]', '2026-04-12 14:11:43', 'Mega Signs Ltd'),
(10, 'New Role Added [ID: 10.Receptionist/Front Desk]', '2026-04-12 14:12:01', 'Mega Signs Ltd'),
(11, 'New Role Added [ID: 11.Purchasing Officer]', '2026-04-12 14:12:22', 'Mega Signs Ltd'),
(12, 'Non Existing User Tried to Login [Email: info@express.mw, Is Staff Member: No, IP: ::1]', '2026-04-12 14:49:06', 'Mega Signs Ltd'),
(13, 'User Successfully Logged In [User Id: 1, Is Staff Member: Yes, IP: ::1]', '2026-04-13 09:06:43', 'Mega Signs Ltd'),
(14, 'Staff Member Updated [ID: 1, Mega Signs Ltd]', '2026-04-13 09:25:09', 'Mega Signs Ltd'),
(15, 'Staff Member Updated [ID: 1, Mega Signs Ltd]', '2026-04-13 09:25:37', 'Mega Signs Ltd'),
(16, 'Staff Member Updated [ID: 1, Mega Signs Ltd]', '2026-04-13 09:27:21', 'Mega Signs Ltd'),
(17, 'Staff Member Updated [ID: 1, Mega Signs Ltd]', '2026-04-13 09:27:30', 'Mega Signs Ltd'),
(18, 'Staff Member Updated [ID: 1, Mega Signs Ltd]', '2026-04-13 09:28:47', 'Mega Signs Ltd'),
(19, 'Role Updated [ID: 2, Name: General Manager ]', '2026-04-13 09:32:16', 'Mega Signs Ltd'),
(20, 'Staff Member Updated [ID: 1, Mega Signs Ltd]', '2026-04-13 09:48:15', 'Mega Signs Ltd'),
(21, 'Staff Member Updated [ID: 1, Mega Signs Ltd]', '2026-04-13 09:48:30', 'Mega Signs Ltd'),
(22, 'Tried to access page where don\'t have permission [approvals]', '2026-04-13 09:50:56', 'Mega Signs Ltd'),
(23, 'New Staff Member Added [ID: 2, sam zarila]', '2026-04-13 10:13:40', 'Mega Signs Ltd'),
(24, 'New Client Created [ID: 1, From Staff: 1]', '2026-04-13 10:21:41', 'Mega Signs Ltd'),
(25, 'New Proposal Created [ID: 1]', '2026-04-13 10:22:51', 'Mega Signs Ltd'),
(26, 'Proposal Updated [ID:1]', '2026-04-13 10:25:48', 'Mega Signs Ltd'),
(27, 'User Successfully Logged In [User Id: 1, Is Staff Member: Yes, IP: ::1]', '2026-04-13 10:27:03', 'Mega Signs Ltd'),
(28, 'New Staff Member Added [ID: 3, John  Doe]', '2026-04-13 10:39:43', 'Mega Signs Ltd'),
(29, 'Staff Member Updated [ID: 3, John  Doe]', '2026-04-13 10:45:37', 'Mega Signs Ltd'),
(30, 'Non Existing User Tried to Login [Email: john@gm.mw, Is Staff Member: No, IP: ::1]', '2026-04-13 10:47:15', 'Mega Signs Ltd'),
(31, 'User Successfully Logged In [User Id: 3, Is Staff Member: Yes, IP: ::1]', '2026-04-13 10:48:02', 'John  Doe'),
(32, 'New Proposal Created [ID: 2]', '2026-04-13 11:19:59', 'John  Doe'),
(33, 'User Successfully Logged In [User Id: 1, Is Staff Member: Yes, IP: ::1]', '2026-04-13 11:42:48', 'Mega Signs Ltd'),
(34, 'Proposal Updated [ID:2]', '2026-04-13 12:27:22', 'Mega Signs Ltd'),
(35, 'User Successfully Logged In [User Id: 3, Is Staff Member: Yes, IP: ::1]', '2026-04-13 12:28:26', 'John  Doe'),
(36, 'User Successfully Logged In [User Id: 2, Is Staff Member: Yes, IP: ::1]', '2026-04-13 12:29:52', 'sam zarila'),
(37, 'User Successfully Logged In [User Id: 1, Is Staff Member: Yes, IP: ::1]', '2026-04-13 12:30:34', 'Mega Signs Ltd'),
(38, 'New Proposal Created [ID: 3]', '2026-04-13 12:34:11', 'Mega Signs Ltd'),
(39, 'New Proposal Created [ID: 4]', '2026-04-13 12:35:35', 'Mega Signs Ltd'),
(40, 'User Successfully Logged In [User Id: 2, Is Staff Member: Yes, IP: ::1]', '2026-04-13 12:36:02', 'sam zarila'),
(41, 'User Successfully Logged In [User Id: 1, Is Staff Member: Yes, IP: ::1]', '2026-04-13 12:38:22', 'Mega Signs Ltd'),
(42, 'New Tax Added [ID: 1, nccic tax]', '2026-04-13 13:00:40', 'sam zarila'),
(43, 'New Tax Added [ID: 2, Vat]', '2026-04-13 13:00:54', 'sam zarila'),
(44, 'New Tax Added [ID: 3, Levy]', '2026-04-13 13:01:08', 'sam zarila'),
(45, 'New Currency Added [ID: K]', '2026-04-13 13:01:25', 'sam zarila'),
(46, 'Currency Deleted [3]', '2026-04-13 13:01:43', 'sam zarila'),
(47, 'Currency Updated [K]', '2026-04-13 13:01:56', 'sam zarila'),
(48, 'New Staff Member Added [ID: 4, Alex Sales Executive Mayo]', '2026-04-13 13:03:39', 'sam zarila'),
(49, 'User Successfully Logged In [User Id: 4, Is Staff Member: Yes, IP: ::1]', '2026-04-13 13:04:55', 'Alex Sales Executive Mayo'),
(50, 'Tried to access page where don\'t have permission [customers]', '2026-04-13 13:05:20', 'Alex Sales Executive Mayo'),
(51, 'Tried to access page where don\'t have permission [customers]', '2026-04-13 13:05:25', 'Alex Sales Executive Mayo'),
(52, 'Tried to access page where don\'t have permission [customers]', '2026-04-13 13:05:27', 'Alex Sales Executive Mayo'),
(53, 'Tried to access page where don\'t have permission [customers]', '2026-04-13 13:05:27', 'Alex Sales Executive Mayo'),
(54, 'Tried to access page where don\'t have permission [customers]', '2026-04-13 13:05:28', 'Alex Sales Executive Mayo'),
(55, 'Role Updated [ID: 5, Name: Sales Manager]', '2026-04-13 13:11:31', 'sam zarila'),
(56, 'Role Updated [ID: 5, Name: Sales Manager]', '2026-04-13 13:11:52', 'sam zarila'),
(57, 'Staff Member Updated [ID: 4, Alex Sales Executive Mayo]', '2026-04-13 13:12:49', 'sam zarila'),
(58, 'Role Updated [ID: 6, Name: Sales Executive]', '2026-04-13 13:16:22', 'sam zarila'),
(59, 'New Proposal Created [ID: 5]', '2026-04-13 13:18:51', 'Alex Sales Executive Mayo'),
(60, 'Proposal Updated [ID:5]', '2026-04-13 13:24:34', 'Alex Sales Executive Mayo'),
(61, 'New Proposal Created [ID: 6]', '2026-04-13 13:25:55', 'Alex Sales Executive Mayo');

-- --------------------------------------------------------

--
-- Table structure for table `tblannouncements`
--

CREATE TABLE `tblannouncements` (
  `announcementid` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `message` mediumtext DEFAULT NULL,
  `showtousers` int(11) NOT NULL,
  `showtostaff` int(11) NOT NULL,
  `showname` int(11) NOT NULL,
  `dateadded` datetime NOT NULL,
  `userid` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblclients`
--

CREATE TABLE `tblclients` (
  `userid` int(11) NOT NULL,
  `company` varchar(191) DEFAULT NULL,
  `is_preffered` int(11) DEFAULT 0,
  `vat` varchar(50) DEFAULT NULL,
  `phonenumber` varchar(30) DEFAULT NULL,
  `country` int(11) NOT NULL DEFAULT 0,
  `city` varchar(100) DEFAULT NULL,
  `zip` varchar(15) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `leadid` int(11) DEFAULT NULL,
  `billing_street` varchar(200) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_zip` varchar(100) DEFAULT NULL,
  `billing_country` int(11) DEFAULT 0,
  `shipping_street` varchar(200) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_zip` varchar(100) DEFAULT NULL,
  `shipping_country` int(11) DEFAULT 0,
  `longitude` varchar(191) DEFAULT NULL,
  `latitude` varchar(191) DEFAULT NULL,
  `default_language` varchar(40) DEFAULT NULL,
  `default_currency` int(11) NOT NULL DEFAULT 0,
  `show_primary_contact` int(11) NOT NULL DEFAULT 0,
  `is_supplier` tinyint(1) NOT NULL,
  `stripe_id` varchar(40) DEFAULT NULL,
  `registration_confirmed` int(11) NOT NULL DEFAULT 1,
  `addedfrom` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblclients`
--

INSERT INTO `tblclients` (`userid`, `company`, `is_preffered`, `vat`, `phonenumber`, `country`, `city`, `zip`, `state`, `address`, `website`, `datecreated`, `active`, `leadid`, `billing_street`, `billing_city`, `billing_state`, `billing_zip`, `billing_country`, `shipping_street`, `shipping_city`, `shipping_state`, `shipping_zip`, `shipping_country`, `longitude`, `latitude`, `default_language`, `default_currency`, `show_primary_contact`, `is_supplier`, `stripe_id`, `registration_confirmed`, `addedfrom`) VALUES
(1, 'Test Customer', 0, '12345', '0880842416', 0, '', '', '', '', '', '2026-04-13 10:21:41', 1, NULL, '', '', '', '', 0, '', '', '', '', 0, NULL, NULL, '', 1, 0, 0, NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblconsents`
--

CREATE TABLE `tblconsents` (
  `id` int(11) NOT NULL,
  `action` varchar(10) NOT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(40) NOT NULL,
  `contact_id` int(11) NOT NULL DEFAULT 0,
  `lead_id` int(11) NOT NULL DEFAULT 0,
  `description` mediumtext DEFAULT NULL,
  `opt_in_purpose_description` mediumtext DEFAULT NULL,
  `purpose_id` int(11) NOT NULL,
  `staff_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblconsent_purposes`
--

CREATE TABLE `tblconsent_purposes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `last_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcontacts`
--

CREATE TABLE `tblcontacts` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `is_primary` int(11) NOT NULL DEFAULT 1,
  `firstname` varchar(191) NOT NULL,
  `lastname` varchar(191) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phonenumber` varchar(100) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `new_pass_key` varchar(32) DEFAULT NULL,
  `new_pass_key_requested` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `email_verification_key` varchar(32) DEFAULT NULL,
  `email_verification_sent_at` datetime DEFAULT NULL,
  `last_ip` varchar(40) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_password_change` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `profile_image` varchar(191) DEFAULT NULL,
  `direction` varchar(3) DEFAULT NULL,
  `invoice_emails` tinyint(1) NOT NULL DEFAULT 1,
  `estimate_emails` tinyint(1) NOT NULL DEFAULT 1,
  `credit_note_emails` tinyint(1) NOT NULL DEFAULT 1,
  `contract_emails` tinyint(1) NOT NULL DEFAULT 1,
  `task_emails` tinyint(1) NOT NULL DEFAULT 1,
  `project_emails` tinyint(1) NOT NULL DEFAULT 1,
  `ticket_emails` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcontact_permissions`
--

CREATE TABLE `tblcontact_permissions` (
  `id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcontracts`
--

CREATE TABLE `tblcontracts` (
  `id` int(11) NOT NULL,
  `content` longtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `client` int(11) NOT NULL,
  `datestart` date DEFAULT NULL,
  `dateend` date DEFAULT NULL,
  `contract_type` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `addedfrom` int(11) NOT NULL,
  `dateadded` datetime NOT NULL,
  `isexpirynotified` int(11) NOT NULL DEFAULT 0,
  `contract_value` decimal(15,2) DEFAULT NULL,
  `trash` tinyint(1) DEFAULT 0,
  `not_visible_to_client` tinyint(1) NOT NULL DEFAULT 0,
  `hash` varchar(32) DEFAULT NULL,
  `signed` tinyint(1) NOT NULL DEFAULT 0,
  `signature` varchar(40) DEFAULT NULL,
  `marked_as_signed` tinyint(1) NOT NULL DEFAULT 0,
  `acceptance_firstname` varchar(50) DEFAULT NULL,
  `acceptance_lastname` varchar(50) DEFAULT NULL,
  `acceptance_email` varchar(100) DEFAULT NULL,
  `acceptance_date` datetime DEFAULT NULL,
  `acceptance_ip` varchar(40) DEFAULT NULL,
  `short_link` varchar(100) DEFAULT NULL,
  `last_sent_at` datetime DEFAULT NULL,
  `contacts_sent_to` mediumtext DEFAULT NULL,
  `last_sign_reminder_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcontracts_types`
--

CREATE TABLE `tblcontracts_types` (
  `id` int(11) NOT NULL,
  `name` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcontract_comments`
--

CREATE TABLE `tblcontract_comments` (
  `id` int(11) NOT NULL,
  `content` longtext DEFAULT NULL,
  `contract_id` int(11) NOT NULL,
  `staffid` int(11) NOT NULL,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcontract_renewals`
--

CREATE TABLE `tblcontract_renewals` (
  `id` int(11) NOT NULL,
  `contractid` int(11) NOT NULL,
  `old_start_date` date NOT NULL,
  `new_start_date` date NOT NULL,
  `old_end_date` date DEFAULT NULL,
  `new_end_date` date DEFAULT NULL,
  `old_value` decimal(15,2) DEFAULT NULL,
  `new_value` decimal(15,2) DEFAULT NULL,
  `date_renewed` datetime NOT NULL,
  `renewed_by` varchar(100) NOT NULL,
  `renewed_by_staff_id` int(11) NOT NULL DEFAULT 0,
  `is_on_old_expiry_notified` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcountries`
--

CREATE TABLE `tblcountries` (
  `country_id` int(11) NOT NULL,
  `iso2` char(2) DEFAULT NULL,
  `short_name` varchar(80) NOT NULL DEFAULT '',
  `long_name` varchar(80) NOT NULL DEFAULT '',
  `iso3` char(3) DEFAULT NULL,
  `numcode` varchar(6) DEFAULT NULL,
  `un_member` varchar(12) DEFAULT NULL,
  `calling_code` varchar(8) DEFAULT NULL,
  `cctld` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblcountries`
--

INSERT INTO `tblcountries` (`country_id`, `iso2`, `short_name`, `long_name`, `iso3`, `numcode`, `un_member`, `calling_code`, `cctld`) VALUES
(1, 'AF', 'Afghanistan', 'Islamic Republic of Afghanistan', 'AFG', '004', 'yes', '93', '.af'),
(2, 'AX', 'Aland Islands', '&Aring;land Islands', 'ALA', '248', 'no', '358', '.ax'),
(3, 'AL', 'Albania', 'Republic of Albania', 'ALB', '008', 'yes', '355', '.al'),
(4, 'DZ', 'Algeria', 'People\'s Democratic Republic of Algeria', 'DZA', '012', 'yes', '213', '.dz'),
(5, 'AS', 'American Samoa', 'American Samoa', 'ASM', '016', 'no', '1+684', '.as'),
(6, 'AD', 'Andorra', 'Principality of Andorra', 'AND', '020', 'yes', '376', '.ad'),
(7, 'AO', 'Angola', 'Republic of Angola', 'AGO', '024', 'yes', '244', '.ao'),
(8, 'AI', 'Anguilla', 'Anguilla', 'AIA', '660', 'no', '1+264', '.ai'),
(9, 'AQ', 'Antarctica', 'Antarctica', 'ATA', '010', 'no', '672', '.aq'),
(10, 'AG', 'Antigua and Barbuda', 'Antigua and Barbuda', 'ATG', '028', 'yes', '1+268', '.ag'),
(11, 'AR', 'Argentina', 'Argentine Republic', 'ARG', '032', 'yes', '54', '.ar'),
(12, 'AM', 'Armenia', 'Republic of Armenia', 'ARM', '051', 'yes', '374', '.am'),
(13, 'AW', 'Aruba', 'Aruba', 'ABW', '533', 'no', '297', '.aw'),
(14, 'AU', 'Australia', 'Commonwealth of Australia', 'AUS', '036', 'yes', '61', '.au'),
(15, 'AT', 'Austria', 'Republic of Austria', 'AUT', '040', 'yes', '43', '.at'),
(16, 'AZ', 'Azerbaijan', 'Republic of Azerbaijan', 'AZE', '031', 'yes', '994', '.az'),
(17, 'BS', 'Bahamas', 'Commonwealth of The Bahamas', 'BHS', '044', 'yes', '1+242', '.bs'),
(18, 'BH', 'Bahrain', 'Kingdom of Bahrain', 'BHR', '048', 'yes', '973', '.bh'),
(19, 'BD', 'Bangladesh', 'People\'s Republic of Bangladesh', 'BGD', '050', 'yes', '880', '.bd'),
(20, 'BB', 'Barbados', 'Barbados', 'BRB', '052', 'yes', '1+246', '.bb'),
(21, 'BY', 'Belarus', 'Republic of Belarus', 'BLR', '112', 'yes', '375', '.by'),
(22, 'BE', 'Belgium', 'Kingdom of Belgium', 'BEL', '056', 'yes', '32', '.be'),
(23, 'BZ', 'Belize', 'Belize', 'BLZ', '084', 'yes', '501', '.bz'),
(24, 'BJ', 'Benin', 'Republic of Benin', 'BEN', '204', 'yes', '229', '.bj'),
(25, 'BM', 'Bermuda', 'Bermuda Islands', 'BMU', '060', 'no', '1+441', '.bm'),
(26, 'BT', 'Bhutan', 'Kingdom of Bhutan', 'BTN', '064', 'yes', '975', '.bt'),
(27, 'BO', 'Bolivia', 'Plurinational State of Bolivia', 'BOL', '068', 'yes', '591', '.bo'),
(28, 'BQ', 'Bonaire, Sint Eustatius and Saba', 'Bonaire, Sint Eustatius and Saba', 'BES', '535', 'no', '599', '.bq'),
(29, 'BA', 'Bosnia and Herzegovina', 'Bosnia and Herzegovina', 'BIH', '070', 'yes', '387', '.ba'),
(30, 'BW', 'Botswana', 'Republic of Botswana', 'BWA', '072', 'yes', '267', '.bw'),
(31, 'BV', 'Bouvet Island', 'Bouvet Island', 'BVT', '074', 'no', 'NONE', '.bv'),
(32, 'BR', 'Brazil', 'Federative Republic of Brazil', 'BRA', '076', 'yes', '55', '.br'),
(33, 'IO', 'British Indian Ocean Territory', 'British Indian Ocean Territory', 'IOT', '086', 'no', '246', '.io'),
(34, 'BN', 'Brunei', 'Brunei Darussalam', 'BRN', '096', 'yes', '673', '.bn'),
(35, 'BG', 'Bulgaria', 'Republic of Bulgaria', 'BGR', '100', 'yes', '359', '.bg'),
(36, 'BF', 'Burkina Faso', 'Burkina Faso', 'BFA', '854', 'yes', '226', '.bf'),
(37, 'BI', 'Burundi', 'Republic of Burundi', 'BDI', '108', 'yes', '257', '.bi'),
(38, 'KH', 'Cambodia', 'Kingdom of Cambodia', 'KHM', '116', 'yes', '855', '.kh'),
(39, 'CM', 'Cameroon', 'Republic of Cameroon', 'CMR', '120', 'yes', '237', '.cm'),
(40, 'CA', 'Canada', 'Canada', 'CAN', '124', 'yes', '1', '.ca'),
(41, 'CV', 'Cape Verde', 'Republic of Cape Verde', 'CPV', '132', 'yes', '238', '.cv'),
(42, 'KY', 'Cayman Islands', 'The Cayman Islands', 'CYM', '136', 'no', '1+345', '.ky'),
(43, 'CF', 'Central African Republic', 'Central African Republic', 'CAF', '140', 'yes', '236', '.cf'),
(44, 'TD', 'Chad', 'Republic of Chad', 'TCD', '148', 'yes', '235', '.td'),
(45, 'CL', 'Chile', 'Republic of Chile', 'CHL', '152', 'yes', '56', '.cl'),
(46, 'CN', 'China', 'People\'s Republic of China', 'CHN', '156', 'yes', '86', '.cn'),
(47, 'CX', 'Christmas Island', 'Christmas Island', 'CXR', '162', 'no', '61', '.cx'),
(48, 'CC', 'Cocos (Keeling) Islands', 'Cocos (Keeling) Islands', 'CCK', '166', 'no', '61', '.cc'),
(49, 'CO', 'Colombia', 'Republic of Colombia', 'COL', '170', 'yes', '57', '.co'),
(50, 'KM', 'Comoros', 'Union of the Comoros', 'COM', '174', 'yes', '269', '.km'),
(51, 'CG', 'Congo', 'Republic of the Congo', 'COG', '178', 'yes', '242', '.cg'),
(52, 'CK', 'Cook Islands', 'Cook Islands', 'COK', '184', 'some', '682', '.ck'),
(53, 'CR', 'Costa Rica', 'Republic of Costa Rica', 'CRI', '188', 'yes', '506', '.cr'),
(54, 'CI', 'Cote d\'ivoire (Ivory Coast)', 'Republic of C&ocirc;te D\'Ivoire (Ivory Coast)', 'CIV', '384', 'yes', '225', '.ci'),
(55, 'HR', 'Croatia', 'Republic of Croatia', 'HRV', '191', 'yes', '385', '.hr'),
(56, 'CU', 'Cuba', 'Republic of Cuba', 'CUB', '192', 'yes', '53', '.cu'),
(57, 'CW', 'Curacao', 'Cura&ccedil;ao', 'CUW', '531', 'no', '599', '.cw'),
(58, 'CY', 'Cyprus', 'Republic of Cyprus', 'CYP', '196', 'yes', '357', '.cy'),
(59, 'CZ', 'Czech Republic', 'Czech Republic', 'CZE', '203', 'yes', '420', '.cz'),
(60, 'CD', 'Democratic Republic of the Congo', 'Democratic Republic of the Congo', 'COD', '180', 'yes', '243', '.cd'),
(61, 'DK', 'Denmark', 'Kingdom of Denmark', 'DNK', '208', 'yes', '45', '.dk'),
(62, 'DJ', 'Djibouti', 'Republic of Djibouti', 'DJI', '262', 'yes', '253', '.dj'),
(63, 'DM', 'Dominica', 'Commonwealth of Dominica', 'DMA', '212', 'yes', '1+767', '.dm'),
(64, 'DO', 'Dominican Republic', 'Dominican Republic', 'DOM', '214', 'yes', '1+809, 8', '.do'),
(65, 'EC', 'Ecuador', 'Republic of Ecuador', 'ECU', '218', 'yes', '593', '.ec'),
(66, 'EG', 'Egypt', 'Arab Republic of Egypt', 'EGY', '818', 'yes', '20', '.eg'),
(67, 'SV', 'El Salvador', 'Republic of El Salvador', 'SLV', '222', 'yes', '503', '.sv'),
(68, 'GQ', 'Equatorial Guinea', 'Republic of Equatorial Guinea', 'GNQ', '226', 'yes', '240', '.gq'),
(69, 'ER', 'Eritrea', 'State of Eritrea', 'ERI', '232', 'yes', '291', '.er'),
(70, 'EE', 'Estonia', 'Republic of Estonia', 'EST', '233', 'yes', '372', '.ee'),
(71, 'ET', 'Ethiopia', 'Federal Democratic Republic of Ethiopia', 'ETH', '231', 'yes', '251', '.et'),
(72, 'FK', 'Falkland Islands (Malvinas)', 'The Falkland Islands (Malvinas)', 'FLK', '238', 'no', '500', '.fk'),
(73, 'FO', 'Faroe Islands', 'The Faroe Islands', 'FRO', '234', 'no', '298', '.fo'),
(74, 'FJ', 'Fiji', 'Republic of Fiji', 'FJI', '242', 'yes', '679', '.fj'),
(75, 'FI', 'Finland', 'Republic of Finland', 'FIN', '246', 'yes', '358', '.fi'),
(76, 'FR', 'France', 'French Republic', 'FRA', '250', 'yes', '33', '.fr'),
(77, 'GF', 'French Guiana', 'French Guiana', 'GUF', '254', 'no', '594', '.gf'),
(78, 'PF', 'French Polynesia', 'French Polynesia', 'PYF', '258', 'no', '689', '.pf'),
(79, 'TF', 'French Southern Territories', 'French Southern Territories', 'ATF', '260', 'no', NULL, '.tf'),
(80, 'GA', 'Gabon', 'Gabonese Republic', 'GAB', '266', 'yes', '241', '.ga'),
(81, 'GM', 'Gambia', 'Republic of The Gambia', 'GMB', '270', 'yes', '220', '.gm'),
(82, 'GE', 'Georgia', 'Georgia', 'GEO', '268', 'yes', '995', '.ge'),
(83, 'DE', 'Germany', 'Federal Republic of Germany', 'DEU', '276', 'yes', '49', '.de'),
(84, 'GH', 'Ghana', 'Republic of Ghana', 'GHA', '288', 'yes', '233', '.gh'),
(85, 'GI', 'Gibraltar', 'Gibraltar', 'GIB', '292', 'no', '350', '.gi'),
(86, 'GR', 'Greece', 'Hellenic Republic', 'GRC', '300', 'yes', '30', '.gr'),
(87, 'GL', 'Greenland', 'Greenland', 'GRL', '304', 'no', '299', '.gl'),
(88, 'GD', 'Grenada', 'Grenada', 'GRD', '308', 'yes', '1+473', '.gd'),
(89, 'GP', 'Guadaloupe', 'Guadeloupe', 'GLP', '312', 'no', '590', '.gp'),
(90, 'GU', 'Guam', 'Guam', 'GUM', '316', 'no', '1+671', '.gu'),
(91, 'GT', 'Guatemala', 'Republic of Guatemala', 'GTM', '320', 'yes', '502', '.gt'),
(92, 'GG', 'Guernsey', 'Guernsey', 'GGY', '831', 'no', '44', '.gg'),
(93, 'GN', 'Guinea', 'Republic of Guinea', 'GIN', '324', 'yes', '224', '.gn'),
(94, 'GW', 'Guinea-Bissau', 'Republic of Guinea-Bissau', 'GNB', '624', 'yes', '245', '.gw'),
(95, 'GY', 'Guyana', 'Co-operative Republic of Guyana', 'GUY', '328', 'yes', '592', '.gy'),
(96, 'HT', 'Haiti', 'Republic of Haiti', 'HTI', '332', 'yes', '509', '.ht'),
(97, 'HM', 'Heard Island and McDonald Islands', 'Heard Island and McDonald Islands', 'HMD', '334', 'no', 'NONE', '.hm'),
(98, 'HN', 'Honduras', 'Republic of Honduras', 'HND', '340', 'yes', '504', '.hn'),
(99, 'HK', 'Hong Kong', 'Hong Kong', 'HKG', '344', 'no', '852', '.hk'),
(100, 'HU', 'Hungary', 'Hungary', 'HUN', '348', 'yes', '36', '.hu'),
(101, 'IS', 'Iceland', 'Republic of Iceland', 'ISL', '352', 'yes', '354', '.is'),
(102, 'IN', 'India', 'Republic of India', 'IND', '356', 'yes', '91', '.in'),
(103, 'ID', 'Indonesia', 'Republic of Indonesia', 'IDN', '360', 'yes', '62', '.id'),
(104, 'IR', 'Iran', 'Islamic Republic of Iran', 'IRN', '364', 'yes', '98', '.ir'),
(105, 'IQ', 'Iraq', 'Republic of Iraq', 'IRQ', '368', 'yes', '964', '.iq'),
(106, 'IE', 'Ireland', 'Ireland', 'IRL', '372', 'yes', '353', '.ie'),
(107, 'IM', 'Isle of Man', 'Isle of Man', 'IMN', '833', 'no', '44', '.im'),
(108, 'IL', 'Israel', 'State of Israel', 'ISR', '376', 'yes', '972', '.il'),
(109, 'IT', 'Italy', 'Italian Republic', 'ITA', '380', 'yes', '39', '.jm'),
(110, 'JM', 'Jamaica', 'Jamaica', 'JAM', '388', 'yes', '1+876', '.jm'),
(111, 'JP', 'Japan', 'Japan', 'JPN', '392', 'yes', '81', '.jp'),
(112, 'JE', 'Jersey', 'The Bailiwick of Jersey', 'JEY', '832', 'no', '44', '.je'),
(113, 'JO', 'Jordan', 'Hashemite Kingdom of Jordan', 'JOR', '400', 'yes', '962', '.jo'),
(114, 'KZ', 'Kazakhstan', 'Republic of Kazakhstan', 'KAZ', '398', 'yes', '7', '.kz'),
(115, 'KE', 'Kenya', 'Republic of Kenya', 'KEN', '404', 'yes', '254', '.ke'),
(116, 'KI', 'Kiribati', 'Republic of Kiribati', 'KIR', '296', 'yes', '686', '.ki'),
(117, 'XK', 'Kosovo', 'Republic of Kosovo', '---', '---', 'some', '381', ''),
(118, 'KW', 'Kuwait', 'State of Kuwait', 'KWT', '414', 'yes', '965', '.kw'),
(119, 'KG', 'Kyrgyzstan', 'Kyrgyz Republic', 'KGZ', '417', 'yes', '996', '.kg'),
(120, 'LA', 'Laos', 'Lao People\'s Democratic Republic', 'LAO', '418', 'yes', '856', '.la'),
(121, 'LV', 'Latvia', 'Republic of Latvia', 'LVA', '428', 'yes', '371', '.lv'),
(122, 'LB', 'Lebanon', 'Republic of Lebanon', 'LBN', '422', 'yes', '961', '.lb'),
(123, 'LS', 'Lesotho', 'Kingdom of Lesotho', 'LSO', '426', 'yes', '266', '.ls'),
(124, 'LR', 'Liberia', 'Republic of Liberia', 'LBR', '430', 'yes', '231', '.lr'),
(125, 'LY', 'Libya', 'Libya', 'LBY', '434', 'yes', '218', '.ly'),
(126, 'LI', 'Liechtenstein', 'Principality of Liechtenstein', 'LIE', '438', 'yes', '423', '.li'),
(127, 'LT', 'Lithuania', 'Republic of Lithuania', 'LTU', '440', 'yes', '370', '.lt'),
(128, 'LU', 'Luxembourg', 'Grand Duchy of Luxembourg', 'LUX', '442', 'yes', '352', '.lu'),
(129, 'MO', 'Macao', 'The Macao Special Administrative Region', 'MAC', '446', 'no', '853', '.mo'),
(130, 'MK', 'North Macedonia', 'Republic of North Macedonia', 'MKD', '807', 'yes', '389', '.mk'),
(131, 'MG', 'Madagascar', 'Republic of Madagascar', 'MDG', '450', 'yes', '261', '.mg'),
(132, 'MW', 'Malawi', 'Republic of Malawi', 'MWI', '454', 'yes', '265', '.mw'),
(133, 'MY', 'Malaysia', 'Malaysia', 'MYS', '458', 'yes', '60', '.my'),
(134, 'MV', 'Maldives', 'Republic of Maldives', 'MDV', '462', 'yes', '960', '.mv'),
(135, 'ML', 'Mali', 'Republic of Mali', 'MLI', '466', 'yes', '223', '.ml'),
(136, 'MT', 'Malta', 'Republic of Malta', 'MLT', '470', 'yes', '356', '.mt'),
(137, 'MH', 'Marshall Islands', 'Republic of the Marshall Islands', 'MHL', '584', 'yes', '692', '.mh'),
(138, 'MQ', 'Martinique', 'Martinique', 'MTQ', '474', 'no', '596', '.mq'),
(139, 'MR', 'Mauritania', 'Islamic Republic of Mauritania', 'MRT', '478', 'yes', '222', '.mr'),
(140, 'MU', 'Mauritius', 'Republic of Mauritius', 'MUS', '480', 'yes', '230', '.mu'),
(141, 'YT', 'Mayotte', 'Mayotte', 'MYT', '175', 'no', '262', '.yt'),
(142, 'MX', 'Mexico', 'United Mexican States', 'MEX', '484', 'yes', '52', '.mx'),
(143, 'FM', 'Micronesia', 'Federated States of Micronesia', 'FSM', '583', 'yes', '691', '.fm'),
(144, 'MD', 'Moldava', 'Republic of Moldova', 'MDA', '498', 'yes', '373', '.md'),
(145, 'MC', 'Monaco', 'Principality of Monaco', 'MCO', '492', 'yes', '377', '.mc'),
(146, 'MN', 'Mongolia', 'Mongolia', 'MNG', '496', 'yes', '976', '.mn'),
(147, 'ME', 'Montenegro', 'Montenegro', 'MNE', '499', 'yes', '382', '.me'),
(148, 'MS', 'Montserrat', 'Montserrat', 'MSR', '500', 'no', '1+664', '.ms'),
(149, 'MA', 'Morocco', 'Kingdom of Morocco', 'MAR', '504', 'yes', '212', '.ma'),
(150, 'MZ', 'Mozambique', 'Republic of Mozambique', 'MOZ', '508', 'yes', '258', '.mz'),
(151, 'MM', 'Myanmar (Burma)', 'Republic of the Union of Myanmar', 'MMR', '104', 'yes', '95', '.mm'),
(152, 'NA', 'Namibia', 'Republic of Namibia', 'NAM', '516', 'yes', '264', '.na'),
(153, 'NR', 'Nauru', 'Republic of Nauru', 'NRU', '520', 'yes', '674', '.nr'),
(154, 'NP', 'Nepal', 'Federal Democratic Republic of Nepal', 'NPL', '524', 'yes', '977', '.np'),
(155, 'NL', 'Netherlands', 'Kingdom of the Netherlands', 'NLD', '528', 'yes', '31', '.nl'),
(156, 'NC', 'New Caledonia', 'New Caledonia', 'NCL', '540', 'no', '687', '.nc'),
(157, 'NZ', 'New Zealand', 'New Zealand', 'NZL', '554', 'yes', '64', '.nz'),
(158, 'NI', 'Nicaragua', 'Republic of Nicaragua', 'NIC', '558', 'yes', '505', '.ni'),
(159, 'NE', 'Niger', 'Republic of Niger', 'NER', '562', 'yes', '227', '.ne'),
(160, 'NG', 'Nigeria', 'Federal Republic of Nigeria', 'NGA', '566', 'yes', '234', '.ng'),
(161, 'NU', 'Niue', 'Niue', 'NIU', '570', 'some', '683', '.nu'),
(162, 'NF', 'Norfolk Island', 'Norfolk Island', 'NFK', '574', 'no', '672', '.nf'),
(163, 'KP', 'North Korea', 'Democratic People\'s Republic of Korea', 'PRK', '408', 'yes', '850', '.kp'),
(164, 'MP', 'Northern Mariana Islands', 'Northern Mariana Islands', 'MNP', '580', 'no', '1+670', '.mp'),
(165, 'NO', 'Norway', 'Kingdom of Norway', 'NOR', '578', 'yes', '47', '.no'),
(166, 'OM', 'Oman', 'Sultanate of Oman', 'OMN', '512', 'yes', '968', '.om'),
(167, 'PK', 'Pakistan', 'Islamic Republic of Pakistan', 'PAK', '586', 'yes', '92', '.pk'),
(168, 'PW', 'Palau', 'Republic of Palau', 'PLW', '585', 'yes', '680', '.pw'),
(169, 'PS', 'Palestine', 'State of Palestine (or Occupied Palestinian Territory)', 'PSE', '275', 'some', '970', '.ps'),
(170, 'PA', 'Panama', 'Republic of Panama', 'PAN', '591', 'yes', '507', '.pa'),
(171, 'PG', 'Papua New Guinea', 'Independent State of Papua New Guinea', 'PNG', '598', 'yes', '675', '.pg'),
(172, 'PY', 'Paraguay', 'Republic of Paraguay', 'PRY', '600', 'yes', '595', '.py'),
(173, 'PE', 'Peru', 'Republic of Peru', 'PER', '604', 'yes', '51', '.pe'),
(174, 'PH', 'Philippines', 'Republic of the Philippines', 'PHL', '608', 'yes', '63', '.ph'),
(175, 'PN', 'Pitcairn', 'Pitcairn', 'PCN', '612', 'no', 'NONE', '.pn'),
(176, 'PL', 'Poland', 'Republic of Poland', 'POL', '616', 'yes', '48', '.pl'),
(177, 'PT', 'Portugal', 'Portuguese Republic', 'PRT', '620', 'yes', '351', '.pt'),
(178, 'PR', 'Puerto Rico', 'Commonwealth of Puerto Rico', 'PRI', '630', 'no', '1+939', '.pr'),
(179, 'QA', 'Qatar', 'State of Qatar', 'QAT', '634', 'yes', '974', '.qa'),
(180, 'RE', 'Reunion', 'R&eacute;union', 'REU', '638', 'no', '262', '.re'),
(181, 'RO', 'Romania', 'Romania', 'ROU', '642', 'yes', '40', '.ro'),
(182, 'RU', 'Russia', 'Russian Federation', 'RUS', '643', 'yes', '7', '.ru'),
(183, 'RW', 'Rwanda', 'Republic of Rwanda', 'RWA', '646', 'yes', '250', '.rw'),
(184, 'BL', 'Saint Barthelemy', 'Saint Barth&eacute;lemy', 'BLM', '652', 'no', '590', '.bl'),
(185, 'SH', 'Saint Helena', 'Saint Helena, Ascension and Tristan da Cunha', 'SHN', '654', 'no', '290', '.sh'),
(186, 'KN', 'Saint Kitts and Nevis', 'Federation of Saint Christopher and Nevis', 'KNA', '659', 'yes', '1+869', '.kn'),
(187, 'LC', 'Saint Lucia', 'Saint Lucia', 'LCA', '662', 'yes', '1+758', '.lc'),
(188, 'MF', 'Saint Martin', 'Saint Martin', 'MAF', '663', 'no', '590', '.mf'),
(189, 'PM', 'Saint Pierre and Miquelon', 'Saint Pierre and Miquelon', 'SPM', '666', 'no', '508', '.pm'),
(190, 'VC', 'Saint Vincent and the Grenadines', 'Saint Vincent and the Grenadines', 'VCT', '670', 'yes', '1+784', '.vc'),
(191, 'WS', 'Samoa', 'Independent State of Samoa', 'WSM', '882', 'yes', '685', '.ws'),
(192, 'SM', 'San Marino', 'Republic of San Marino', 'SMR', '674', 'yes', '378', '.sm'),
(193, 'ST', 'Sao Tome and Principe', 'Democratic Republic of S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'STP', '678', 'yes', '239', '.st'),
(194, 'SA', 'Saudi Arabia', 'Kingdom of Saudi Arabia', 'SAU', '682', 'yes', '966', '.sa'),
(195, 'SN', 'Senegal', 'Republic of Senegal', 'SEN', '686', 'yes', '221', '.sn'),
(196, 'RS', 'Serbia', 'Republic of Serbia', 'SRB', '688', 'yes', '381', '.rs'),
(197, 'SC', 'Seychelles', 'Republic of Seychelles', 'SYC', '690', 'yes', '248', '.sc'),
(198, 'SL', 'Sierra Leone', 'Republic of Sierra Leone', 'SLE', '694', 'yes', '232', '.sl'),
(199, 'SG', 'Singapore', 'Republic of Singapore', 'SGP', '702', 'yes', '65', '.sg'),
(200, 'SX', 'Sint Maarten', 'Sint Maarten', 'SXM', '534', 'no', '1+721', '.sx'),
(201, 'SK', 'Slovakia', 'Slovak Republic', 'SVK', '703', 'yes', '421', '.sk'),
(202, 'SI', 'Slovenia', 'Republic of Slovenia', 'SVN', '705', 'yes', '386', '.si'),
(203, 'SB', 'Solomon Islands', 'Solomon Islands', 'SLB', '090', 'yes', '677', '.sb'),
(204, 'SO', 'Somalia', 'Somali Republic', 'SOM', '706', 'yes', '252', '.so'),
(205, 'ZA', 'South Africa', 'Republic of South Africa', 'ZAF', '710', 'yes', '27', '.za'),
(206, 'GS', 'South Georgia and the South Sandwich Islands', 'South Georgia and the South Sandwich Islands', 'SGS', '239', 'no', '500', '.gs'),
(207, 'KR', 'South Korea', 'Republic of Korea', 'KOR', '410', 'yes', '82', '.kr'),
(208, 'SS', 'South Sudan', 'Republic of South Sudan', 'SSD', '728', 'yes', '211', '.ss'),
(209, 'ES', 'Spain', 'Kingdom of Spain', 'ESP', '724', 'yes', '34', '.es'),
(210, 'LK', 'Sri Lanka', 'Democratic Socialist Republic of Sri Lanka', 'LKA', '144', 'yes', '94', '.lk'),
(211, 'SD', 'Sudan', 'Republic of the Sudan', 'SDN', '729', 'yes', '249', '.sd'),
(212, 'SR', 'Suriname', 'Republic of Suriname', 'SUR', '740', 'yes', '597', '.sr'),
(213, 'SJ', 'Svalbard and Jan Mayen', 'Svalbard and Jan Mayen', 'SJM', '744', 'no', '47', '.sj'),
(214, 'SZ', 'Swaziland', 'Kingdom of Swaziland', 'SWZ', '748', 'yes', '268', '.sz'),
(215, 'SE', 'Sweden', 'Kingdom of Sweden', 'SWE', '752', 'yes', '46', '.se'),
(216, 'CH', 'Switzerland', 'Swiss Confederation', 'CHE', '756', 'yes', '41', '.ch'),
(217, 'SY', 'Syria', 'Syrian Arab Republic', 'SYR', '760', 'yes', '963', '.sy'),
(218, 'TW', 'Taiwan', 'Republic of China (Taiwan)', 'TWN', '158', 'former', '886', '.tw'),
(219, 'TJ', 'Tajikistan', 'Republic of Tajikistan', 'TJK', '762', 'yes', '992', '.tj'),
(220, 'TZ', 'Tanzania', 'United Republic of Tanzania', 'TZA', '834', 'yes', '255', '.tz'),
(221, 'TH', 'Thailand', 'Kingdom of Thailand', 'THA', '764', 'yes', '66', '.th'),
(222, 'TL', 'Timor-Leste (East Timor)', 'Democratic Republic of Timor-Leste', 'TLS', '626', 'yes', '670', '.tl'),
(223, 'TG', 'Togo', 'Togolese Republic', 'TGO', '768', 'yes', '228', '.tg'),
(224, 'TK', 'Tokelau', 'Tokelau', 'TKL', '772', 'no', '690', '.tk'),
(225, 'TO', 'Tonga', 'Kingdom of Tonga', 'TON', '776', 'yes', '676', '.to'),
(226, 'TT', 'Trinidad and Tobago', 'Republic of Trinidad and Tobago', 'TTO', '780', 'yes', '1+868', '.tt'),
(227, 'TN', 'Tunisia', 'Republic of Tunisia', 'TUN', '788', 'yes', '216', '.tn'),
(228, 'TR', 'Turkey', 'Republic of Turkey', 'TUR', '792', 'yes', '90', '.tr'),
(229, 'TM', 'Turkmenistan', 'Turkmenistan', 'TKM', '795', 'yes', '993', '.tm'),
(230, 'TC', 'Turks and Caicos Islands', 'Turks and Caicos Islands', 'TCA', '796', 'no', '1+649', '.tc'),
(231, 'TV', 'Tuvalu', 'Tuvalu', 'TUV', '798', 'yes', '688', '.tv'),
(232, 'UG', 'Uganda', 'Republic of Uganda', 'UGA', '800', 'yes', '256', '.ug'),
(233, 'UA', 'Ukraine', 'Ukraine', 'UKR', '804', 'yes', '380', '.ua'),
(234, 'AE', 'United Arab Emirates', 'United Arab Emirates', 'ARE', '784', 'yes', '971', '.ae'),
(235, 'GB', 'United Kingdom', 'United Kingdom of Great Britain and Nothern Ireland', 'GBR', '826', 'yes', '44', '.uk'),
(236, 'US', 'United States', 'United States of America', 'USA', '840', 'yes', '1', '.us'),
(237, 'UM', 'United States Minor Outlying Islands', 'United States Minor Outlying Islands', 'UMI', '581', 'no', 'NONE', 'NONE'),
(238, 'UY', 'Uruguay', 'Eastern Republic of Uruguay', 'URY', '858', 'yes', '598', '.uy'),
(239, 'UZ', 'Uzbekistan', 'Republic of Uzbekistan', 'UZB', '860', 'yes', '998', '.uz'),
(240, 'VU', 'Vanuatu', 'Republic of Vanuatu', 'VUT', '548', 'yes', '678', '.vu'),
(241, 'VA', 'Vatican City', 'State of the Vatican City', 'VAT', '336', 'no', '39', '.va'),
(242, 'VE', 'Venezuela', 'Bolivarian Republic of Venezuela', 'VEN', '862', 'yes', '58', '.ve'),
(243, 'VN', 'Vietnam', 'Socialist Republic of Vietnam', 'VNM', '704', 'yes', '84', '.vn'),
(244, 'VG', 'Virgin Islands, British', 'British Virgin Islands', 'VGB', '092', 'no', '1+284', '.vg'),
(245, 'VI', 'Virgin Islands, US', 'Virgin Islands of the United States', 'VIR', '850', 'no', '1+340', '.vi'),
(246, 'WF', 'Wallis and Futuna', 'Wallis and Futuna', 'WLF', '876', 'no', '681', '.wf'),
(247, 'EH', 'Western Sahara', 'Western Sahara', 'ESH', '732', 'no', '212', '.eh'),
(248, 'YE', 'Yemen', 'Republic of Yemen', 'YEM', '887', 'yes', '967', '.ye'),
(249, 'ZM', 'Zambia', 'Republic of Zambia', 'ZMB', '894', 'yes', '260', '.zm'),
(250, 'ZW', 'Zimbabwe', 'Republic of Zimbabwe', 'ZWE', '716', 'yes', '263', '.zw');

-- --------------------------------------------------------

--
-- Table structure for table `tblcreditnotes`
--

CREATE TABLE `tblcreditnotes` (
  `id` int(11) NOT NULL,
  `clientid` int(11) NOT NULL,
  `deleted_customer_name` varchar(100) DEFAULT NULL,
  `number` int(11) NOT NULL,
  `prefix` varchar(50) DEFAULT NULL,
  `number_format` int(11) NOT NULL DEFAULT 1,
  `formatted_number` varchar(100) DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `date` date NOT NULL,
  `adminnote` mediumtext DEFAULT NULL,
  `terms` mediumtext DEFAULT NULL,
  `clientnote` mediumtext DEFAULT NULL,
  `currency` int(11) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `total_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `adjustment` decimal(15,2) DEFAULT NULL,
  `addedfrom` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `discount_percent` decimal(15,2) DEFAULT 0.00,
  `discount_total` decimal(15,2) DEFAULT 0.00,
  `discount_type` varchar(30) NOT NULL,
  `billing_street` varchar(200) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_zip` varchar(100) DEFAULT NULL,
  `billing_country` int(11) DEFAULT NULL,
  `shipping_street` varchar(200) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_zip` varchar(100) DEFAULT NULL,
  `shipping_country` int(11) DEFAULT NULL,
  `include_shipping` tinyint(1) NOT NULL,
  `show_shipping_on_credit_note` tinyint(1) NOT NULL DEFAULT 1,
  `show_quantity_as` int(11) NOT NULL DEFAULT 1,
  `reference_no` varchar(100) DEFAULT NULL,
  `approval_request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcreditnote_refunds`
--

CREATE TABLE `tblcreditnote_refunds` (
  `id` int(11) NOT NULL,
  `credit_note_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `refunded_on` date NOT NULL,
  `payment_mode` varchar(40) NOT NULL,
  `note` mediumtext DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcredits`
--

CREATE TABLE `tblcredits` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `credit_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `date_applied` datetime NOT NULL,
  `amount` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcurrencies`
--

CREATE TABLE `tblcurrencies` (
  `id` int(11) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `decimal_separator` varchar(5) DEFAULT NULL,
  `thousand_separator` varchar(5) DEFAULT NULL,
  `placement` varchar(10) DEFAULT NULL,
  `isdefault` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblcurrencies`
--

INSERT INTO `tblcurrencies` (`id`, `symbol`, `name`, `decimal_separator`, `thousand_separator`, `placement`, `isdefault`) VALUES
(1, 'K', 'K', '.', ',', 'before', 1),
(2, '€', 'EUR', ',', '.', 'before', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tblcustomers_groups`
--

CREATE TABLE `tblcustomers_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcustomer_admins`
--

CREATE TABLE `tblcustomer_admins` (
  `staff_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `date_assigned` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcustomer_groups`
--

CREATE TABLE `tblcustomer_groups` (
  `id` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcustomfields`
--

CREATE TABLE `tblcustomfields` (
  `id` int(11) NOT NULL,
  `fieldto` varchar(30) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL,
  `options` longtext DEFAULT NULL,
  `display_inline` tinyint(1) NOT NULL DEFAULT 0,
  `field_order` int(11) DEFAULT 0,
  `active` int(11) NOT NULL DEFAULT 1,
  `show_on_pdf` int(11) NOT NULL DEFAULT 0,
  `show_on_ticket_form` tinyint(1) NOT NULL DEFAULT 0,
  `only_admin` tinyint(1) NOT NULL DEFAULT 0,
  `show_on_table` tinyint(1) NOT NULL DEFAULT 0,
  `show_on_client_portal` int(11) NOT NULL DEFAULT 0,
  `disalow_client_to_edit` int(11) NOT NULL DEFAULT 0,
  `bs_column` int(11) NOT NULL DEFAULT 12,
  `default_value` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcustomfieldsvalues`
--

CREATE TABLE `tblcustomfieldsvalues` (
  `id` int(11) NOT NULL,
  `relid` int(11) NOT NULL,
  `fieldid` int(11) NOT NULL,
  `fieldto` varchar(15) NOT NULL,
  `value` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcustom_templates`
--

CREATE TABLE `tblcustom_templates` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `template_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbldepartments`
--

CREATE TABLE `tbldepartments` (
  `departmentid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `imap_username` varchar(191) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `email_from_header` tinyint(1) NOT NULL DEFAULT 0,
  `host` varchar(150) DEFAULT NULL,
  `password` longtext DEFAULT NULL,
  `encryption` varchar(3) DEFAULT NULL,
  `folder` varchar(191) NOT NULL DEFAULT 'INBOX',
  `delete_after_import` int(11) NOT NULL DEFAULT 0,
  `calendar_id` longtext DEFAULT NULL,
  `hidefromclient` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbldismissed_announcements`
--

CREATE TABLE `tbldismissed_announcements` (
  `dismissedannouncementid` int(11) NOT NULL,
  `announcementid` int(11) NOT NULL,
  `staff` int(11) NOT NULL,
  `userid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblemailtemplates`
--

CREATE TABLE `tblemailtemplates` (
  `emailtemplateid` int(11) NOT NULL,
  `type` longtext NOT NULL,
  `slug` varchar(100) NOT NULL,
  `language` varchar(40) DEFAULT NULL,
  `name` longtext NOT NULL,
  `subject` longtext NOT NULL,
  `message` longtext NOT NULL,
  `fromname` longtext NOT NULL,
  `fromemail` varchar(100) DEFAULT NULL,
  `plaintext` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(4) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblemailtemplates`
--

INSERT INTO `tblemailtemplates` (`emailtemplateid`, `type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
(1, 'client', 'new-client-created', 'english', 'New Contact Added/Registered (Welcome Email)', 'Welcome aboard', 'Dear {contact_firstname} {contact_lastname}<br /><br />Thank you for registering on the <strong>{companyname}</strong> CRM System.<br /><br />We just wanted to say welcome.<br /><br />Please contact us if you need any help.<br /><br />Click here to view your profile: <a href=\"{crm_url}\">{crm_url}</a><br /><br />Kind Regards, <br />{email_signature}<br /><br />(This is an automated email, so please don\'t reply to this email address)', '{companyname} | CRM', '', 0, 1, 0),
(2, 'invoice', 'invoice-send-to-client', 'english', 'Send Invoice to Customer', 'Invoice with number {invoice_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">We have prepared the following invoice for you: <strong># {invoice_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>Invoice status</strong>: {invoice_status}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the invoice on the following link: <a href=\"{invoice_link}\">{invoice_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(3, 'ticket', 'new-ticket-opened-admin', 'english', 'New Ticket Opened (Opened by Staff, Sent to Customer)', 'New Support Ticket Opened', '<p><span style=\"font-size: 12pt;\">Hi {contact_firstname} {contact_lastname}</span><br><br><span style=\"font-size: 12pt;\">New support ticket has been opened.</span><br><br><span style=\"font-size: 12pt;\"><strong>Subject:</strong> {ticket_subject}</span><br><span style=\"font-size: 12pt;\"><strong>Department:</strong> {ticket_department}</span><br><span style=\"font-size: 12pt;\"><strong>Priority:</strong> {ticket_priority}<br></span><br><span style=\"font-size: 12pt;\"><strong>Ticket message:</strong></span><br><span style=\"font-size: 12pt;\">{ticket_message}</span><br><br><span style=\"font-size: 12pt;\">You can view the ticket on the following link: <a href=\"{ticket_public_url}\">#{ticket_id}</a><br><br>Kind Regards,</span><br><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
(4, 'ticket', 'ticket-reply', 'english', 'Ticket Reply (Sent to Customer)', 'New Ticket Reply', '<span style=\"font-size: 12pt;\">Hi {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">You have a new ticket reply to ticket <a href=\"{ticket_public_url}\">#{ticket_id}</a></span><br /><br /><span style=\"font-size: 12pt;\"><strong>Ticket Subject:</strong> {ticket_subject}<br /></span><br /><span style=\"font-size: 12pt;\"><strong>Ticket message:</strong></span><br /><span style=\"font-size: 12pt;\">{ticket_message}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the ticket on the following link: <a href=\"{ticket_public_url}\">#{ticket_id}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(5, 'ticket', 'ticket-autoresponse', 'english', 'New Ticket Opened - Autoresponse', 'New Support Ticket Opened', '<span style=\"font-size: 12pt;\">Hi {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Thank you for contacting our support team. A support ticket has now been opened for your request. You will be notified when a response is made by email.</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Subject:</strong> {ticket_subject}</span><br /><span style=\"font-size: 12pt;\"><strong>Department</strong>: {ticket_department}</span><br /><span style=\"font-size: 12pt;\"><strong>Priority:</strong> {ticket_priority}</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Ticket message:</strong></span><br /><span style=\"font-size: 12pt;\">{ticket_message}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the ticket on the following link: <a href=\"{ticket_public_url}\">#{ticket_id}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(6, 'invoice', 'invoice-payment-recorded', 'english', 'Invoice Payment Recorded (Sent to Customer)', 'Invoice Payment Recorded', '<span style=\"font-size: 12pt;\">Hello {contact_firstname}&nbsp;{contact_lastname}<br /><br /></span>Thank you for the payment. Find the payment details below:<br /><br />-------------------------------------------------<br /><br />Amount:&nbsp;<strong>{payment_total}<br /></strong>Date:&nbsp;<strong>{payment_date}</strong><br />Invoice number:&nbsp;<span style=\"font-size: 12pt;\"><strong># {invoice_number}<br /><br /></strong></span>-------------------------------------------------<br /><br />You can always view the invoice for this payment at the following link:&nbsp;<a href=\"{invoice_link}\"><span style=\"font-size: 12pt;\">{invoice_number}</span></a><br /><br />We are looking forward working with you.<br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(7, 'invoice', 'invoice-overdue-notice', 'english', 'Invoice Overdue Notice', 'Invoice Overdue Notice - {invoice_number}', '<span style=\"font-size: 12pt;\">Hi {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">This is an overdue notice for invoice <strong># {invoice_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\">This invoice was due: {invoice_duedate}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the invoice on the following link: <a href=\"{invoice_link}\">{invoice_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(8, 'invoice', 'invoice-already-send', 'english', 'Invoice Already Sent to Customer', 'Invoice # {invoice_number} ', '<span style=\"font-size: 12pt;\">Hi {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">At your request, here is the invoice with number <strong># {invoice_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\">You can view the invoice on the following link: <a href=\"{invoice_link}\">{invoice_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(9, 'ticket', 'new-ticket-created-staff', 'english', 'New Ticket Created (Opened by Customer, Sent to Staff Members)', 'New Ticket Created', '<p><span style=\"font-size: 12pt;\">A new support ticket has been opened.</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Subject</strong>: {ticket_subject}</span><br /><span style=\"font-size: 12pt;\"><strong>Department</strong>: {ticket_department}</span><br /><span style=\"font-size: 12pt;\"><strong>Priority</strong>: {ticket_priority}<br /></span><br /><span style=\"font-size: 12pt;\"><strong>Ticket message:</strong></span><br /><span style=\"font-size: 12pt;\">{ticket_message}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the ticket on the following link: <a href=\"{ticket_url}\">#{ticket_id}</a></span><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
(10, 'estimate', 'estimate-send-to-client', 'english', 'Send Estimate to Customer', 'Estimate # {estimate_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached estimate <strong># {estimate_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>Estimate status:</strong> {estimate_status}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the estimate on the following link: <a href=\"{estimate_link}\">{estimate_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
(11, 'ticket', 'ticket-reply-to-admin', 'english', 'Ticket Reply (Sent to Staff)', 'New Support Ticket Reply', '<span style=\"font-size: 12pt;\">A new support ticket reply from {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Subject</strong>: {ticket_subject}</span><br /><span style=\"font-size: 12pt;\"><strong>Department</strong>: {ticket_department}</span><br /><span style=\"font-size: 12pt;\"><strong>Priority</strong>: {ticket_priority}</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Ticket message:</strong></span><br /><span style=\"font-size: 12pt;\">{ticket_message}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the ticket on the following link: <a href=\"{ticket_url}\">#{ticket_id}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(12, 'estimate', 'estimate-already-send', 'english', 'Estimate Already Sent to Customer', 'Estimate # {estimate_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your estimate request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the estimate on the following link: <a href=\"{estimate_link}\">{estimate_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(13, 'contract', 'contract-expiration', 'english', 'Contract Expiration Reminder (Sent to Customer Contacts)', 'Contract Expiration Reminder', '<span style=\"font-size: 12pt;\">Dear {client_company}</span><br /><br /><span style=\"font-size: 12pt;\">This is a reminder that the following contract will expire soon:</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Subject:</strong> {contract_subject}</span><br /><span style=\"font-size: 12pt;\"><strong>Description:</strong> {contract_description}</span><br /><span style=\"font-size: 12pt;\"><strong>Date Start:</strong> {contract_datestart}</span><br /><span style=\"font-size: 12pt;\"><strong>Date End:</strong> {contract_dateend}</span><br /><br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(14, 'tasks', 'task-assigned', 'english', 'New Task Assigned (Sent to Staff)', 'New Task Assigned to You - {task_name}', '<span style=\"font-size: 12pt;\">Dear {staff_firstname}</span><br /><br /><span style=\"font-size: 12pt;\">You have been assigned to a new task:</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Name:</strong> {task_name}<br /></span><strong>Start Date:</strong> {task_startdate}<br /><span style=\"font-size: 12pt;\"><strong>Due date:</strong> {task_duedate}</span><br /><span style=\"font-size: 12pt;\"><strong>Priority:</strong> {task_priority}<br /><br /></span><span style=\"font-size: 12pt;\"><span>You can view the task on the following link</span>: <a href=\"{task_link}\">{task_name}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(15, 'tasks', 'task-added-as-follower', 'english', 'Staff Member Added as Follower on Task (Sent to Staff)', 'You are added as follower on task - {task_name}', '<span style=\"font-size: 12pt;\">Hi {staff_firstname}<br /></span><br /><span style=\"font-size: 12pt;\">You have been added as follower on the following task:</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Name:</strong> {task_name}</span><br /><span style=\"font-size: 12pt;\"><strong>Start date:</strong> {task_startdate}</span><br /><br /><span>You can view the task on the following link</span><span>: </span><a href=\"{task_link}\">{task_name}</a><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(16, 'tasks', 'task-commented', 'english', 'New Comment on Task (Sent to Staff)', 'New Comment on Task - {task_name}', 'Dear {staff_firstname}<br /><br />A comment has been made on the following task:<br /><br /><strong>Name:</strong> {task_name}<br /><strong>Comment:</strong> {task_comment}<br /><br />You can view the task on the following link: <a href=\"{task_link}\">{task_name}</a><br /><br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(17, 'tasks', 'task-added-attachment', 'english', 'New Attachment(s) on Task (Sent to Staff)', 'New Attachment on Task - {task_name}', 'Hi {staff_firstname}<br /><br /><strong>{task_user_take_action}</strong> added an attachment on the following task:<br /><br /><strong>Name:</strong> {task_name}<br /><br />You can view the task on the following link: <a href=\"{task_link}\">{task_name}</a><br /><br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(18, 'estimate', 'estimate-declined-to-staff', 'english', 'Estimate Declined (Sent to Staff)', 'Customer Declined Estimate', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined estimate with number <strong># {estimate_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the estimate on the following link: <a href=\"{estimate_link}\">{estimate_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(19, 'estimate', 'estimate-accepted-to-staff', 'english', 'Estimate Accepted (Sent to Staff)', 'Customer Accepted Estimate', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted estimate with number <strong># {estimate_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the estimate on the following link: <a href=\"{estimate_link}\">{estimate_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(20, 'proposals', 'proposal-client-accepted', 'english', 'Customer Action - Accepted (Sent to Staff)', 'Customer Accepted Proposal', '<div>Hi<br /> <br />Client <strong>{proposal_proposal_to}</strong> accepted the following proposal:<br /> <br /><strong>Number:</strong> {proposal_number}<br /><strong>Subject</strong>: {proposal_subject}<br /><strong>Total</strong>: {proposal_total}<br /> <br />View the proposal on the following link: <a href=\"{proposal_link}\">{proposal_number}</a><br /> <br />Kind Regards,<br />{email_signature}</div>\r\n<div>&nbsp;</div>\r\n<div>&nbsp;</div>\r\n<div>&nbsp;</div>', '{companyname} | CRM', '', 0, 1, 0),
(21, 'proposals', 'proposal-send-to-customer', 'english', 'Send Proposal to Customer', 'Proposal With Number {proposal_number} Created', 'Dear {proposal_proposal_to}<br /><br />Please find our attached proposal.<br /><br />This proposal is valid until: {proposal_open_till}<br />You can view the proposal on the following link: <a href=\"{proposal_link}\">{proposal_number}</a><br /><br />Please don\'t hesitate to comment online if you have any questions.<br /><br />We look forward to your communication.<br /><br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(22, 'proposals', 'proposal-client-declined', 'english', 'Customer Action - Declined (Sent to Staff)', 'Client Declined Proposal', 'Hi<br /> <br />Customer <strong>{proposal_proposal_to}</strong> declined the proposal <strong>{proposal_subject}</strong><br /> <br />View the proposal on the following link <a href=\"{proposal_link}\">{proposal_number}</a>&nbsp;or from the admin area.<br /> <br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(23, 'proposals', 'proposal-client-thank-you', 'english', 'Thank You Email (Sent to Customer After Accept)', 'Thank for you accepting proposal', 'Dear {proposal_proposal_to}<br /> <br />Thank for for accepting the proposal.<br /> <br />We look forward to doing business with you.<br /> <br />We will contact you as soon as possible<br /> <br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(24, 'proposals', 'proposal-comment-to-client', 'english', 'New Comment  (Sent to Customer/Lead)', 'New Proposal Comment', 'Dear {proposal_proposal_to}<br /> <br />A new comment has been made on the following proposal: <strong>{proposal_number}</strong><br /> <br />You can view and reply to the comment on the following link: <a href=\"{proposal_link}\">{proposal_number}</a><br /> <br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(25, 'proposals', 'proposal-comment-to-admin', 'english', 'New Comment (Sent to Staff) ', 'New Proposal Comment', 'Hi<br /> <br />A new comment has been made to the proposal <strong>{proposal_subject}</strong><br /> <br />You can view and reply to the comment on the following link: <a href=\"{proposal_link}\">{proposal_number}</a>&nbsp;or from the admin area.<br /> <br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(26, 'estimate', 'estimate-thank-you-to-customer', 'english', 'Thank You Email (Sent to Customer After Accept)', 'Thank for you accepting estimate', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank for for accepting the estimate.</span><br /> <br /><span style=\"font-size: 12pt;\">We look forward to doing business with you.</span><br /> <br /><span style=\"font-size: 12pt;\">We will contact you as soon as possible.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(27, 'tasks', 'task-deadline-notification', 'english', 'Task Deadline Reminder - Sent to Assigned Members', 'Task Deadline Reminder', 'Hi {staff_firstname}&nbsp;{staff_lastname}<br /><br />This is an automated email from {companyname}.<br /><br />The task <strong>{task_name}</strong> deadline is on <strong>{task_duedate}</strong>. <br />This task is still not finished.<br /><br />You can view the task on the following link: <a href=\"{task_link}\">{task_name}</a><br /><br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(28, 'contract', 'send-contract', 'english', 'Send Contract to Customer', 'Contract - {contract_subject}', '<p><span style=\"font-size: 12pt;\">Hi&nbsp;{contact_firstname}&nbsp;{contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the <a href=\"{contract_link}\">{contract_subject}</a> attached.<br /><br />Description: {contract_description}<br /><br /></span><span style=\"font-size: 12pt;\">Looking forward to hear from you.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
(29, 'invoice', 'invoice-payment-recorded-to-staff', 'english', 'Invoice Payment Recorded (Sent to Staff)', 'New Invoice Payment', '<span style=\"font-size: 12pt;\">Hi</span><br /><br /><span style=\"font-size: 12pt;\">Customer recorded payment for invoice <strong># {invoice_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the invoice on the following link: <a href=\"{invoice_link}\">{invoice_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(30, 'ticket', 'auto-close-ticket', 'english', 'Auto Close Ticket', 'Ticket Auto Closed', '<p><span style=\"font-size: 12pt;\">Hi {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Ticket {ticket_subject} has been auto close due to inactivity.</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Ticket #</strong>: <a href=\"{ticket_public_url}\">{ticket_id}</a></span><br /><span style=\"font-size: 12pt;\"><strong>Department</strong>: {ticket_department}</span><br /><span style=\"font-size: 12pt;\"><strong>Priority:</strong> {ticket_priority}</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
(31, 'project', 'new-project-discussion-created-to-staff', 'english', 'New Project Discussion (Sent to Project Members)', 'New Project Discussion Created - {project_name}', '<p>Hi {staff_firstname}<br /><br />New project discussion created from <strong>{discussion_creator}</strong><br /><br /><strong>Subject:</strong> {discussion_subject}<br /><strong>Description:</strong> {discussion_description}<br /><br />You can view the discussion on the following link: <a href=\"{discussion_link}\">{discussion_subject}</a><br /><br />Kind Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(32, 'project', 'new-project-discussion-created-to-customer', 'english', 'New Project Discussion (Sent to Customer Contacts)', 'New Project Discussion Created - {project_name}', '<p>Hello {contact_firstname} {contact_lastname}<br /><br />New project discussion created from <strong>{discussion_creator}</strong><br /><br /><strong>Subject:</strong> {discussion_subject}<br /><strong>Description:</strong> {discussion_description}<br /><br />You can view the discussion on the following link: <a href=\"{discussion_link}\">{discussion_subject}</a><br /><br />Kind Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(33, 'project', 'new-project-file-uploaded-to-customer', 'english', 'New Project File(s) Uploaded (Sent to Customer Contacts)', 'New Project File(s) Uploaded - {project_name}', '<p>Hello {contact_firstname} {contact_lastname}<br /><br />New project file is uploaded on <strong>{project_name}</strong> from <strong>{file_creator}</strong><br /><br />You can view the project on the following link: <a href=\"{project_link}\">{project_name}</a><br /><br />To view the file in our CRM you can click on the following link: <a href=\"{discussion_link}\">{discussion_subject}</a><br /><br />Kind Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(34, 'project', 'new-project-file-uploaded-to-staff', 'english', 'New Project File(s) Uploaded (Sent to Project Members)', 'New Project File(s) Uploaded - {project_name}', '<p>Hello&nbsp;{staff_firstname}</p>\r\n<p>New project&nbsp;file is uploaded on&nbsp;<strong>{project_name}</strong> from&nbsp;<strong>{file_creator}</strong></p>\r\n<p>You can view the project on the following link: <a href=\"{project_link}\">{project_name}<br /></a><br />To view&nbsp;the file you can click on the following link: <a href=\"{discussion_link}\">{discussion_subject}</a></p>\r\n<p>Kind Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(35, 'project', 'new-project-discussion-comment-to-customer', 'english', 'New Discussion Comment  (Sent to Customer Contacts)', 'New Discussion Comment', '<p><span style=\"font-size: 12pt;\">Hello {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">New discussion comment has been made on <strong>{discussion_subject}</strong> from <strong>{comment_creator}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>Discussion subject:</strong> {discussion_subject}</span><br /><span style=\"font-size: 12pt;\"><strong>Comment</strong>: {discussion_comment}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the discussion on the following link: <a href=\"{discussion_link}\">{discussion_subject}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
(36, 'project', 'new-project-discussion-comment-to-staff', 'english', 'New Discussion Comment (Sent to Project Members)', 'New Discussion Comment', '<p>Hi {staff_firstname}<br /><br />New discussion comment has been made on <strong>{discussion_subject}</strong> from <strong>{comment_creator}</strong><br /><br /><strong>Discussion subject:</strong> {discussion_subject}<br /><strong>Comment:</strong> {discussion_comment}<br /><br />You can view the discussion on the following link: <a href=\"{discussion_link}\">{discussion_subject}</a><br /><br />Kind Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(37, 'project', 'staff-added-as-project-member', 'english', 'Staff Added as Project Member', 'New project assigned to you', '<p>Hi {staff_firstname}<br /><br />New project has been assigned to you.<br /><br />You can view the project on the following link <a href=\"{project_link}\">{project_name}</a><br /><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(38, 'estimate', 'estimate-expiry-reminder', 'english', 'Estimate Expiration Reminder', 'Estimate Expiration Reminder', '<p><span style=\"font-size: 12pt;\">Hello {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">The estimate with <strong># {estimate_number}</strong> will expire on <strong>{estimate_expirydate}</strong></span><br /><br /><span style=\"font-size: 12pt;\">You can view the estimate on the following link: <a href=\"{estimate_link}\">{estimate_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
(39, 'proposals', 'proposal-expiry-reminder', 'english', 'Proposal Expiration Reminder', 'Proposal Expiration Reminder', '<p>Hello {proposal_proposal_to}<br /><br />The proposal {proposal_number}&nbsp;will expire on <strong>{proposal_open_till}</strong><br /><br />You can view the proposal on the following link: <a href=\"{proposal_link}\">{proposal_number}</a><br /><br />Kind Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(40, 'staff', 'new-staff-created', 'english', 'New Staff Created (Welcome Email)', 'You are added as staff member', 'Hi {staff_firstname}<br /><br />You are added as member on our CRM.<br /><br />Please use the following logic credentials:<br /><br /><strong>Email:</strong> {staff_email}<br /><strong>Password:</strong> {password}<br /><br />Click <a href=\"{admin_url}\">here </a>to login in the dashboard.<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(41, 'client', 'contact-forgot-password', 'english', 'Forgot Password', 'Create New Password', '<h2>Create a new password</h2>\r\nForgot your password?<br /> To create a new password, just follow this link:<br /> <br /><a href=\"{reset_password_url}\">Reset Password</a><br /> <br /> You received this email, because it was requested by a {companyname}&nbsp;user. This is part of the procedure to create a new password on the system. If you DID NOT request a new password then please ignore this email and your password will remain the same. <br /><br /> {email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(42, 'client', 'contact-password-reseted', 'english', 'Password Reset - Confirmation', 'Your password has been changed', '<strong><span style=\"font-size: 14pt;\">You have changed your password.</span><br /></strong><br /> Please, keep it in your records so you don\'t forget it.<br /> <br /> Your email address for login is: {contact_email}<br /><br />If this wasnt you, please contact us.<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(43, 'client', 'contact-set-password', 'english', 'Set New Password', 'Set new password on {companyname} ', '<h2><span style=\"font-size: 14pt;\">Setup your new password on {companyname}</span></h2>\r\nPlease use the following link to set up your new password:<br /><br /><a href=\"{set_password_url}\">Set new password</a><br /><br />Keep it in your records so you don\'t forget it.<br /><br />Please set your new password in <strong>48 hours</strong>. After that, you won\'t be able to set your password because this link will expire.<br /><br />You can login at: <a href=\"{crm_url}\">{crm_url}</a><br />Your email address for login: {contact_email}<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(44, 'staff', 'staff-forgot-password', 'english', 'Forgot Password', 'Create New Password', '<h2><span style=\"font-size: 14pt;\">Create a new password</span></h2>\r\nForgot your password?<br /> To create a new password, just follow this link:<br /> <br /><a href=\"{reset_password_url}\">Reset Password</a><br /> <br /> You received this email, because it was requested by a <strong>{companyname}</strong>&nbsp;user. This is part of the procedure to create a new password on the system. If you DID NOT request a new password then please ignore this email and your password will remain the same. <br /><br /> {email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(45, 'staff', 'staff-password-reseted', 'english', 'Password Reset - Confirmation', 'Your password has been changed', '<span style=\"font-size: 14pt;\"><strong>You have changed your password.<br /></strong></span><br /> Please, keep it in your records so you don\'t forget it.<br /> <br /> Your email address for login is: {staff_email}<br /><br /> If this wasnt you, please contact us.<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(46, 'project', 'assigned-to-project', 'english', 'New Project Created (Sent to Customer Contacts)', 'New Project Created', '<p>Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}</p>\r\n<p>New project is assigned to your company.<br /><br /><strong>Project Name:</strong>&nbsp;{project_name}<br /><strong>Project Start Date:</strong>&nbsp;{project_start_date}</p>\r\n<p>You can view the project on the following link:&nbsp;<a href=\"{project_link}\">{project_name}</a></p>\r\n<p>We are looking forward hearing from you.<br /><br />Kind Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(47, 'tasks', 'task-added-attachment-to-contacts', 'english', 'New Attachment(s) on Task (Sent to Customer Contacts)', 'New Attachment on Task - {task_name}', '<span>Hi {contact_firstname} {contact_lastname}</span><br /><br /><strong>{task_user_take_action}</strong><span> added an attachment on the following task:</span><br /><br /><strong>Name:</strong><span> {task_name}</span><br /><br /><span>You can view the task on the following link: </span><a href=\"{task_link}\">{task_name}</a><br /><br /><span>Kind Regards,</span><br /><span>{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(48, 'tasks', 'task-commented-to-contacts', 'english', 'New Comment on Task (Sent to Customer Contacts)', 'New Comment on Task - {task_name}', '<span>Dear {contact_firstname} {contact_lastname}</span><br /><br /><span>A comment has been made on the following task:</span><br /><br /><strong>Name:</strong><span> {task_name}</span><br /><strong>Comment:</strong><span> {task_comment}</span><br /><br /><span>You can view the task on the following link: </span><a href=\"{task_link}\">{task_name}</a><br /><br /><span>Kind Regards,</span><br /><span>{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(49, 'leads', 'new-lead-assigned', 'english', 'New Lead Assigned to Staff Member', 'New lead assigned to you', '<p>Hello {lead_assigned}<br /><br />New lead is assigned to you.<br /><br /><strong>Lead Name:</strong>&nbsp;{lead_name}<br /><strong>Lead Email:</strong>&nbsp;{lead_email}<br /><br />You can view the lead on the following link: <a href=\"{lead_link}\">{lead_name}</a><br /><br />Kind Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(50, 'client', 'client-statement', 'english', 'Statement - Account Summary', 'Account Statement from {statement_from} to {statement_to}', 'Dear {contact_firstname} {contact_lastname}, <br /><br />Its been a great experience working with you.<br /><br />Attached with this email is a list of all transactions for the period between {statement_from} to {statement_to}<br /><br />For your information your account balance due is total:&nbsp;{statement_balance_due}<br /><br />Please contact us if you need more information.<br /> <br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(51, 'ticket', 'ticket-assigned-to-admin', 'english', 'New Ticket Assigned (Sent to Staff)', 'New support ticket has been assigned to you', '<p><span style=\"font-size: 12pt;\">Hi</span></p>\r\n<p><span style=\"font-size: 12pt;\">A new support ticket&nbsp;has been assigned to you.</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Subject</strong>: {ticket_subject}</span><br /><span style=\"font-size: 12pt;\"><strong>Department</strong>: {ticket_department}</span><br /><span style=\"font-size: 12pt;\"><strong>Priority</strong>: {ticket_priority}</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Ticket message:</strong></span><br /><span style=\"font-size: 12pt;\">{ticket_message}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the ticket on the following link: <a href=\"{ticket_url}\">#{ticket_id}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
(52, 'client', 'new-client-registered-to-admin', 'english', 'New Customer Registration (Sent to admins)', 'New Customer Registration', 'Hello.<br /><br />New customer registration on your customer portal:<br /><br /><strong>Firstname:</strong>&nbsp;{contact_firstname}<br /><strong>Lastname:</strong>&nbsp;{contact_lastname}<br /><strong>Company:</strong>&nbsp;{client_company}<br /><strong>Email:</strong>&nbsp;{contact_email}<br /><br />Best Regards', '{companyname} | CRM', '', 0, 1, 0),
(53, 'leads', 'new-web-to-lead-form-submitted', 'english', 'Web to lead form submitted - Sent to lead', '{lead_name} - We Received Your Request', 'Hello {lead_name}.<br /><br /><strong>Your request has been received.</strong><br /><br />This email is to let you know that we received your request and we will get back to you as soon as possible with more information.<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 0, 0),
(54, 'staff', 'two-factor-authentication', 'english', 'Two Factor Authentication', 'Confirm Your Login', '<p>Hi {staff_firstname}</p>\r\n<p style=\"text-align: left;\">You received this email because you have enabled two factor authentication in your account.<br />Use the following code to confirm your login:</p>\r\n<p style=\"text-align: left;\"><span style=\"font-size: 18pt;\"><strong>{two_factor_auth_code}<br /><br /></strong><span style=\"font-size: 12pt;\">{email_signature}</span><strong><br /><br /><br /><br /></strong></span></p>', '{companyname} | CRM', '', 0, 1, 0),
(55, 'project', 'project-finished-to-customer', 'english', 'Project Marked as Finished (Sent to Customer Contacts)', 'Project Marked as Finished', '<p>Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}</p>\r\n<p>You are receiving this email because project&nbsp;<strong>{project_name}</strong> has been marked as finished. This project is assigned under your company and we just wanted to keep you up to date.<br /><br />You can view the project on the following link:&nbsp;<a href=\"{project_link}\">{project_name}</a></p>\r\n<p>If you have any questions don\'t hesitate to contact us.<br /><br />Kind Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(56, 'credit_note', 'credit-note-send-to-client', 'english', 'Send Credit Note To Email', 'Credit Note With Number #{credit_note_number} Created', 'Dear&nbsp;{contact_firstname}&nbsp;{contact_lastname}<br /><br />We have attached the credit note with number <strong>#{credit_note_number} </strong>for your reference.<br /><br /><strong>Date:</strong>&nbsp;{credit_note_date}<br /><strong>Total Amount:</strong>&nbsp;{credit_note_total}<br /><br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(57, 'tasks', 'task-status-change-to-staff', 'english', 'Task Status Changed (Sent to Staff)', 'Task Status Changed', '<span style=\"font-size: 12pt;\">Hi {staff_firstname}</span><br /><br /><span style=\"font-size: 12pt;\"><strong>{task_user_take_action}</strong> marked task as <strong>{task_status}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>Name:</strong> {task_name}</span><br /><span style=\"font-size: 12pt;\"><strong>Due date:</strong> {task_duedate}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the task on the following link: <a href=\"{task_link}\">{task_name}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(58, 'tasks', 'task-status-change-to-contacts', 'english', 'Task Status Changed (Sent to Customer Contacts)', 'Task Status Changed', '<span style=\"font-size: 12pt;\">Hi {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\"><strong>{task_user_take_action}</strong> marked task as <strong>{task_status}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>Name:</strong> {task_name}</span><br /><span style=\"font-size: 12pt;\"><strong>Due date:</strong> {task_duedate}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the task on the following link: <a href=\"{task_link}\">{task_name}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(59, 'staff', 'reminder-email-staff', 'english', 'Staff Reminder Email', 'You Have a New Reminder!', '<p>Hello&nbsp;{staff_firstname}<br /><br /><strong>You have a new reminder&nbsp;linked to&nbsp;{staff_reminder_relation_name}!<br /><br />Reminder description:</strong><br />{staff_reminder_description}<br /><br />Click <a href=\"{staff_reminder_relation_link}\">here</a> to view&nbsp;<a href=\"{staff_reminder_relation_link}\">{staff_reminder_relation_name}</a><br /><br />Best Regards<br /><br /></p>', '{companyname} | CRM', '', 0, 1, 0),
(60, 'contract', 'contract-comment-to-client', 'english', 'New Comment  (Sent to Customer Contacts)', 'New Contract Comment', 'Dear {contact_firstname} {contact_lastname}<br /> <br />A new comment has been made on the following contract: <strong>{contract_subject}</strong><br /> <br />You can view and reply to the comment on the following link: <a href=\"{contract_link}\">{contract_subject}</a><br /> <br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(61, 'contract', 'contract-comment-to-admin', 'english', 'New Comment (Sent to Staff) ', 'New Contract Comment', 'Hi {staff_firstname}<br /><br />A new comment has been made to the contract&nbsp;<strong>{contract_subject}</strong><br /><br />You can view and reply to the comment on the following link: <a href=\"{contract_link}\">{contract_subject}</a>&nbsp;or from the admin area.<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(62, 'subscriptions', 'send-subscription', 'english', 'Send Subscription to Customer', 'Subscription Created', 'Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}<br /><br />We have prepared the subscription&nbsp;<strong>{subscription_name}</strong> for your company.<br /><br />Click <a href=\"{subscription_link}\">here</a> to review the subscription and subscribe.<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(63, 'subscriptions', 'subscription-payment-failed', 'english', 'Subscription Payment Failed', 'Your most recent invoice payment failed', 'Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}<br /><br br=\"\" />Unfortunately, your most recent invoice payment for&nbsp;<strong>{subscription_name}</strong> was declined.<br /><br />This could be due to a change in your card number, your card expiring,<br />cancellation of your credit card, or the card issuer not recognizing the<br />payment and therefore taking action to prevent it.<br /><br />Please update your payment information as soon as possible by logging in here:<br /><a href=\"{crm_url}/login\">{crm_url}/login</a><br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(64, 'subscriptions', 'subscription-canceled', 'english', 'Subscription Canceled (Sent to customer primary contact)', 'Your subscription has been canceled', 'Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}<br /><br />Your subscription&nbsp;<strong>{subscription_name} </strong>has been canceled, if you have any questions don\'t hesitate to contact us.<br /><br />It was a pleasure doing business with you.<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(65, 'subscriptions', 'subscription-payment-succeeded', 'english', 'Subscription Payment Succeeded (Sent to customer primary contact)', 'Subscription  Payment Receipt - {subscription_name}', 'Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}<br /><br />This email is to let you know that we received your payment for subscription&nbsp;<strong>{subscription_name}&nbsp;</strong>of&nbsp;<strong><span>{payment_total}<br /><br /></span></strong>The invoice associated with it is now with status&nbsp;<strong>{invoice_status}<br /></strong><br />Thank you for your confidence.<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(66, 'contract', 'contract-expiration-to-staff', 'english', 'Contract Expiration Reminder (Sent to Staff)', 'Contract Expiration Reminder', 'Hi {staff_firstname}<br /><br /><span style=\"font-size: 12pt;\">This is a reminder that the following contract will expire soon:</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Subject:</strong> {contract_subject}</span><br /><span style=\"font-size: 12pt;\"><strong>Description:</strong> {contract_description}</span><br /><span style=\"font-size: 12pt;\"><strong>Date Start:</strong> {contract_datestart}</span><br /><span style=\"font-size: 12pt;\"><strong>Date End:</strong> {contract_dateend}</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(67, 'gdpr', 'gdpr-removal-request', 'english', 'Removal Request From Contact (Sent to administrators)', 'Data Removal Request Received', 'Hello&nbsp;{staff_firstname}&nbsp;{staff_lastname}<br /><br />Data removal has been requested by&nbsp;{contact_firstname} {contact_lastname}<br /><br />You can review this request and take proper actions directly from the admin area.', '{companyname} | CRM', '', 0, 1, 0),
(68, 'gdpr', 'gdpr-removal-request-lead', 'english', 'Removal Request From Lead (Sent to administrators)', 'Data Removal Request Received', 'Hello&nbsp;{staff_firstname}&nbsp;{staff_lastname}<br /><br />Data removal has been requested by {lead_name}<br /><br />You can review this request and take proper actions directly from the admin area.<br /><br />To view the lead inside the admin area click here:&nbsp;<a href=\"{lead_link}\">{lead_link}</a>', '{companyname} | CRM', '', 0, 1, 0),
(69, 'client', 'client-registration-confirmed', 'english', 'Customer Registration Confirmed', 'Your registration is confirmed', '<p>Dear {contact_firstname} {contact_lastname}<br /><br />We just wanted to let you know that your registration at&nbsp;{companyname} is successfully confirmed and your account is now active.<br /><br />You can login at&nbsp;<a href=\"{crm_url}\">{crm_url}</a> with the email and password you provided during registration.<br /><br />Please contact us if you need any help.<br /><br />Kind Regards, <br />{email_signature}</p>\r\n<p><br />(This is an automated email, so please don\'t reply to this email address)</p>', '{companyname} | CRM', '', 0, 1, 0),
(70, 'contract', 'contract-signed-to-staff', 'english', 'Contract Signed (Sent to Staff)', 'Customer Signed a Contract', 'Hi {staff_firstname}<br /><br />A contract with subject&nbsp;<strong>{contract_subject} </strong>has been successfully signed by the customer.<br /><br />You can view the contract at the following link: <a href=\"{contract_link}\">{contract_subject}</a>&nbsp;or from the admin area.<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(71, 'subscriptions', 'customer-subscribed-to-staff', 'english', 'Customer Subscribed to a Subscription (Sent to administrators and subscription creator)', 'Customer Subscribed to a Subscription', 'The customer <strong>{client_company}</strong> subscribed to a subscription with name&nbsp;<strong>{subscription_name}</strong><br /><br /><strong>ID</strong>:&nbsp;{subscription_id}<br /><strong>Subscription name</strong>:&nbsp;{subscription_name}<br /><strong>Subscription description</strong>:&nbsp;{subscription_description}<br /><br />You can view the subscription by clicking <a href=\"{subscription_link}\">here</a><br />\r\n<div style=\"text-align: center;\"><span style=\"font-size: 10pt;\">&nbsp;</span></div>\r\nBest Regards,<br />{email_signature}<br /><br /><span style=\"font-size: 10pt;\"><span style=\"color: #999999;\">You are receiving this email because you are either administrator or you are creator of the subscription.</span></span>', '{companyname} | CRM', '', 0, 1, 0),
(72, 'client', 'contact-verification-email', 'english', 'Email Verification (Sent to Contact After Registration)', 'Please Verify Your Email Address', '<p>Hello&nbsp;{contact_firstname}<br /><br />Please click the button below to verify your email address.<br /><br /><a href=\"{email_verification_url}\">Verify Email Address</a><br /><br />If you did not create an account, no further action is required</p>\r\n<p><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(73, 'client', 'new-customer-profile-file-uploaded-to-staff', 'english', 'New Customer Profile File(s) Uploaded (Sent to Staff)', 'Customer Uploaded New File(s) in Profile', 'Hi!<br /><br />New file(s) is uploaded into the customer ({client_company}) profile by&nbsp;{contact_firstname}<br /><br />You can check the uploaded files into the admin area by clicking <a href=\"{customer_profile_files_admin_link}\">here</a> or at the following link:&nbsp;{customer_profile_files_admin_link}<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(74, 'staff', 'event-notification-to-staff', 'english', 'Event Notification (Calendar)', 'Upcoming Event - {event_title}', 'Hi {staff_firstname}! <br /><br />This is a reminder for event <a href=\\\"{event_link}\\\">{event_title}</a> scheduled at {event_start_date}. <br /><br />Regards.', '', '', 0, 1, 0),
(75, 'subscriptions', 'subscription-payment-requires-action', 'english', 'Credit Card Authorization Required - SCA', 'Important: Confirm your subscription {subscription_name} payment', '<p>Hello {contact_firstname}</p>\r\n<p><strong>Your bank sometimes requires an additional step to make sure an online transaction was authorized.</strong><br /><br />Because of European regulation to protect consumers, many online payments now require two-factor authentication. Your bank ultimately decides when authentication is required to confirm a payment, but you may notice this step when you start paying for a service or when the cost changes.<br /><br />In order to pay the subscription <strong>{subscription_name}</strong>, you will need to&nbsp;confirm your payment by clicking on the follow link: <strong><a href=\"{subscription_authorize_payment_link}\">{subscription_authorize_payment_link}</a></strong><br /><br />To view the subscription, please click at the following link: <a href=\"{subscription_link}\"><span>{subscription_link}</span></a><br />or you can login in our dedicated area here: <a href=\"{crm_url}/login\">{crm_url}/login</a> in case you want to update your credit card or view the subscriptions you are subscribed.<br /><br />Best Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0);
INSERT INTO `tblemailtemplates` (`emailtemplateid`, `type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
(76, 'invoice', 'invoice-due-notice', 'english', 'Invoice Due Notice', 'Your {invoice_number} will be due soon', '<span style=\"font-size: 12pt;\">Hi {contact_firstname} {contact_lastname}<br /><br /></span>You invoice <span style=\"font-size: 12pt;\"><strong># {invoice_number} </strong>will be due on <strong>{invoice_duedate}</strong></span><br /><br /><span style=\"font-size: 12pt;\">You can view the invoice on the following link: <a href=\"{invoice_link}\">{invoice_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(77, 'estimate_request', 'estimate-request-submitted-to-staff', 'english', 'Estimate Request Submitted (Sent to Staff)', 'New Estimate Request Submitted', '<span> Hello,&nbsp;</span><br /><br />{estimate_request_email} submitted an estimate request via the {estimate_request_form_name} form.<br /><br />You can view the request at the following link: <a href=\"{estimate_request_link}\">{estimate_request_link}</a><br /><br />==<br /><br />{estimate_request_submitted_data}<br /><br />Kind Regards,<br /><span>{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(78, 'estimate_request', 'estimate-request-assigned', 'english', 'Estimate Request Assigned (Sent to Staff)', 'New Estimate Request Assigned', '<span> Hello {estimate_request_assigned},&nbsp;</span><br /><br />Estimate request #{estimate_request_id} has been assigned to you.<br /><br />You can view the request at the following link: <a href=\"{estimate_request_link}\">{estimate_request_link}</a><br /><br />Kind Regards,<br /><span>{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
(79, 'estimate_request', 'estimate-request-received-to-user', 'english', 'Estimate Request Received (Sent to User)', 'Estimate Request Received', 'Hello,<br /><br /><strong>Your request has been received.</strong><br /><br />This email is to let you know that we received your request and we will get back to you as soon as possible with more information.<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 0, 0),
(80, 'notifications', 'non-billed-tasks-reminder', 'english', 'Non-billed tasks reminder (sent to selected staff members)', 'Action required: Completed tasks are not billed', 'Hello {staff_firstname}<br><br>The following tasks are marked as complete but not yet billed:<br><br>{unbilled_tasks_list}<br><br>Kind Regards,<br><br>{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(81, 'invoice', 'invoices-batch-payments', 'english', 'Invoices Payments Recorded in Batch (Sent to Customer)', 'We have received your payments', 'Hello {contact_firstname} {contact_lastname}<br><br>Thank you for the payments. Please find the payments details below:<br><br>{batch_payments_list}<br><br>We are looking forward working with you.<br><br>Kind Regards,<br><br>{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
(82, 'contract', 'contract-sign-reminder', 'english', 'Contract Sign Reminder (Sent to Customer)', 'Contract Sign Reminder', '<p>Hello {contact_firstname} {contact_lastname}<br /><br />This is a reminder to review and sign the contract:<a href=\"{contract_link}\">{contract_subject}</a></p><p>You can view and sign by visiting: <a href=\"{contract_link}\">{contract_subject}</a></p><p><br />We are looking forward working with you.<br /><br />Kind Regards,<br /><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
(83, 'purchase_order', 'purchase-order-to-contact', 'english', 'Purchase Order (Sent to contact)', 'Purchase Order', '<span style=\\\"font-size: 12pt;\\\"> Hello !. </span><br /><br /><span style=\\\"font-size: 12pt;\\\"> We would like to share with you a link of Purchase Order information with the number {po_number} </span><br /><br /><span style=\\\"font-size: 12pt;\\\"><br />Please click on the link to view information: {public_link}\r\n  </span><br /><br />', '{companyname} | CRM', NULL, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tblestimates`
--

CREATE TABLE `tblestimates` (
  `id` int(11) NOT NULL,
  `sent` tinyint(1) NOT NULL DEFAULT 0,
  `datesend` datetime DEFAULT NULL,
  `clientid` int(11) NOT NULL,
  `deleted_customer_name` varchar(100) DEFAULT NULL,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `number` int(11) NOT NULL,
  `prefix` varchar(50) DEFAULT NULL,
  `number_format` int(11) NOT NULL DEFAULT 0,
  `formatted_number` varchar(100) DEFAULT NULL,
  `hash` varchar(32) DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `date` date NOT NULL,
  `expirydate` date DEFAULT NULL,
  `currency` int(11) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `total_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `adjustment` decimal(15,2) DEFAULT NULL,
  `addedfrom` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `clientnote` mediumtext DEFAULT NULL,
  `adminnote` mediumtext DEFAULT NULL,
  `discount_percent` decimal(15,2) DEFAULT 0.00,
  `discount_total` decimal(15,2) DEFAULT 0.00,
  `discount_type` varchar(30) DEFAULT NULL,
  `invoiceid` int(11) DEFAULT NULL,
  `invoiced_date` datetime DEFAULT NULL,
  `terms` mediumtext DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `sale_agent` int(11) NOT NULL DEFAULT 0,
  `billing_street` varchar(200) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_zip` varchar(100) DEFAULT NULL,
  `billing_country` int(11) DEFAULT NULL,
  `shipping_street` varchar(200) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_zip` varchar(100) DEFAULT NULL,
  `shipping_country` int(11) DEFAULT NULL,
  `include_shipping` tinyint(1) NOT NULL,
  `show_shipping_on_estimate` tinyint(1) NOT NULL DEFAULT 1,
  `show_quantity_as` int(11) NOT NULL DEFAULT 1,
  `pipeline_order` int(11) DEFAULT 1,
  `is_expiry_notified` int(11) NOT NULL DEFAULT 0,
  `acceptance_firstname` varchar(50) DEFAULT NULL,
  `acceptance_lastname` varchar(50) DEFAULT NULL,
  `acceptance_email` varchar(100) DEFAULT NULL,
  `acceptance_date` datetime DEFAULT NULL,
  `acceptance_ip` varchar(40) DEFAULT NULL,
  `signature` varchar(40) DEFAULT NULL,
  `short_link` varchar(100) DEFAULT NULL,
  `approval_request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblestimate_requests`
--

CREATE TABLE `tblestimate_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(100) NOT NULL,
  `submission` longtext NOT NULL,
  `last_status_change` datetime DEFAULT NULL,
  `date_estimated` datetime DEFAULT NULL,
  `from_form_id` int(11) DEFAULT NULL,
  `assigned` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `default_language` int(11) NOT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblestimate_request_forms`
--

CREATE TABLE `tblestimate_request_forms` (
  `id` int(10) UNSIGNED NOT NULL,
  `form_key` varchar(32) NOT NULL,
  `type` varchar(100) NOT NULL,
  `name` varchar(191) NOT NULL,
  `form_data` longtext DEFAULT NULL,
  `recaptcha` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `submit_btn_name` varchar(100) DEFAULT NULL,
  `submit_btn_bg_color` varchar(10) DEFAULT '#84c529',
  `submit_btn_text_color` varchar(10) DEFAULT '#ffffff',
  `success_submit_msg` mediumtext DEFAULT NULL,
  `submit_action` int(11) DEFAULT 0,
  `submit_redirect_url` longtext DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `dateadded` datetime DEFAULT NULL,
  `notify_type` varchar(100) DEFAULT NULL,
  `notify_ids` longtext DEFAULT NULL,
  `responsible` int(11) DEFAULT NULL,
  `notify_request_submitted` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblestimate_request_status`
--

CREATE TABLE `tblestimate_request_status` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `statusorder` int(11) DEFAULT NULL,
  `color` varchar(10) DEFAULT NULL,
  `flag` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblestimate_request_status`
--

INSERT INTO `tblestimate_request_status` (`id`, `name`, `statusorder`, `color`, `flag`) VALUES
(1, 'Cancelled', 1, '#808080', 'cancelled'),
(2, 'Processing', 2, '#007bff', 'processing'),
(3, 'Completed', 3, '#28a745', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `tblevents`
--

CREATE TABLE `tblevents` (
  `eventid` int(11) NOT NULL,
  `title` longtext NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `userid` int(11) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime DEFAULT NULL,
  `public` int(11) NOT NULL DEFAULT 0,
  `color` varchar(10) DEFAULT NULL,
  `isstartnotified` tinyint(1) NOT NULL DEFAULT 0,
  `reminder_before` int(11) NOT NULL DEFAULT 0,
  `reminder_before_type` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblexpenses`
--

CREATE TABLE `tblexpenses` (
  `id` int(11) NOT NULL,
  `category` int(11) NOT NULL,
  `currency` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `tax` int(11) DEFAULT NULL,
  `tax2` int(11) NOT NULL DEFAULT 0,
  `reference_no` varchar(100) DEFAULT NULL,
  `note` mediumtext DEFAULT NULL,
  `expense_name` varchar(191) DEFAULT NULL,
  `clientid` int(11) NOT NULL,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `billable` int(11) DEFAULT 0,
  `invoiceid` int(11) DEFAULT NULL,
  `paymentmode` varchar(50) DEFAULT NULL,
  `date` date NOT NULL,
  `recurring_type` varchar(10) DEFAULT NULL,
  `repeat_every` int(11) DEFAULT NULL,
  `recurring` int(11) NOT NULL DEFAULT 0,
  `cycles` int(11) NOT NULL DEFAULT 0,
  `total_cycles` int(11) NOT NULL DEFAULT 0,
  `custom_recurring` int(11) NOT NULL DEFAULT 0,
  `last_recurring_date` date DEFAULT NULL,
  `create_invoice_billable` tinyint(1) DEFAULT NULL,
  `send_invoice_to_customer` tinyint(1) NOT NULL,
  `recurring_from` int(11) DEFAULT NULL,
  `dateadded` datetime NOT NULL,
  `addedfrom` int(11) NOT NULL,
  `vendor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblexpenses_categories`
--

CREATE TABLE `tblexpenses_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblfiles`
--

CREATE TABLE `tblfiles` (
  `id` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(20) NOT NULL,
  `file_name` varchar(191) NOT NULL,
  `filetype` varchar(40) DEFAULT NULL,
  `visible_to_customer` int(11) NOT NULL DEFAULT 0,
  `attachment_key` varchar(32) DEFAULT NULL,
  `external` varchar(40) DEFAULT NULL,
  `external_link` mediumtext DEFAULT NULL,
  `thumbnail_link` mediumtext DEFAULT NULL COMMENT 'For external usage',
  `staffid` int(11) NOT NULL,
  `contact_id` int(11) DEFAULT 0,
  `task_comment_id` int(11) NOT NULL DEFAULT 0,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblfilters`
--

CREATE TABLE `tblfilters` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `builder` mediumtext NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `identifier` varchar(191) NOT NULL,
  `is_shared` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblfilter_defaults`
--

CREATE TABLE `tblfilter_defaults` (
  `filter_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(11) NOT NULL,
  `identifier` varchar(191) NOT NULL,
  `view` varchar(191) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblform_questions`
--

CREATE TABLE `tblform_questions` (
  `questionid` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(20) DEFAULT NULL,
  `question` longtext NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `question_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblform_question_box`
--

CREATE TABLE `tblform_question_box` (
  `boxid` int(11) NOT NULL,
  `boxtype` varchar(10) NOT NULL,
  `questionid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblform_question_box_description`
--

CREATE TABLE `tblform_question_box_description` (
  `questionboxdescriptionid` int(11) NOT NULL,
  `description` longtext NOT NULL,
  `boxid` longtext NOT NULL,
  `questionid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblform_results`
--

CREATE TABLE `tblform_results` (
  `resultid` int(11) NOT NULL,
  `boxid` int(11) NOT NULL,
  `boxdescriptionid` int(11) DEFAULT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(20) DEFAULT NULL,
  `questionid` int(11) NOT NULL,
  `answer` mediumtext DEFAULT NULL,
  `resultsetid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblgdpr_requests`
--

CREATE TABLE `tblgdpr_requests` (
  `id` int(11) NOT NULL,
  `clientid` int(11) NOT NULL DEFAULT 0,
  `contact_id` int(11) NOT NULL DEFAULT 0,
  `lead_id` int(11) NOT NULL DEFAULT 0,
  `request_type` varchar(191) DEFAULT NULL,
  `status` varchar(40) DEFAULT NULL,
  `request_date` datetime NOT NULL,
  `request_from` varchar(150) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblgoods_delivery`
--

CREATE TABLE `tblgoods_delivery` (
  `id` int(11) UNSIGNED NOT NULL,
  `rel_type` int(11) DEFAULT NULL COMMENT 'type goods delivery',
  `rel_document` int(11) DEFAULT NULL COMMENT 'document id of goods delivery',
  `customer_code` text DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `to_` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL COMMENT 'the reason delivery',
  `staff_id` int(11) DEFAULT NULL COMMENT 'salesman',
  `date_c` date DEFAULT NULL,
  `date_add` date DEFAULT NULL,
  `goods_delivery_code` varchar(100) DEFAULT NULL COMMENT 'số chứng từ xuất kho',
  `warehouse_id` int(11) DEFAULT NULL,
  `total_money` varchar(200) DEFAULT NULL,
  `approval` int(11) DEFAULT 0 COMMENT 'status approval ',
  `addedfrom` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblgoods_delivery_detail`
--

CREATE TABLE `tblgoods_delivery_detail` (
  `id` int(11) UNSIGNED NOT NULL,
  `goods_delivery_id` int(11) NOT NULL,
  `commodity_code` varchar(100) DEFAULT NULL,
  `commodity_name` text DEFAULT NULL,
  `warehouse_id` text DEFAULT NULL,
  `unit_id` text DEFAULT NULL,
  `quantities` text DEFAULT NULL,
  `unit_price` varchar(100) DEFAULT NULL,
  `total_money` varchar(200) DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblgoods_receipt`
--

CREATE TABLE `tblgoods_receipt` (
  `id` int(11) UNSIGNED NOT NULL,
  `supplier_code` varchar(100) DEFAULT NULL,
  `supplier_name` text DEFAULT NULL,
  `deliver_name` text DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `pr_order_id` int(11) DEFAULT NULL COMMENT 'code puchase request agree',
  `date_c` date DEFAULT NULL,
  `date_add` date DEFAULT NULL,
  `goods_receipt_code` varchar(100) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `total_tax_money` varchar(100) DEFAULT NULL,
  `total_goods_money` varchar(100) DEFAULT NULL,
  `value_of_inventory` varchar(100) DEFAULT NULL,
  `total_money` varchar(100) DEFAULT NULL COMMENT 'total_money = total_tax_money +total_goods_money ',
  `addedfrom` int(11) DEFAULT NULL,
  `approval` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblgoods_receipt_detail`
--

CREATE TABLE `tblgoods_receipt_detail` (
  `id` int(11) UNSIGNED NOT NULL,
  `goods_receipt_id` int(11) NOT NULL,
  `commodity_code` varchar(100) DEFAULT NULL,
  `commodity_name` text DEFAULT NULL,
  `warehouse_id` text DEFAULT NULL,
  `unit_id` text DEFAULT NULL,
  `quantities` text DEFAULT NULL,
  `unit_price` varchar(100) DEFAULT NULL,
  `tax` varchar(100) DEFAULT NULL,
  `tax_money` varchar(100) DEFAULT NULL,
  `goods_money` varchar(100) DEFAULT NULL,
  `date_manufacture` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblgoods_transaction_detail`
--

CREATE TABLE `tblgoods_transaction_detail` (
  `id` int(11) UNSIGNED NOT NULL,
  `goods_receipt_id` int(11) DEFAULT NULL COMMENT 'id_goods_receipt_id or goods_delivery_id',
  `goods_id` int(11) NOT NULL COMMENT ' is id commodity',
  `old_quantity` varchar(100) DEFAULT NULL,
  `quantity` varchar(100) DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `commodity_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `status` int(2) DEFAULT NULL COMMENT '1:Goods receipt note 2:Goods delivery note'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblinventory_commodity_min`
--

CREATE TABLE `tblinventory_commodity_min` (
  `id` int(11) UNSIGNED NOT NULL,
  `commodity_id` int(11) NOT NULL,
  `commodity_code` varchar(100) DEFAULT NULL,
  `commodity_name` varchar(100) DEFAULT NULL,
  `inventory_number_min` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblinventory_manage`
--

CREATE TABLE `tblinventory_manage` (
  `id` int(11) UNSIGNED NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `commodity_id` int(11) NOT NULL,
  `inventory_number` varchar(100) DEFAULT NULL,
  `date_manufacture` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblinvoicepaymentrecords`
--

CREATE TABLE `tblinvoicepaymentrecords` (
  `id` int(11) NOT NULL,
  `invoiceid` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paymentmode` varchar(40) DEFAULT NULL,
  `paymentmethod` varchar(191) DEFAULT NULL,
  `date` date NOT NULL,
  `daterecorded` datetime NOT NULL,
  `note` mediumtext DEFAULT NULL,
  `transactionid` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblinvoices`
--

CREATE TABLE `tblinvoices` (
  `id` int(11) NOT NULL,
  `sent` tinyint(1) NOT NULL DEFAULT 0,
  `datesend` datetime DEFAULT NULL,
  `clientid` int(11) NOT NULL,
  `deleted_customer_name` varchar(100) DEFAULT NULL,
  `number` int(11) NOT NULL,
  `prefix` varchar(50) DEFAULT NULL,
  `number_format` int(11) NOT NULL DEFAULT 0,
  `formatted_number` varchar(100) DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `date` date NOT NULL,
  `duedate` date DEFAULT NULL,
  `currency` int(11) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `total_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `adjustment` decimal(15,2) DEFAULT NULL,
  `addedfrom` int(11) DEFAULT NULL,
  `hash` varchar(32) NOT NULL,
  `status` int(11) DEFAULT 1,
  `clientnote` mediumtext DEFAULT NULL,
  `adminnote` mediumtext DEFAULT NULL,
  `last_overdue_reminder` date DEFAULT NULL,
  `last_due_reminder` date DEFAULT NULL,
  `cancel_overdue_reminders` int(11) NOT NULL DEFAULT 0,
  `allowed_payment_modes` longtext DEFAULT NULL,
  `token` longtext DEFAULT NULL,
  `discount_percent` decimal(15,2) DEFAULT 0.00,
  `discount_total` decimal(15,2) DEFAULT 0.00,
  `discount_type` varchar(30) NOT NULL,
  `recurring` int(11) NOT NULL DEFAULT 0,
  `recurring_type` varchar(10) DEFAULT NULL,
  `custom_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `cycles` int(11) NOT NULL DEFAULT 0,
  `total_cycles` int(11) NOT NULL DEFAULT 0,
  `is_recurring_from` int(11) DEFAULT NULL,
  `last_recurring_date` date DEFAULT NULL,
  `terms` mediumtext DEFAULT NULL,
  `sale_agent` int(11) NOT NULL DEFAULT 0,
  `billing_street` varchar(200) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_zip` varchar(100) DEFAULT NULL,
  `billing_country` int(11) DEFAULT NULL,
  `shipping_street` varchar(200) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_zip` varchar(100) DEFAULT NULL,
  `shipping_country` int(11) DEFAULT NULL,
  `include_shipping` tinyint(1) NOT NULL,
  `show_shipping_on_invoice` tinyint(1) NOT NULL DEFAULT 1,
  `show_quantity_as` int(11) NOT NULL DEFAULT 1,
  `project_id` int(11) DEFAULT 0,
  `subscription_id` int(11) NOT NULL DEFAULT 0,
  `short_link` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblipms_approval_actions`
--

CREATE TABLE `tblipms_approval_actions` (
  `id` int(11) NOT NULL,
  `approval_request_id` int(11) NOT NULL,
  `actor_id` int(11) NOT NULL,
  `actor_name` varchar(200) DEFAULT NULL,
  `actor_role` varchar(100) DEFAULT NULL,
  `action` enum('submitted','approved','rejected','revision_requested','escalated','cancelled','reminder_sent') NOT NULL,
  `comments` text DEFAULT NULL,
  `acted_at` datetime NOT NULL,
  `stage_number` tinyint(4) DEFAULT 1,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblipms_approval_actions`
--

INSERT INTO `tblipms_approval_actions` (`id`, `approval_request_id`, `actor_id`, `actor_name`, `actor_role`, `action`, `comments`, `acted_at`, `stage_number`, `ip_address`) VALUES
(1, 1, 3, 'John  Doe', 'General Manager ', 'submitted', NULL, '2026-04-13 11:39:59', 1, '::1'),
(2, 2, 1, 'Mega Signs Ltd', 'General Manager ', 'submitted', NULL, '2026-04-13 11:39:59', 1, '::1'),
(3, 1, 1, 'Mega Signs Ltd', 'General Manager ', 'approved', 'Approved from dashboard', '2026-04-13 12:22:58', 1, '::1'),
(4, 2, 1, 'Mega Signs Ltd', 'General Manager ', 'approved', 'Approved from dashboard', '2026-04-13 12:23:39', 1, '::1'),
(5, 3, 1, 'Mega Signs Ltd', 'General Manager ', 'submitted', NULL, '2026-04-13 12:36:16', 1, '::1'),
(6, 3, 2, 'sam zarila', 'Sales Manager', 'revision_requested', 'Please revise and resubmit.', '2026-04-13 12:36:37', 1, '::1'),
(7, 4, 4, 'Alex Sales Executive Mayo', 'Sales Executive', 'submitted', NULL, '2026-04-13 13:21:41', 1, '::1'),
(8, 5, 4, 'Alex Sales Executive Mayo', 'Sales Executive', 'submitted', NULL, '2026-04-13 13:26:21', 1, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `tblipms_approval_notifications`
--

CREATE TABLE `tblipms_approval_notifications` (
  `id` int(11) NOT NULL,
  `approval_request_id` int(11) NOT NULL,
  `notification_type` enum('in_app','email','sms') NOT NULL,
  `sent_to_staff_id` int(11) NOT NULL,
  `sent_at` datetime NOT NULL,
  `status` enum('sent','failed') DEFAULT 'sent',
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblipms_approval_notifications`
--

INSERT INTO `tblipms_approval_notifications` (`id`, `approval_request_id`, `notification_type`, `sent_to_staff_id`, `sent_at`, `status`, `error_message`) VALUES
(1, 1, 'in_app', 1, '2026-04-13 11:39:59', 'sent', NULL),
(2, 1, 'email', 1, '2026-04-13 11:39:59', 'failed', 'send_simple_email returned false'),
(3, 1, 'sms', 1, '2026-04-13 11:39:59', 'failed', 'SMS not dispatched (filter approvals_sms_notify_approver)'),
(4, 2, 'in_app', 1, '2026-04-13 11:39:59', 'sent', NULL),
(5, 2, 'email', 1, '2026-04-13 11:39:59', 'failed', 'send_simple_email returned false'),
(6, 2, 'sms', 1, '2026-04-13 11:39:59', 'failed', 'SMS not dispatched (filter approvals_sms_notify_approver)'),
(7, 3, 'in_app', 2, '2026-04-13 12:36:16', 'sent', NULL),
(8, 3, 'email', 2, '2026-04-13 12:36:16', 'failed', 'send_simple_email returned false'),
(9, 3, 'sms', 2, '2026-04-13 12:36:16', 'failed', 'SMS not dispatched (filter approvals_sms_notify_approver)'),
(10, 4, 'in_app', 2, '2026-04-13 13:21:41', 'sent', NULL),
(11, 4, 'email', 2, '2026-04-13 13:21:41', 'failed', 'send_simple_email returned false'),
(12, 4, 'sms', 2, '2026-04-13 13:21:41', 'failed', 'SMS not dispatched (filter approvals_sms_notify_approver)'),
(13, 5, 'in_app', 2, '2026-04-13 13:26:21', 'sent', NULL),
(14, 5, 'email', 2, '2026-04-13 13:26:21', 'failed', 'send_simple_email returned false'),
(15, 5, 'sms', 2, '2026-04-13 13:26:21', 'failed', 'SMS not dispatched (filter approvals_sms_notify_approver)');

-- --------------------------------------------------------

--
-- Table structure for table `tblipms_approval_requests`
--

CREATE TABLE `tblipms_approval_requests` (
  `id` int(11) NOT NULL,
  `request_ref` varchar(30) NOT NULL,
  `document_type` enum('quotation','credit_note','journal_entry','payment','purchase_requisition') NOT NULL,
  `document_id` int(11) NOT NULL,
  `document_ref` varchar(50) DEFAULT NULL,
  `document_value` decimal(15,2) DEFAULT 0.00,
  `submitted_by` int(11) NOT NULL,
  `submitted_at` datetime NOT NULL,
  `current_approver_id` int(11) DEFAULT NULL,
  `current_approver_role` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected','revision_requested','escalated','cancelled') DEFAULT 'pending',
  `approval_stage` tinyint(4) DEFAULT 1,
  `total_stages` tinyint(4) DEFAULT 1,
  `sla_deadline` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblipms_approval_requests`
--

INSERT INTO `tblipms_approval_requests` (`id`, `request_ref`, `document_type`, `document_id`, `document_ref`, `document_value`, `submitted_by`, `submitted_at`, `current_approver_id`, `current_approver_role`, `status`, `approval_stage`, `total_stages`, `sla_deadline`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'APR-2026-00001', 'quotation', 2, 'PRO-000002', 6100000.00, 3, '2026-04-13 11:39:59', 1, 'General Manager ', 'approved', 1, 1, '2026-04-14 11:39:59', 'Auto-backfilled from sent proposal.', '2026-04-13 11:39:59', '2026-04-13 12:22:58'),
(2, 'APR-2026-00002', 'quotation', 1, 'PRO-000001', 6010000.00, 1, '2026-04-13 11:39:59', 1, 'General Manager ', 'approved', 1, 1, '2026-04-14 11:39:59', 'Auto-backfilled from sent proposal.', '2026-04-13 11:39:59', '2026-04-13 12:23:39'),
(3, 'APR-2026-00003', 'quotation', 4, 'PRO-000004', 1500000.00, 1, '2026-04-13 12:36:16', 2, 'Sales Manager', 'revision_requested', 1, 1, '2026-04-14 12:36:16', 'Auto-backfilled from sent proposal.', '2026-04-13 12:36:16', '2026-04-13 12:36:37'),
(4, 'APR-2026-00004', 'quotation', 5, 'PRO-000005', 1000.00, 4, '2026-04-13 13:21:41', 2, 'Sales Manager', 'pending', 1, 1, '2026-04-14 13:21:41', 'Auto-backfilled from sent proposal.', '2026-04-13 13:21:41', '2026-04-13 13:21:41'),
(5, 'APR-2026-00005', 'quotation', 6, 'PRO-000006', 2100.00, 4, '2026-04-13 13:26:21', 2, 'Sales Manager', 'pending', 1, 1, '2026-04-14 13:26:21', 'Auto-backfilled from sent proposal.', '2026-04-13 13:26:21', '2026-04-13 13:26:21');

-- --------------------------------------------------------

--
-- Table structure for table `tblipms_approval_thresholds`
--

CREATE TABLE `tblipms_approval_thresholds` (
  `id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `tier1_role` varchar(100) DEFAULT NULL,
  `tier1_max` decimal(15,2) DEFAULT NULL,
  `tier2_role` varchar(100) DEFAULT NULL,
  `tier2_max` decimal(15,2) DEFAULT NULL,
  `tier3_role` varchar(100) DEFAULT NULL,
  `always_gm` tinyint(1) DEFAULT 0,
  `sla_hours` int(11) DEFAULT 24,
  `total_stages` tinyint(4) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblipms_approval_thresholds`
--

INSERT INTO `tblipms_approval_thresholds` (`id`, `document_type`, `tier1_role`, `tier1_max`, `tier2_role`, `tier2_max`, `tier3_role`, `always_gm`, `sla_hours`, `total_stages`, `updated_at`) VALUES
(1, 'quotation', 'Sales Manager', 3000000.00, 'Finance Manager', 5000000.00, 'General Manager', 0, 24, 1, '2026-04-13 09:47:36'),
(2, 'credit_note', NULL, NULL, NULL, NULL, 'General Manager', 1, 4, 1, '2026-04-13 09:47:36'),
(3, 'journal_entry', NULL, NULL, NULL, NULL, 'General Manager', 1, 4, 1, '2026-04-13 09:47:36'),
(4, 'payment', NULL, NULL, NULL, NULL, 'General Manager', 1, 4, 1, '2026-04-13 09:47:36'),
(5, 'purchase_requisition', 'Finance Manager', 5000000.00, 'General Manager', NULL, NULL, 0, 24, 2, '2026-04-13 09:47:36');

-- --------------------------------------------------------

--
-- Table structure for table `tblitemable`
--

CREATE TABLE `tblitemable` (
  `id` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(15) NOT NULL,
  `description` longtext NOT NULL,
  `long_description` longtext DEFAULT NULL,
  `qty` decimal(15,2) NOT NULL,
  `rate` decimal(15,2) NOT NULL,
  `unit` varchar(40) DEFAULT NULL,
  `is_optional` tinyint(4) NOT NULL DEFAULT 0,
  `is_selected` tinyint(4) NOT NULL DEFAULT 1,
  `item_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblitemable`
--

INSERT INTO `tblitemable` (`id`, `rel_id`, `rel_type`, `description`, `long_description`, `qty`, `rate`, `unit`, `is_optional`, `is_selected`, `item_order`) VALUES
(1, 1, 'proposal', 'test', 'test', 1.00, 10000.00, '', 0, 1, 1),
(2, 1, 'proposal', 'test', 'tes', 1.00, 6000000.00, '', 0, 1, 2),
(3, 2, 'proposal', 'printing billboard', '15mx145', 1.00, 6100000.00, '', 0, 1, 1),
(4, 2, 'proposal', '', '', 1.00, 3500000.00, '', 0, 1, 2),
(5, 3, 'proposal', '15 by 30', 'fitted along chipembere highway ', 1.00, 3500000.00, '', 0, 1, 1),
(6, 4, 'proposal', '', '', 1.00, 1500000.00, '', 0, 1, 1),
(7, 5, 'proposal', 'asd', 'asd', 1.00, 10000.00, '', 0, 1, 1),
(8, 6, 'proposal', 'ssss', 'sss', 1.00, 1000.00, '', 0, 1, 1),
(9, 6, 'proposal', 'aaaa', 'aaa', 1.00, 1000.00, '', 0, 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `tblitems`
--

CREATE TABLE `tblitems` (
  `id` int(11) NOT NULL,
  `userid` int(11) DEFAULT NULL,
  `description` longtext NOT NULL,
  `long_description` mediumtext DEFAULT NULL,
  `rate` decimal(15,2) NOT NULL,
  `tax` int(11) DEFAULT NULL,
  `tax2` int(11) DEFAULT NULL,
  `unit` varchar(40) DEFAULT NULL,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `commodity_code` varchar(100) NOT NULL,
  `commodity_barcode` text DEFAULT NULL,
  `commodity_type` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `color_id` int(11) DEFAULT NULL,
  `style_id` int(11) DEFAULT NULL,
  `model_id` int(11) DEFAULT NULL,
  `size_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `sku_code` varchar(200) DEFAULT NULL,
  `sku_name` varchar(200) DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `sub_group` varchar(200) DEFAULT NULL,
  `commodity_name` varchar(200) NOT NULL,
  `color` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblitems_groups`
--

CREATE TABLE `tblitems_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `commodity_group_code` varchar(100) DEFAULT NULL,
  `order` int(10) DEFAULT NULL,
  `display` int(1) DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblitem_tax`
--

CREATE TABLE `tblitem_tax` (
  `id` int(11) NOT NULL,
  `itemid` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(20) NOT NULL,
  `taxrate` decimal(15,2) NOT NULL,
  `taxname` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblitem_tax`
--

INSERT INTO `tblitem_tax` (`id`, `itemid`, `rel_id`, `rel_type`, `taxrate`, `taxname`) VALUES
(1, 9, 6, 'proposal', 10.00, 'nccic tax');

-- --------------------------------------------------------

--
-- Table structure for table `tblknowedge_base_article_feedback`
--

CREATE TABLE `tblknowedge_base_article_feedback` (
  `articleanswerid` int(11) NOT NULL,
  `articleid` int(11) NOT NULL,
  `answer` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblknowledge_base`
--

CREATE TABLE `tblknowledge_base` (
  `articleid` int(11) NOT NULL,
  `articlegroup` int(11) NOT NULL,
  `subject` longtext NOT NULL,
  `description` mediumtext NOT NULL,
  `slug` longtext NOT NULL,
  `active` tinyint(4) NOT NULL,
  `datecreated` datetime NOT NULL,
  `article_order` int(11) NOT NULL DEFAULT 0,
  `staff_article` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblknowledge_base_groups`
--

CREATE TABLE `tblknowledge_base_groups` (
  `groupid` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `group_slug` mediumtext DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `active` tinyint(4) NOT NULL,
  `color` varchar(10) DEFAULT '#28B8DA',
  `group_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblleads`
--

CREATE TABLE `tblleads` (
  `id` int(11) NOT NULL,
  `hash` varchar(65) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `company` varchar(191) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `country` int(11) NOT NULL DEFAULT 0,
  `zip` varchar(15) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `assigned` int(11) NOT NULL DEFAULT 0,
  `dateadded` datetime NOT NULL,
  `from_form_id` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL,
  `source` int(11) NOT NULL,
  `lastcontact` datetime DEFAULT NULL,
  `dateassigned` date DEFAULT NULL,
  `last_status_change` datetime DEFAULT NULL,
  `addedfrom` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `leadorder` int(11) DEFAULT 1,
  `phonenumber` varchar(50) DEFAULT NULL,
  `date_converted` datetime DEFAULT NULL,
  `lost` tinyint(1) NOT NULL DEFAULT 0,
  `junk` int(11) NOT NULL DEFAULT 0,
  `last_lead_status` int(11) NOT NULL DEFAULT 0,
  `is_imported_from_email_integration` tinyint(1) NOT NULL DEFAULT 0,
  `email_integration_uid` varchar(30) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `default_language` varchar(40) DEFAULT NULL,
  `client_id` int(11) NOT NULL DEFAULT 0,
  `lead_value` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblleads_email_integration`
--

CREATE TABLE `tblleads_email_integration` (
  `id` int(11) NOT NULL COMMENT 'the ID always must be 1',
  `active` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `imap_server` varchar(100) NOT NULL,
  `password` longtext NOT NULL,
  `check_every` int(11) NOT NULL DEFAULT 5,
  `responsible` int(11) NOT NULL,
  `lead_source` int(11) NOT NULL,
  `lead_status` int(11) NOT NULL,
  `encryption` varchar(3) DEFAULT NULL,
  `folder` varchar(100) NOT NULL,
  `last_run` varchar(50) DEFAULT NULL,
  `notify_lead_imported` tinyint(1) NOT NULL DEFAULT 1,
  `notify_lead_contact_more_times` tinyint(1) NOT NULL DEFAULT 1,
  `notify_type` varchar(20) DEFAULT NULL,
  `notify_ids` longtext DEFAULT NULL,
  `mark_public` int(11) NOT NULL DEFAULT 0,
  `only_loop_on_unseen_emails` tinyint(1) NOT NULL DEFAULT 1,
  `delete_after_import` int(11) NOT NULL DEFAULT 0,
  `create_task_if_customer` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblleads_email_integration`
--

INSERT INTO `tblleads_email_integration` (`id`, `active`, `email`, `imap_server`, `password`, `check_every`, `responsible`, `lead_source`, `lead_status`, `encryption`, `folder`, `last_run`, `notify_lead_imported`, `notify_lead_contact_more_times`, `notify_type`, `notify_ids`, `mark_public`, `only_loop_on_unseen_emails`, `delete_after_import`, `create_task_if_customer`) VALUES
(1, 0, '', '', '', 10, 0, 0, 0, 'tls', 'INBOX', '', 1, 1, 'assigned', '', 0, 1, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblleads_sources`
--

CREATE TABLE `tblleads_sources` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblleads_sources`
--

INSERT INTO `tblleads_sources` (`id`, `name`) VALUES
(2, 'Facebook'),
(1, 'Google');

-- --------------------------------------------------------

--
-- Table structure for table `tblleads_status`
--

CREATE TABLE `tblleads_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `statusorder` int(11) DEFAULT NULL,
  `color` varchar(10) DEFAULT '#28B8DA',
  `isdefault` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblleads_status`
--

INSERT INTO `tblleads_status` (`id`, `name`, `statusorder`, `color`, `isdefault`) VALUES
(1, 'Customer', 1000, '#7cb342', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbllead_activity_log`
--

CREATE TABLE `tbllead_activity_log` (
  `id` int(11) NOT NULL,
  `leadid` int(11) NOT NULL,
  `description` longtext NOT NULL,
  `additional_data` mediumtext DEFAULT NULL,
  `date` datetime NOT NULL,
  `staffid` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `custom_activity` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbllead_integration_emails`
--

CREATE TABLE `tbllead_integration_emails` (
  `id` int(11) NOT NULL,
  `subject` longtext DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `dateadded` datetime NOT NULL,
  `leadid` int(11) NOT NULL,
  `emailid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblmail_queue`
--

CREATE TABLE `tblmail_queue` (
  `id` int(11) NOT NULL,
  `engine` varchar(40) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `cc` mediumtext DEFAULT NULL,
  `bcc` mediumtext DEFAULT NULL,
  `message` longtext NOT NULL,
  `alt_message` longtext DEFAULT NULL,
  `status` enum('pending','sending','sent','failed') DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `headers` mediumtext DEFAULT NULL,
  `attachments` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblmigrations`
--

CREATE TABLE `tblmigrations` (
  `version` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblmigrations`
--

INSERT INTO `tblmigrations` (`version`) VALUES
(341);

-- --------------------------------------------------------

--
-- Table structure for table `tblmilestones`
--

CREATE TABLE `tblmilestones` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `description_visible_to_customer` tinyint(1) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `due_date` date NOT NULL,
  `project_id` int(11) NOT NULL,
  `color` varchar(10) DEFAULT NULL,
  `milestone_order` int(11) NOT NULL DEFAULT 0,
  `datecreated` date NOT NULL,
  `hide_from_customer` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblmodules`
--

CREATE TABLE `tblmodules` (
  `id` int(11) NOT NULL,
  `module_name` varchar(55) NOT NULL,
  `installed_version` varchar(11) NOT NULL,
  `active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblmodules`
--

INSERT INTO `tblmodules` (`id`, `module_name`, `installed_version`, `active`) VALUES
(1, 'accounting', '1.0.8', 1),
(2, 'account_planning', '1.0.0', 1),
(3, 'si_export_customer', '1.0.3', 1),
(4, 'warehouse', '1.0.0', 1),
(5, 'custom_email_and_sms_notifications', '2.3.2', 0),
(6, 'purchase', '1.0.9', 1),
(7, 'supplier', '2.3.4', 1),
(8, 'api', '1.0.0', 1),
(9, 'approvals', '1.0.0', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblnewsfeed_comment_likes`
--

CREATE TABLE `tblnewsfeed_comment_likes` (
  `id` int(11) NOT NULL,
  `postid` int(11) NOT NULL,
  `commentid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `dateliked` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblnewsfeed_posts`
--

CREATE TABLE `tblnewsfeed_posts` (
  `postid` int(11) NOT NULL,
  `creator` int(11) NOT NULL,
  `datecreated` datetime NOT NULL,
  `visibility` varchar(100) NOT NULL,
  `content` mediumtext NOT NULL,
  `pinned` int(11) NOT NULL,
  `datepinned` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblnewsfeed_post_comments`
--

CREATE TABLE `tblnewsfeed_post_comments` (
  `id` int(11) NOT NULL,
  `content` mediumtext DEFAULT NULL,
  `userid` int(11) NOT NULL,
  `postid` int(11) NOT NULL,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblnewsfeed_post_likes`
--

CREATE TABLE `tblnewsfeed_post_likes` (
  `id` int(11) NOT NULL,
  `postid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `dateliked` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblnotes`
--

CREATE TABLE `tblnotes` (
  `id` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(20) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `date_contacted` datetime DEFAULT NULL,
  `addedfrom` int(11) NOT NULL,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblnotifications`
--

CREATE TABLE `tblnotifications` (
  `id` int(11) NOT NULL,
  `isread` int(11) NOT NULL DEFAULT 0,
  `isread_inline` tinyint(1) NOT NULL DEFAULT 0,
  `date` datetime NOT NULL,
  `description` mediumtext NOT NULL,
  `fromuserid` int(11) NOT NULL,
  `fromclientid` int(11) NOT NULL DEFAULT 0,
  `from_fullname` varchar(100) NOT NULL,
  `touserid` int(11) NOT NULL,
  `fromcompany` int(11) DEFAULT NULL,
  `link` longtext DEFAULT NULL,
  `additional_data` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblnotifications`
--

INSERT INTO `tblnotifications` (`id`, `isread`, `isread_inline`, `date`, `description`, `fromuserid`, `fromclientid`, `from_fullname`, `touserid`, `fromcompany`, `link`, `additional_data`) VALUES
(1, 1, 0, '2026-04-13 11:39:59', 'Approval required: Quotation PRO-000002 ($6,100,000.00)', 0, 0, '', 1, 1, 'approvals/view/1', NULL),
(2, 1, 0, '2026-04-13 11:39:59', 'Approval required: Quotation PRO-000001 ($6,010,000.00)', 0, 0, '', 1, 1, 'approvals/view/2', NULL),
(3, 0, 0, '2026-04-13 12:22:58', 'Your Quotation PRO-000002 has been approved: Approved from dashboard', 0, 0, '', 3, 1, 'approvals/view/1', NULL),
(4, 1, 0, '2026-04-13 12:23:39', 'Your Quotation PRO-000001 has been approved: Approved from dashboard', 0, 0, '', 1, 1, 'approvals/view/2', NULL),
(5, 1, 0, '2026-04-13 12:36:16', 'Approval required: Quotation PRO-000004 ($1,500,000.00)', 0, 0, '', 2, 1, 'approvals/view/3', NULL),
(6, 1, 1, '2026-04-13 12:36:37', 'Your Quotation PRO-000004 has been revision requested: Please revise and resubmit.', 0, 0, '', 1, 1, 'approvals/view/3', NULL),
(7, 0, 0, '2026-04-13 13:21:41', 'Approval required: Quotation PRO-000005 (K1,000.00)', 0, 0, '', 2, 1, 'approvals/view/4', NULL),
(8, 0, 0, '2026-04-13 13:26:21', 'Approval required: Quotation PRO-000006 (K2,100.00)', 0, 0, '', 2, 1, 'approvals/view/5', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbloptions`
--

CREATE TABLE `tbloptions` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `value` longtext NOT NULL,
  `autoload` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbloptions`
--

INSERT INTO `tbloptions` (`id`, `name`, `value`, `autoload`) VALUES
(1, 'dateformat', 'Y-m-d|%Y-%m-%d', 1),
(2, 'companyname', '', 1),
(3, 'services', '1', 1),
(4, 'maximum_allowed_ticket_attachments', '4', 1),
(5, 'ticket_attachments_file_extensions', '.jpg,.jpeg,.png,.pdf,.doc,.zip,.rar', 1),
(6, 'staff_access_only_assigned_departments', '1', 1),
(7, 'use_knowledge_base', '1', 1),
(8, 'smtp_email', '', 1),
(9, 'smtp_password', '', 1),
(10, 'company_info_format', '{company_name}<br />\r\n      {address}<br />\r\n      {city} {state}<br />\r\n      {country_code} {zip_code}<br />\r\n      {vat_number_with_label}', 0),
(11, 'smtp_port', '', 1),
(12, 'smtp_host', '', 1),
(13, 'smtp_email_charset', 'utf-8', 1),
(14, 'default_timezone', 'Africa/Blantyre', 1),
(15, 'clients_default_theme', 'perfex', 1),
(16, 'company_logo', '', 1),
(17, 'tables_pagination_limit', '25', 1),
(18, 'main_domain', '', 1),
(19, 'allow_registration', '0', 1),
(20, 'knowledge_base_without_registration', '1', 1),
(21, 'email_signature', '', 1),
(22, 'default_staff_role', '1', 1),
(23, 'newsfeed_maximum_files_upload', '10', 1),
(24, 'contract_expiration_before', '4', 1),
(25, 'invoice_prefix', 'INV-', 1),
(26, 'decimal_separator', '.', 1),
(27, 'thousand_separator', ',', 1),
(28, 'invoice_company_name', '', 1),
(29, 'invoice_company_address', '', 1),
(30, 'invoice_company_city', '', 1),
(31, 'invoice_company_country_code', '', 1),
(32, 'invoice_company_postal_code', '', 1),
(33, 'invoice_company_phonenumber', '', 1),
(34, 'view_invoice_only_logged_in', '0', 1),
(35, 'invoice_number_format', '1', 1),
(36, 'next_invoice_number', '1', 0),
(37, 'active_language', 'english', 1),
(38, 'invoice_number_decrement_on_delete', '1', 1),
(39, 'automatically_send_invoice_overdue_reminder_after', '1', 1),
(40, 'automatically_resend_invoice_overdue_reminder_after', '3', 1),
(41, 'expenses_auto_operations_hour', '9', 1),
(42, 'delete_only_on_last_invoice', '1', 1),
(43, 'delete_only_on_last_estimate', '1', 1),
(44, 'create_invoice_from_recurring_only_on_paid_invoices', '0', 1),
(45, 'allow_payment_amount_to_be_modified', '1', 1),
(46, 'rtl_support_client', '0', 1),
(47, 'limit_top_search_bar_results_to', '10', 1),
(48, 'estimate_prefix', 'EST-', 1),
(49, 'next_estimate_number', '1', 0),
(50, 'estimate_number_decrement_on_delete', '1', 1),
(51, 'estimate_number_format', '1', 1),
(52, 'estimate_auto_convert_to_invoice_on_client_accept', '1', 1),
(53, 'exclude_estimate_from_client_area_with_draft_status', '1', 1),
(54, 'rtl_support_admin', '0', 1),
(55, 'last_cron_run', '', 1),
(56, 'show_sale_agent_on_estimates', '1', 1),
(57, 'show_sale_agent_on_invoices', '1', 1),
(58, 'predefined_terms_invoice', '', 1),
(59, 'predefined_terms_estimate', '', 1),
(60, 'default_task_priority', '2', 1),
(61, 'dropbox_app_key', '', 1),
(62, 'show_expense_reminders_on_calendar', '1', 1),
(63, 'only_show_contact_tickets', '1', 1),
(64, 'predefined_clientnote_invoice', '', 1),
(65, 'predefined_clientnote_estimate', '', 1),
(66, 'custom_pdf_logo_image_url', '', 1),
(67, 'favicon', '', 1),
(68, 'invoice_due_after', '30', 1),
(69, 'google_api_key', '', 1),
(70, 'google_calendar_main_calendar', '', 1),
(71, 'default_tax', 'a:0:{}', 1),
(72, 'show_invoices_on_calendar', '1', 1),
(73, 'show_estimates_on_calendar', '1', 1),
(74, 'show_contracts_on_calendar', '1', 1),
(75, 'show_tasks_on_calendar', '1', 1),
(76, 'show_customer_reminders_on_calendar', '1', 1),
(77, 'output_client_pdfs_from_admin_area_in_client_language', '0', 1),
(78, 'show_lead_reminders_on_calendar', '1', 1),
(79, 'send_estimate_expiry_reminder_before', '4', 1),
(80, 'leads_default_source', '', 1),
(81, 'leads_default_status', '', 1),
(82, 'proposal_expiry_reminder_enabled', '1', 1),
(83, 'send_proposal_expiry_reminder_before', '4', 1),
(84, 'default_contact_permissions', 'a:6:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";i:3;s:1:\"4\";i:4;s:1:\"5\";i:5;s:1:\"6\";}', 1),
(85, 'pdf_logo_width', '150', 1),
(86, 'access_tickets_to_none_staff_members', '0', 1),
(87, 'customer_default_country', '', 1),
(88, 'view_estimate_only_logged_in', '0', 1),
(89, 'show_status_on_pdf_ei', '1', 1),
(90, 'email_piping_only_replies', '0', 1),
(91, 'email_piping_only_registered', '0', 1),
(92, 'default_view_calendar', 'dayGridMonth', 1),
(93, 'email_piping_default_priority', '2', 1),
(94, 'total_to_words_lowercase', '0', 1),
(95, 'show_tax_per_item', '1', 1),
(96, 'total_to_words_enabled', '0', 1),
(97, 'receive_notification_on_new_ticket', '1', 0),
(98, 'autoclose_tickets_after', '0', 1),
(99, 'media_max_file_size_upload', '10', 1),
(100, 'client_staff_add_edit_delete_task_comments_first_hour', '0', 1),
(101, 'show_projects_on_calendar', '1', 1),
(102, 'leads_kanban_limit', '50', 1),
(103, 'tasks_reminder_notification_before', '2', 1),
(104, 'pdf_font', 'freesans', 1),
(105, 'pdf_table_heading_color', '#e5e7eb', 1),
(106, 'pdf_table_heading_text_color', '#030712', 1),
(107, 'pdf_font_size', '10', 1),
(108, 'default_leads_kanban_sort', 'leadorder', 1),
(109, 'default_leads_kanban_sort_type', 'asc', 1),
(110, 'allowed_files', '.png,.jpg,.jpeg,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt', 1),
(111, 'show_all_tasks_for_project_member', '1', 1),
(112, 'email_protocol', 'smtp', 1),
(113, 'calendar_first_day', '0', 1),
(114, 'recaptcha_secret_key', '', 1),
(115, 'show_help_on_setup_menu', '1', 1),
(116, 'show_proposals_on_calendar', '1', 1),
(117, 'smtp_encryption', '', 1),
(118, 'recaptcha_site_key', '', 1),
(119, 'smtp_username', '', 1),
(120, 'auto_stop_tasks_timers_on_new_timer', '1', 1),
(121, 'notification_when_customer_pay_invoice', '1', 1),
(122, 'calendar_invoice_color', '#FF6F00', 1),
(123, 'calendar_estimate_color', '#FF6F00', 1),
(124, 'calendar_proposal_color', '#84c529', 1),
(125, 'new_task_auto_assign_current_member', '1', 1),
(126, 'calendar_reminder_color', '#03A9F4', 1),
(127, 'calendar_contract_color', '#B72974', 1),
(128, 'calendar_project_color', '#B72974', 1),
(129, 'update_info_message', '', 1),
(130, 'show_estimate_reminders_on_calendar', '1', 1),
(131, 'show_invoice_reminders_on_calendar', '1', 1),
(132, 'show_proposal_reminders_on_calendar', '1', 1),
(133, 'proposal_due_after', '7', 1),
(134, 'allow_customer_to_change_ticket_status', '0', 1),
(135, 'lead_lock_after_convert_to_customer', '0', 1),
(136, 'default_proposals_pipeline_sort', 'pipeline_order', 1),
(137, 'default_proposals_pipeline_sort_type', 'asc', 1),
(138, 'default_estimates_pipeline_sort', 'pipeline_order', 1),
(139, 'default_estimates_pipeline_sort_type', 'asc', 1),
(140, 'use_recaptcha_customers_area', '0', 1),
(141, 'remove_decimals_on_zero', '0', 1),
(142, 'remove_tax_name_from_item_table', '0', 1),
(143, 'pdf_format_invoice', 'A4-PORTRAIT', 1),
(144, 'pdf_format_estimate', 'A4-PORTRAIT', 1),
(145, 'pdf_format_proposal', 'A4-PORTRAIT', 1),
(146, 'pdf_format_payment', 'A4-PORTRAIT', 1),
(147, 'pdf_format_contract', 'A4-PORTRAIT', 1),
(148, 'swap_pdf_info', '0', 1),
(149, 'exclude_invoice_from_client_area_with_draft_status', '1', 1),
(150, 'cron_has_run_from_cli', '0', 1),
(151, 'hide_cron_is_required_message', '0', 0),
(152, 'auto_assign_customer_admin_after_lead_convert', '1', 1),
(153, 'show_transactions_on_invoice_pdf', '1', 1),
(154, 'show_pay_link_to_invoice_pdf', '1', 1),
(155, 'tasks_kanban_limit', '50', 1),
(156, 'purchase_key', '', 1),
(157, 'estimates_pipeline_limit', '50', 1),
(158, 'proposals_pipeline_limit', '50', 1),
(159, 'proposal_number_prefix', 'PRO-', 1),
(160, 'number_padding_prefixes', '6', 1),
(161, 'show_page_number_on_pdf', '0', 1),
(162, 'calendar_events_limit', '4', 1),
(163, 'show_setup_menu_item_only_on_hover', '0', 1),
(164, 'company_requires_vat_number_field', '1', 1),
(165, 'company_is_required', '1', 1),
(166, 'allow_contact_to_delete_files', '0', 1),
(167, 'company_vat', '', 1),
(168, 'di', '1775985776', 1),
(169, 'invoice_auto_operations_hour', '21', 1),
(170, 'use_minified_files', '1', 1),
(171, 'only_own_files_contacts', '0', 1),
(172, 'allow_primary_contact_to_view_edit_billing_and_shipping', '0', 1),
(173, 'estimate_due_after', '7', 1),
(174, 'staff_members_open_tickets_to_all_contacts', '1', 1),
(175, 'time_format', '24', 1),
(176, 'delete_activity_log_older_then', '1', 1),
(177, 'disable_language', '0', 1),
(178, 'company_state', '', 1),
(179, 'email_header', '<!doctype html>\r\n      <html>\r\n      <head>\r\n      <meta name=\"viewport\" content=\"width=device-width\" />\r\n      <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\r\n      <style>\r\n      body {\r\n        background-color: #f6f6f6;\r\n        font-family: sans-serif;\r\n        -webkit-font-smoothing: antialiased;\r\n        font-size: 14px;\r\n        line-height: 1.4;\r\n        margin: 0;\r\n        padding: 0;\r\n        -ms-text-size-adjust: 100%;\r\n        -webkit-text-size-adjust: 100%;\r\n      }\r\n      table {\r\n        border-collapse: separate;\r\n        mso-table-lspace: 0pt;\r\n        mso-table-rspace: 0pt;\r\n        width: 100%;\r\n      }\r\n      table td {\r\n        font-family: sans-serif;\r\n        font-size: 14px;\r\n        vertical-align: top;\r\n      }\r\n      /* -------------------------------------\r\n      BODY & CONTAINER\r\n      ------------------------------------- */\r\n      .body {\r\n        background-color: #f6f6f6;\r\n        width: 100%;\r\n      }\r\n      /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */\r\n      \r\n      .container {\r\n        display: block;\r\n        margin: 0 auto !important;\r\n        /* makes it centered */\r\n        max-width: 680px;\r\n        padding: 10px;\r\n        width: 680px;\r\n      }\r\n      /* This should also be a block element, so that it will fill 100% of the .container */\r\n      \r\n      .content {\r\n        box-sizing: border-box;\r\n        display: block;\r\n        margin: 0 auto;\r\n        max-width: 680px;\r\n        padding: 10px;\r\n      }\r\n      /* -------------------------------------\r\n      HEADER, FOOTER, MAIN\r\n      ------------------------------------- */\r\n      \r\n      .main {\r\n        background: #fff;\r\n        border-radius: 3px;\r\n        width: 100%;\r\n      }\r\n      .wrapper {\r\n        box-sizing: border-box;\r\n        padding: 20px;\r\n      }\r\n      .footer {\r\n        clear: both;\r\n        padding-top: 10px;\r\n        text-align: center;\r\n        width: 100%;\r\n      }\r\n      .footer td,\r\n      .footer p,\r\n      .footer span,\r\n      .footer a {\r\n        color: #999999;\r\n        font-size: 12px;\r\n        text-align: center;\r\n      }\r\n      hr {\r\n        border: 0;\r\n        border-bottom: 1px solid #f6f6f6;\r\n        margin: 20px 0;\r\n      }\r\n      /* -------------------------------------\r\n      RESPONSIVE AND MOBILE FRIENDLY STYLES\r\n      ------------------------------------- */\r\n      \r\n      @media only screen and (max-width: 620px) {\r\n        table[class=body] .content {\r\n          padding: 0 !important;\r\n        }\r\n        table[class=body] .container {\r\n          padding: 0 !important;\r\n          width: 100% !important;\r\n        }\r\n        table[class=body] .main {\r\n          border-left-width: 0 !important;\r\n          border-radius: 0 !important;\r\n          border-right-width: 0 !important;\r\n        }\r\n      }\r\n      </style>\r\n      </head>\r\n      <body class=\"\">\r\n      <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"body\">\r\n      <tr>\r\n      <td>&nbsp;</td>\r\n      <td class=\"container\">\r\n      <div class=\"content\">\r\n      <!-- START CENTERED WHITE CONTAINER -->\r\n      <table class=\"main\">\r\n      <!-- START MAIN CONTENT AREA -->\r\n      <tr>\r\n      <td class=\"wrapper\">\r\n      <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n      <tr>\r\n      <td>', 1),
(180, 'show_pdf_signature_invoice', '1', 0),
(181, 'show_pdf_signature_estimate', '1', 0),
(182, 'signature_image', '', 0),
(183, 'email_footer', '</td>\r\n      </tr>\r\n      </table>\r\n      </td>\r\n      </tr>\r\n      <!-- END MAIN CONTENT AREA -->\r\n      </table>\r\n      <!-- START FOOTER -->\r\n      <div class=\"footer\">\r\n      <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n      <tr>\r\n      <td class=\"content-block\">\r\n      <span>{companyname}</span>\r\n      </td>\r\n      </tr>\r\n      </table>\r\n      </div>\r\n      <!-- END FOOTER -->\r\n      <!-- END CENTERED WHITE CONTAINER -->\r\n      </div>\r\n      </td>\r\n      <td>&nbsp;</td>\r\n      </tr>\r\n      </table>\r\n      </body>\r\n      </html>', 1),
(184, 'exclude_proposal_from_client_area_with_draft_status', '1', 1),
(185, 'pusher_app_key', '', 1),
(186, 'pusher_app_secret', '', 1),
(187, 'pusher_app_id', '', 1),
(188, 'pusher_realtime_notifications', '0', 1),
(189, 'pdf_format_statement', 'A4-PORTRAIT', 1),
(190, 'pusher_cluster', '', 1),
(191, 'show_table_export_button', 'to_all', 1),
(192, 'allow_staff_view_proposals_assigned', '1', 1),
(193, 'show_cloudflare_notice', '1', 0),
(194, 'task_modal_class', 'modal-lg', 1),
(195, 'lead_modal_class', 'modal-lg', 1),
(196, 'show_timesheets_overview_all_members_notice_admins', '0', 1),
(197, 'desktop_notifications', '0', 1),
(198, 'hide_notified_reminders_from_calendar', '1', 0),
(199, 'customer_info_format', '{company_name}<br />\r\n      {street}<br />\r\n      {city} {state}<br />\r\n      {country_code} {zip_code}<br />\r\n      {vat_number_with_label}', 0),
(200, 'timer_started_change_status_in_progress', '1', 0),
(201, 'default_ticket_reply_status', '3', 1),
(202, 'default_task_status', 'auto', 1),
(203, 'email_queue_skip_with_attachments', '1', 1),
(204, 'email_queue_enabled', '0', 1),
(205, 'last_email_queue_retry', '', 1),
(206, 'auto_dismiss_desktop_notifications_after', '0', 1),
(207, 'proposal_info_format', '{proposal_to}<br />\r\n      {address}<br />\r\n      {city} {state}<br />\r\n      {country_code} {zip_code}<br />\r\n      {phone}<br />\r\n      {email}', 0),
(208, 'ticket_replies_order', 'desc', 1),
(209, 'new_recurring_invoice_action', 'generate_and_send', 0),
(210, 'bcc_emails', '', 0),
(211, 'email_templates_language_checks', '', 0),
(212, 'proposal_accept_identity_confirmation', '1', 0),
(213, 'estimate_accept_identity_confirmation', '1', 0),
(214, 'new_task_auto_follower_current_member', '0', 1),
(215, 'task_biillable_checked_on_creation', '1', 1),
(216, 'predefined_clientnote_credit_note', '', 1),
(217, 'predefined_terms_credit_note', '', 1),
(218, 'next_credit_note_number', '1', 1),
(219, 'credit_note_prefix', 'CN-', 1),
(220, 'credit_note_number_decrement_on_delete', '1', 1),
(221, 'pdf_format_credit_note', 'A4-PORTRAIT', 1),
(222, 'show_pdf_signature_credit_note', '1', 0),
(223, 'show_credit_note_reminders_on_calendar', '1', 1),
(224, 'show_amount_due_on_invoice', '1', 1),
(225, 'show_total_paid_on_invoice', '1', 1),
(226, 'show_credits_applied_on_invoice', '1', 1),
(227, 'staff_members_create_inline_lead_status', '1', 1),
(228, 'staff_members_create_inline_customer_groups', '1', 1),
(229, 'staff_members_create_inline_ticket_services', '1', 1),
(230, 'staff_members_save_tickets_predefined_replies', '1', 1),
(231, 'staff_members_create_inline_contract_types', '1', 1),
(232, 'staff_members_create_inline_expense_categories', '1', 1),
(233, 'show_project_on_credit_note', '1', 1),
(234, 'proposals_auto_operations_hour', '9', 1),
(235, 'estimates_auto_operations_hour', '9', 1),
(236, 'contracts_auto_operations_hour', '9', 1),
(237, 'credit_note_number_format', '1', 1),
(238, 'allow_non_admin_members_to_import_leads', '0', 1),
(239, 'e_sign_legal_text', 'By clicking on \"Sign\", I consent to be legally bound by this electronic representation of my signature.', 1),
(240, 'show_pdf_signature_contract', '1', 1),
(241, 'view_contract_only_logged_in', '0', 1),
(242, 'show_subscriptions_in_customers_area', '1', 1),
(243, 'calendar_only_assigned_tasks', '0', 1),
(244, 'after_subscription_payment_captured', 'send_invoice_and_receipt', 1),
(245, 'mail_engine', 'phpmailer', 1),
(246, 'gdpr_enable_terms_and_conditions', '0', 1),
(247, 'privacy_policy', '', 1),
(248, 'terms_and_conditions', '', 1),
(249, 'gdpr_enable_terms_and_conditions_lead_form', '0', 1),
(250, 'gdpr_enable_terms_and_conditions_ticket_form', '0', 1),
(251, 'gdpr_contact_enable_right_to_be_forgotten', '0', 1),
(252, 'show_gdpr_in_customers_menu', '1', 1),
(253, 'show_gdpr_link_in_footer', '1', 1),
(254, 'enable_gdpr', '0', 1),
(255, 'gdpr_on_forgotten_remove_invoices_credit_notes', '0', 1),
(256, 'gdpr_on_forgotten_remove_estimates', '0', 1),
(257, 'gdpr_enable_consent_for_contacts', '0', 1),
(258, 'gdpr_consent_public_page_top_block', '', 1),
(259, 'gdpr_page_top_information_block', '', 1),
(260, 'gdpr_enable_lead_public_form', '0', 1),
(261, 'gdpr_show_lead_custom_fields_on_public_form', '0', 1),
(262, 'gdpr_lead_attachments_on_public_form', '0', 1),
(263, 'gdpr_enable_consent_for_leads', '0', 1),
(264, 'gdpr_lead_enable_right_to_be_forgotten', '0', 1),
(265, 'allow_staff_view_invoices_assigned', '1', 1),
(266, 'gdpr_data_portability_leads', '0', 1),
(267, 'gdpr_lead_data_portability_allowed', '', 1),
(268, 'gdpr_contact_data_portability_allowed', '', 1),
(269, 'gdpr_data_portability_contacts', '0', 1),
(270, 'allow_staff_view_estimates_assigned', '1', 1),
(271, 'gdpr_after_lead_converted_delete', '0', 1),
(272, 'gdpr_show_terms_and_conditions_in_footer', '0', 1),
(273, 'save_last_order_for_tables', '0', 1),
(274, 'company_logo_dark', '', 1),
(275, 'customers_register_require_confirmation', '0', 1),
(276, 'allow_non_admin_staff_to_delete_ticket_attachments', '0', 1),
(277, 'receive_notification_on_new_ticket_replies', '1', 0),
(278, 'google_client_id', '', 1),
(279, 'enable_google_picker', '1', 1),
(280, 'show_ticket_reminders_on_calendar', '1', 1),
(281, 'ticket_import_reply_only', '0', 1),
(282, 'visible_customer_profile_tabs', 'all', 0),
(283, 'show_project_on_invoice', '1', 1),
(284, 'show_project_on_estimate', '1', 1),
(285, 'staff_members_create_inline_lead_source', '1', 1),
(286, 'lead_unique_validation', '[\"email\"]', 1),
(287, 'last_upgrade_copy_data', '', 1),
(288, 'custom_js_admin_scripts', '', 1),
(289, 'custom_js_customer_scripts', '0', 1),
(290, 'stripe_webhook_id', '', 1),
(291, 'stripe_webhook_signing_secret', '', 1),
(292, 'stripe_ideal_webhook_id', '', 1),
(293, 'stripe_ideal_webhook_signing_secret', '', 1),
(294, 'show_php_version_notice', '1', 0),
(295, 'recaptcha_ignore_ips', '', 1),
(296, 'show_task_reminders_on_calendar', '1', 1),
(297, 'customer_settings', 'true', 1),
(298, 'tasks_reminder_notification_hour', '9', 1),
(299, 'allow_primary_contact_to_manage_other_contacts', '0', 1),
(300, 'items_table_amounts_exclude_currency_symbol', '1', 1),
(301, 'round_off_task_timer_option', '0', 1),
(302, 'round_off_task_timer_time', '5', 1),
(303, 'bitly_access_token', '', 1),
(304, 'enable_support_menu_badges', '0', 1),
(305, 'attach_invoice_to_payment_receipt_email', '0', 1),
(306, 'invoice_due_notice_before', '2', 1),
(307, 'invoice_due_notice_resend_after', '0', 1),
(308, '_leads_settings', 'true', 1),
(309, 'show_estimate_request_in_customers_area', '0', 1),
(310, 'gdpr_enable_terms_and_conditions_estimate_request_form', '0', 1),
(311, 'identification_key', '564670359177598582869db64a475d65', 1),
(312, 'automatically_stop_task_timer_after_hours', '8', 1),
(313, 'automatically_assign_ticket_to_first_staff_responding', '0', 1),
(314, 'reminder_for_completed_but_not_billed_tasks', '0', 1),
(315, 'staff_notify_completed_but_not_billed_tasks', '', 1),
(316, 'reminder_for_completed_but_not_billed_tasks_days', '', 1),
(317, 'tasks_reminder_notification_last_notified_day', '', 1),
(318, 'staff_related_ticket_notification_to_assignee_only', '0', 1),
(319, 'show_pdf_signature_proposal', '1', 1),
(320, 'enable_honeypot_spam_validation', '0', 1),
(321, 'microsoft_mail_client_id', '', 1),
(322, 'microsoft_mail_client_secret', '', 1),
(323, 'microsoft_mail_azure_tenant_id', '', 1),
(324, 'google_mail_client_id', '', 1),
(325, 'google_mail_client_secret', '', 1),
(326, 'google_mail_refresh_token', '', 1),
(327, 'automatically_set_logged_in_staff_sales_agent', '1', 1),
(328, 'contract_sign_reminder_every_days', '0', 1),
(329, 'last_updated_date', '', 1),
(330, 'v310_incompatible_tables', '[]', 1),
(331, 'microsoft_mail_refresh_token', '', 1),
(332, 'required_register_fields', '[]', 0),
(333, 'allow_non_admin_members_to_delete_tickets_and_replies', '1', 1),
(334, 'proposal_auto_convert_to_invoice_on_client_accept', '0', 1),
(335, 'show_project_on_proposal', '1', 1),
(336, 'disable_ticket_public_url', '0', 1),
(337, 'openai_max_token', '500', 1),
(338, 'ai_provider', '', 1),
(339, 'ai_enable_ticket_summarization', '0', 0),
(340, 'ai_enable_ticket_reply_suggestions', '0', 0),
(341, 'openai_use_fine_tuning', '0', 1),
(342, 'openai_fine_tuning_base_model', '', 1),
(343, 'openai_fine_tuning_last_job_id', '', 1),
(344, 'openai_fine_tuned_model', '', 1),
(345, 'openai_our_fine_tuned_model', '', 1),
(346, 'openai_last_fine_tuning_file_id', '', 1),
(347, 'ai_system_prompt', 'You are a support representative', 1),
(348, 'upgraded_from_version', '', 0),
(349, 'einvoice_send_as_invoice_email_attachment', '0', 1),
(350, 'einvoice_default_credit_note_email_template', '0', 1),
(351, 'einvoice_default_invoice_template', '', 1),
(352, 'einvoice_default_credit_note_template', '', 1),
(353, 'einvoice_send_as_credit_note_email_attachment', '0', 1),
(354, 'enabled_languages', '', 1),
(355, 'proposal_auto_convert_leads_to_client_on_client_accept', '0', 1),
(356, 'allow_non_admin_members_to_edit_ticket_messages', '0', 1),
(357, 'localization_settings', 'true', 1),
(358, 'sms_clickatell_api_key', '', 1),
(359, 'sms_clickatell_active', '0', 1),
(360, 'sms_clickatell_initialized', '1', 1),
(361, 'sms_msg91_sender_id', '', 1),
(362, 'sms_msg91_api_type', 'api', 1),
(363, 'sms_msg91_auth_key', '', 1),
(364, 'sms_msg91_active', '0', 1),
(365, 'sms_msg91_initialized', '1', 1),
(366, 'sms_twilio_account_sid', '', 1),
(367, 'sms_twilio_auth_token', '', 1),
(368, 'sms_twilio_phone_number', '', 1),
(369, 'sms_twilio_sender_id', '', 1),
(370, 'sms_twilio_active', '0', 1),
(371, 'sms_twilio_initialized', '1', 1),
(372, 'acc_first_month_of_financial_year', 'January', 1),
(373, 'acc_first_month_of_tax_year', 'same_as_financial_year', 1),
(374, 'acc_accounting_method', 'accrual', 1),
(375, 'acc_close_the_books', '0', 1),
(376, 'acc_allow_changes_after_viewing', 'allow_changes_after_viewing_a_warning', 1),
(377, 'acc_close_book_password', '', 1),
(378, 'acc_close_book_passwordr', '', 1),
(379, 'acc_enable_account_numbers', '0', 1),
(380, 'acc_show_account_numbers', '0', 1),
(381, 'acc_closing_date', '', 1),
(382, 'acc_add_default_account', '1', 1),
(383, 'acc_add_default_account_new', '0', 1),
(384, 'acc_invoice_automatic_conversion', '1', 1),
(385, 'acc_payment_automatic_conversion', '1', 1),
(386, 'acc_expense_automatic_conversion', '1', 1),
(387, 'acc_tax_automatic_conversion', '1', 1),
(388, 'acc_invoice_payment_account', '66', 1),
(389, 'acc_invoice_deposit_to', '1', 1),
(390, 'acc_payment_payment_account', '1', 1),
(391, 'acc_payment_deposit_to', '13', 1),
(392, 'acc_expense_payment_account', '13', 1),
(393, 'acc_expense_deposit_to', '80', 1),
(394, 'acc_tax_payment_account', '29', 1),
(395, 'acc_tax_deposit_to', '1', 1),
(396, 'acc_expense_tax_payment_account', '13', 1),
(397, 'acc_expense_tax_deposit_to', '29', 1),
(398, 'acc_active_payment_mode_mapping', '1', 1),
(399, 'acc_active_expense_category_mapping', '1', 1),
(400, 'acc_payment_expense_automatic_conversion', '1', 1),
(401, 'acc_payment_sale_automatic_conversion', '1', 1),
(402, 'acc_expense_payment_payment_account', '1', 1),
(403, 'acc_expense_payment_deposit_to', '1', 1),
(404, 'acc_pl_total_insurance_automatic_conversion', '1', 1),
(405, 'acc_pl_total_insurance_payment_account', '13', 1),
(406, 'acc_pl_total_insurance_deposit_to', '32', 1),
(407, 'acc_pl_tax_paye_automatic_conversion', '1', 1),
(408, 'acc_pl_tax_paye_payment_account', '13', 1),
(409, 'acc_pl_tax_paye_deposit_to', '28', 1),
(410, 'acc_pl_net_pay_automatic_conversion', '1', 1),
(411, 'acc_pl_net_pay_payment_account', '13', 1),
(412, 'acc_pl_net_pay_deposit_to', '56', 1),
(413, 'acc_wh_stock_import_automatic_conversion', '1', 1),
(414, 'acc_wh_stock_import_payment_account', '87', 1),
(415, 'acc_wh_stock_import_deposit_to', '37', 1),
(416, 'acc_wh_stock_export_automatic_conversion', '1', 1),
(417, 'acc_wh_stock_export_payment_account', '37', 1),
(418, 'acc_wh_stock_export_deposit_to', '1', 1),
(419, 'acc_wh_loss_adjustment_automatic_conversion', '1', 1),
(420, 'acc_wh_decrease_payment_account', '37', 1),
(421, 'acc_wh_decrease_deposit_to', '1', 1),
(422, 'acc_wh_increase_payment_account', '87', 1),
(423, 'acc_wh_increase_deposit_to', '37', 1),
(424, 'acc_wh_opening_stock_automatic_conversion', '1', 1),
(425, 'acc_wh_opening_stock_payment_account', '88', 1),
(426, 'acc_wh_opening_stock_deposit_to', '37', 1),
(427, 'acc_pur_order_automatic_conversion', '1', 1),
(428, 'acc_pur_order_payment_account', '13', 1),
(429, 'acc_pur_order_deposit_to', '80', 1),
(430, 'acc_pur_payment_automatic_conversion', '1', 1),
(431, 'acc_pur_payment_payment_account', '16', 1),
(432, 'acc_pur_payment_deposit_to', '37', 1),
(433, 'account_planning_enabled', '1', 1),
(434, 'si_export_customer_logo_size', '135', 1),
(435, 'si_export_customer_specimen_sign', '2', 1),
(436, 'si_export_customer_show_shipping_address', '0', 1),
(437, 'si_export_customer_show_billing_address', '0', 1),
(438, 'si_export_customer_print_files_separate_page', '1', 1),
(439, 'si_export_customer_print_heading_color', '#00A1CB', 1),
(440, 'si_export_customer_show_primary_contact', '1', 1),
(441, 'si_export_customer_show_groups', '1', 1),
(442, 'si_export_customer_show_custom_fields', '1', 1),
(443, 'si_export_customer_print_heading_text', 'Client Profile', 1),
(444, 'si_export_customer_print_custom_fields_text', 'Other Information', 1),
(445, 'si_export_customer_print_attachment_text', 'Documents Attached', 1),
(446, 'si_export_customer_print_specimen_sign_text', 'Specimen Signature', 1),
(447, 'si_export_customer_print_services_text', 'Services', 1),
(448, 'si_export_customer_print_orientation', 'P', 1),
(449, 'si_export_customer_print_services', '1', 1),
(450, 'si_export_customer_matrix_print_heading_text', 'Overview', 1),
(451, 'si_export_customer_matrix_print_heading_color', '#00A1CB', 1),
(452, 'si_export_customer_matrix_print_orientation', 'P', 1),
(453, 'si_export_customer_matrix_print_services', '1', 1),
(457, 'custom_email_and_sms_notifications', '1', 1),
(458, 'aside_custom_email_and_sms_notifications_active', '[]', 1),
(459, 'setup_custom_email_and_sms_notifications_active', '[]', 1),
(460, 'paymentmethod_authorize_acceptjs_active', '0', 1),
(461, 'paymentmethod_authorize_acceptjs_label', 'Authorize.net Accept.js', 1),
(462, 'paymentmethod_authorize_acceptjs_public_key', '', 0),
(463, 'paymentmethod_authorize_acceptjs_api_login_id', '', 0),
(464, 'paymentmethod_authorize_acceptjs_api_transaction_key', '', 0),
(465, 'paymentmethod_authorize_acceptjs_description_dashboard', 'Payment for Invoice {invoice_number}', 0),
(466, 'paymentmethod_authorize_acceptjs_currencies', 'USD', 0),
(467, 'paymentmethod_authorize_acceptjs_test_mode_enabled', '0', 0),
(468, 'paymentmethod_authorize_acceptjs_default_selected', '1', 1),
(469, 'paymentmethod_authorize_acceptjs_initialized', '1', 1),
(470, 'paymentmethod_instamojo_active', '0', 1),
(471, 'paymentmethod_instamojo_label', 'Instamojo', 1),
(472, 'paymentmethod_instamojo_fee_fixed', '0', 0),
(473, 'paymentmethod_instamojo_fee_percent', '0', 0),
(474, 'paymentmethod_instamojo_api_key', '', 0),
(475, 'paymentmethod_instamojo_auth_token', '', 0),
(476, 'paymentmethod_instamojo_description_dashboard', 'Payment for Invoice {invoice_number}', 0),
(477, 'paymentmethod_instamojo_currencies', 'INR', 0),
(478, 'paymentmethod_instamojo_test_mode_enabled', '1', 0),
(479, 'paymentmethod_instamojo_default_selected', '1', 1),
(480, 'paymentmethod_instamojo_initialized', '1', 1),
(481, 'paymentmethod_mollie_active', '0', 1),
(482, 'paymentmethod_mollie_label', 'Mollie', 1),
(483, 'paymentmethod_mollie_api_key', '', 0),
(484, 'paymentmethod_mollie_description_dashboard', 'Payment for Invoice {invoice_number}', 0),
(485, 'paymentmethod_mollie_currencies', 'EUR', 0),
(486, 'paymentmethod_mollie_test_mode_enabled', '1', 0),
(487, 'paymentmethod_mollie_default_selected', '1', 1),
(488, 'paymentmethod_mollie_initialized', '1', 1),
(489, 'paymentmethod_paypal_braintree_active', '0', 1),
(490, 'paymentmethod_paypal_braintree_label', 'Braintree', 1),
(491, 'paymentmethod_paypal_braintree_merchant_id', '', 0),
(492, 'paymentmethod_paypal_braintree_api_public_key', '', 0),
(493, 'paymentmethod_paypal_braintree_api_private_key', '', 0),
(494, 'paymentmethod_paypal_braintree_currencies', 'USD', 0),
(495, 'paymentmethod_paypal_braintree_paypal_enabled', '1', 0),
(496, 'paymentmethod_paypal_braintree_test_mode_enabled', '1', 0),
(497, 'paymentmethod_paypal_braintree_default_selected', '1', 1),
(498, 'paymentmethod_paypal_braintree_initialized', '1', 1),
(499, 'paymentmethod_paypal_checkout_active', '0', 1),
(500, 'paymentmethod_paypal_checkout_label', 'Paypal Smart Checkout', 1),
(501, 'paymentmethod_paypal_checkout_fee_fixed', '0', 0),
(502, 'paymentmethod_paypal_checkout_fee_percent', '0', 0),
(503, 'paymentmethod_paypal_checkout_client_id', '', 0),
(504, 'paymentmethod_paypal_checkout_secret', '', 0),
(505, 'paymentmethod_paypal_checkout_payment_description', 'Payment for Invoice {invoice_number}', 0),
(506, 'paymentmethod_paypal_checkout_currencies', 'USD,CAD,EUR', 0),
(507, 'paymentmethod_paypal_checkout_test_mode_enabled', '1', 0),
(508, 'paymentmethod_paypal_checkout_default_selected', '1', 1),
(509, 'paymentmethod_paypal_checkout_initialized', '1', 1),
(510, 'paymentmethod_paypal_active', '0', 1),
(511, 'paymentmethod_paypal_label', 'Paypal', 1),
(512, 'paymentmethod_paypal_fee_fixed', '0', 0),
(513, 'paymentmethod_paypal_fee_percent', '0', 0),
(514, 'paymentmethod_paypal_username', '', 0),
(515, 'paymentmethod_paypal_password', '', 0),
(516, 'paymentmethod_paypal_signature', '', 0),
(517, 'paymentmethod_paypal_description_dashboard', 'Payment for Invoice {invoice_number}', 0),
(518, 'paymentmethod_paypal_currencies', 'EUR,USD', 0),
(519, 'paymentmethod_paypal_test_mode_enabled', '1', 0),
(520, 'paymentmethod_paypal_default_selected', '1', 1),
(521, 'paymentmethod_paypal_initialized', '1', 1),
(522, 'paymentmethod_payu_money_active', '0', 1),
(523, 'paymentmethod_payu_money_label', 'PayU Money', 1),
(524, 'paymentmethod_payu_money_fee_fixed', '0', 0),
(525, 'paymentmethod_payu_money_fee_percent', '0', 0),
(526, 'paymentmethod_payu_money_key', '', 0),
(527, 'paymentmethod_payu_money_salt', '', 0),
(528, 'paymentmethod_payu_money_description_dashboard', 'Payment for Invoice {invoice_number}', 0),
(529, 'paymentmethod_payu_money_currencies', 'INR', 0),
(530, 'paymentmethod_payu_money_test_mode_enabled', '1', 0),
(531, 'paymentmethod_payu_money_default_selected', '1', 1),
(532, 'paymentmethod_payu_money_initialized', '1', 1),
(533, 'paymentmethod_stripe_active', '0', 1),
(534, 'paymentmethod_stripe_label', 'Stripe Checkout', 1),
(535, 'paymentmethod_stripe_fee_fixed', '0', 0),
(536, 'paymentmethod_stripe_fee_percent', '0', 0),
(537, 'paymentmethod_stripe_api_publishable_key', '', 0),
(538, 'paymentmethod_stripe_api_secret_key', '', 0),
(539, 'paymentmethod_stripe_description_dashboard', 'Payment for Invoice {invoice_number}', 0),
(540, 'paymentmethod_stripe_currencies', 'USD,CAD', 0),
(541, 'paymentmethod_stripe_allow_primary_contact_to_update_credit_card', '1', 0),
(542, 'paymentmethod_stripe_default_selected', '1', 1),
(543, 'paymentmethod_stripe_initialized', '1', 1),
(544, 'paymentmethod_stripe_ideal_active', '0', 1),
(545, 'paymentmethod_stripe_ideal_label', 'Stripe iDEAL', 1),
(546, 'paymentmethod_stripe_ideal_api_secret_key', '', 0),
(547, 'paymentmethod_stripe_ideal_api_publishable_key', '', 0),
(548, 'paymentmethod_stripe_ideal_description_dashboard', 'Payment for Invoice {invoice_number}', 0),
(549, 'paymentmethod_stripe_ideal_statement_descriptor', 'Payment for Invoice {invoice_number}', 0),
(550, 'paymentmethod_stripe_ideal_currencies', 'EUR', 0),
(551, 'paymentmethod_stripe_ideal_default_selected', '1', 1),
(552, 'paymentmethod_stripe_ideal_initialized', '1', 1),
(553, 'paymentmethod_two_checkout_active', '0', 1),
(554, 'paymentmethod_two_checkout_label', '2Checkout', 1),
(555, 'paymentmethod_two_checkout_fee_fixed', '0', 0),
(556, 'paymentmethod_two_checkout_fee_percent', '0', 0),
(557, 'paymentmethod_two_checkout_merchant_code', '', 0),
(558, 'paymentmethod_two_checkout_secret_key', '', 0),
(559, 'paymentmethod_two_checkout_description', 'Payment for Invoice {invoice_number}', 0),
(560, 'paymentmethod_two_checkout_currencies', 'USD, EUR, GBP', 0),
(561, 'paymentmethod_two_checkout_test_mode_enabled', '1', 0),
(562, 'paymentmethod_two_checkout_default_selected', '1', 1),
(563, 'paymentmethod_two_checkout_initialized', '1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblpayment_attempts`
--

CREATE TABLE `tblpayment_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference` varchar(100) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` double NOT NULL,
  `fee` double NOT NULL,
  `payment_gateway` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpayment_modes`
--

CREATE TABLE `tblpayment_modes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `show_on_pdf` int(11) NOT NULL DEFAULT 0,
  `invoices_only` int(11) NOT NULL DEFAULT 0,
  `expenses_only` int(11) NOT NULL DEFAULT 0,
  `selected_by_default` int(11) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblpayment_modes`
--

INSERT INTO `tblpayment_modes` (`id`, `name`, `description`, `show_on_pdf`, `invoices_only`, `expenses_only`, `selected_by_default`, `active`) VALUES
(1, 'Bank', NULL, 0, 0, 0, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblpinned_projects`
--

CREATE TABLE `tblpinned_projects` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblprojectdiscussioncomments`
--

CREATE TABLE `tblprojectdiscussioncomments` (
  `id` int(11) NOT NULL,
  `discussion_id` int(11) NOT NULL,
  `discussion_type` varchar(10) NOT NULL,
  `parent` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `staff_id` int(11) NOT NULL,
  `contact_id` int(11) DEFAULT 0,
  `fullname` varchar(191) DEFAULT NULL,
  `file_name` varchar(191) DEFAULT NULL,
  `file_mime_type` varchar(70) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblprojectdiscussions`
--

CREATE TABLE `tblprojectdiscussions` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `subject` varchar(191) NOT NULL,
  `description` mediumtext NOT NULL,
  `show_to_customer` tinyint(1) NOT NULL DEFAULT 0,
  `datecreated` datetime NOT NULL,
  `last_activity` datetime DEFAULT NULL,
  `staff_id` int(11) NOT NULL DEFAULT 0,
  `contact_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblprojects`
--

CREATE TABLE `tblprojects` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `clientid` int(11) NOT NULL,
  `billing_type` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `deadline` date DEFAULT NULL,
  `project_created` date NOT NULL,
  `date_finished` datetime DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `progress_from_tasks` int(11) NOT NULL DEFAULT 1,
  `project_cost` decimal(15,2) DEFAULT NULL,
  `project_rate_per_hour` decimal(15,2) DEFAULT NULL,
  `estimated_hours` decimal(15,2) DEFAULT NULL,
  `addedfrom` int(11) NOT NULL,
  `contact_notification` int(11) DEFAULT 1,
  `notify_contacts` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblproject_activity`
--

CREATE TABLE `tblproject_activity` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL DEFAULT 0,
  `contact_id` int(11) NOT NULL DEFAULT 0,
  `fullname` varchar(100) DEFAULT NULL,
  `visible_to_customer` int(11) NOT NULL DEFAULT 0,
  `description_key` varchar(191) NOT NULL COMMENT 'Language file key',
  `additional_data` mediumtext DEFAULT NULL,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblproject_files`
--

CREATE TABLE `tblproject_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(191) NOT NULL,
  `original_file_name` longtext DEFAULT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `filetype` varchar(50) DEFAULT NULL,
  `dateadded` datetime NOT NULL,
  `last_activity` datetime DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `visible_to_customer` tinyint(1) DEFAULT 0,
  `staffid` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL DEFAULT 0,
  `external` varchar(40) DEFAULT NULL,
  `external_link` mediumtext DEFAULT NULL,
  `thumbnail_link` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblproject_members`
--

CREATE TABLE `tblproject_members` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblproject_notes`
--

CREATE TABLE `tblproject_notes` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `staff_id` int(11) NOT NULL,
  `dateadded` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblproject_settings`
--

CREATE TABLE `tblproject_settings` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblproposals`
--

CREATE TABLE `tblproposals` (
  `id` int(11) NOT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `addedfrom` int(11) NOT NULL,
  `datecreated` datetime NOT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `total_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `adjustment` decimal(15,2) DEFAULT NULL,
  `discount_percent` decimal(15,2) NOT NULL,
  `discount_total` decimal(15,2) NOT NULL,
  `discount_type` varchar(30) DEFAULT NULL,
  `show_quantity_as` int(11) NOT NULL DEFAULT 1,
  `currency` int(11) NOT NULL,
  `open_till` date DEFAULT NULL,
  `date` date NOT NULL,
  `rel_id` int(11) DEFAULT NULL,
  `rel_type` varchar(40) DEFAULT NULL,
  `assigned` int(11) DEFAULT NULL,
  `hash` varchar(32) NOT NULL,
  `proposal_to` varchar(191) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `country` int(11) NOT NULL DEFAULT 0,
  `zip` varchar(50) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `allow_comments` tinyint(1) NOT NULL DEFAULT 1,
  `status` int(11) NOT NULL,
  `estimate_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `date_converted` datetime DEFAULT NULL,
  `pipeline_order` int(11) DEFAULT 1,
  `is_expiry_notified` int(11) NOT NULL DEFAULT 0,
  `acceptance_firstname` varchar(50) DEFAULT NULL,
  `acceptance_lastname` varchar(50) DEFAULT NULL,
  `acceptance_email` varchar(100) DEFAULT NULL,
  `acceptance_date` datetime DEFAULT NULL,
  `acceptance_ip` varchar(40) DEFAULT NULL,
  `signature` varchar(40) DEFAULT NULL,
  `short_link` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblproposals`
--

INSERT INTO `tblproposals` (`id`, `subject`, `content`, `addedfrom`, `datecreated`, `total`, `subtotal`, `total_tax`, `adjustment`, `discount_percent`, `discount_total`, `discount_type`, `show_quantity_as`, `currency`, `open_till`, `date`, `rel_id`, `rel_type`, `assigned`, `hash`, `proposal_to`, `project_id`, `country`, `zip`, `state`, `city`, `address`, `email`, `phone`, `allow_comments`, `status`, `estimate_id`, `invoice_id`, `date_converted`, `pipeline_order`, `is_expiry_notified`, `acceptance_firstname`, `acceptance_lastname`, `acceptance_email`, `acceptance_date`, `acceptance_ip`, `signature`, `short_link`) VALUES
(1, 'computer science', '{proposal_items}', 1, '2026-04-13 10:22:51', 6010000.00, 6010000.00, 0.00, 0.00, 0.00, 0.00, '', 1, 1, '2026-04-20', '2026-04-13', 1, 'customer', 1, '3a1779caa980fd3ef3a0b97f786cf3c4', 'Test Customer', 0, 0, '', '', '', '', 'test@test.com', '0880842416', 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'billboard fitting', '{proposal_items}', 3, '2026-04-13 11:19:59', 9600000.00, 9600000.00, 0.00, 0.00, 0.00, 0.00, '', 1, 1, '2026-04-20', '2026-04-13', 1, 'customer', 3, '4298ad9b58df964fe567e5ddbac66909', 'Test Customer', 0, 132, '', '', 'Blantyre, Malawi', 'BT', 'zarilasam99@gmail.com', '0899622111', 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'billboard fitting', '{proposal_items}', 1, '2026-04-13 12:34:11', 3500000.00, 3500000.00, 0.00, 0.00, 0.00, 0.00, '', 1, 1, '2026-04-20', '2026-04-13', 1, 'customer', 1, '65ec27411a4fcb157938bae9908e8320', 'Test Customer', 0, 132, '', '', 'Blantyre, Malawi', 'BT', 'zarilasam99@gmail.com', '0899622111', 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'filming and tunnel ', '{proposal_items}', 1, '2026-04-13 12:35:35', 1500000.00, 1500000.00, 0.00, 0.00, 0.00, 0.00, '', 1, 1, '2026-04-20', '2026-04-13', 1, 'customer', 1, 'b17434537094b5c7ec232d9af8d3647c', 'Test Customer', 0, 132, '', '', 'Blantyre, Malawi', 'BT', 'zarilasam99@gmail.com', '0899622111', 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'test3', '{proposal_items}', 4, '2026-04-13 13:18:51', 10000.00, 10000.00, 0.00, 0.00, 0.00, 0.00, '', 1, 1, '2026-04-20', '2026-04-13', 1, 'customer', 4, 'e23c2971372d520cb3b598f2acc3eec3', 'Test Customer', 0, 0, '', '', '', '', 'info@express.mw', '0880842416', 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'sss', '{proposal_items}', 4, '2026-04-13 13:25:55', 2100.00, 2000.00, 100.00, 0.00, 0.00, 0.00, '', 1, 1, '2026-04-20', '2026-04-13', 1, 'customer', 4, '0bbb906294420480b475a27f7ca74c65', 'Test Customer', 0, 0, '', '', '', '', 'info@express.mws', '0880842416', 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblproposal_comments`
--

CREATE TABLE `tblproposal_comments` (
  `id` int(11) NOT NULL,
  `content` longtext DEFAULT NULL,
  `proposalid` int(11) NOT NULL,
  `staffid` int(11) NOT NULL,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpurchase_option`
--

CREATE TABLE `tblpurchase_option` (
  `option_id` int(11) UNSIGNED NOT NULL,
  `option_name` varchar(200) NOT NULL,
  `option_val` longtext DEFAULT NULL,
  `auto` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblpurchase_option`
--

INSERT INTO `tblpurchase_option` (`option_id`, `option_name`, `option_val`, `auto`) VALUES
(1, 'purchase_order_setting', '1', 1),
(2, 'pur_order_prefix', '#PO', 1),
(3, 'next_po_number', '1', 1),
(4, 'date_reset_number', '', 1),
(5, 'pur_request_prefix', '#PR', 1),
(6, 'next_pr_number', '1', 1),
(7, 'date_reset_pr_number', '', 1),
(8, 'pur_inv_prefix', '#INV', 1),
(9, 'next_inv_number', '1', 1),
(10, 'create_invoice_by', 'contract', 1),
(11, 'item_by_vendor', '0', 1),
(12, 'terms_and_conditions', '', 1),
(13, 'vendor_note', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_approval_details`
--

CREATE TABLE `tblpur_approval_details` (
  `id` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(45) NOT NULL,
  `staffid` varchar(45) DEFAULT NULL,
  `approve` varchar(45) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `approve_action` varchar(255) DEFAULT NULL,
  `reject_action` varchar(255) DEFAULT NULL,
  `approve_value` varchar(255) DEFAULT NULL,
  `reject_value` varchar(255) DEFAULT NULL,
  `staff_approve` int(11) DEFAULT NULL,
  `action` varchar(45) DEFAULT NULL,
  `sender` int(11) DEFAULT NULL,
  `date_send` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_approval_setting`
--

CREATE TABLE `tblpur_approval_setting` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `related` varchar(255) NOT NULL,
  `setting` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_contacts`
--

CREATE TABLE `tblpur_contacts` (
  `id` int(11) UNSIGNED NOT NULL,
  `userid` int(11) NOT NULL,
  `is_primary` int(11) NOT NULL DEFAULT 1,
  `firstname` varchar(191) NOT NULL,
  `lastname` varchar(191) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phonenumber` varchar(100) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `new_pass_key` varchar(32) DEFAULT NULL,
  `new_pass_key_requested` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `email_verification_key` varchar(32) DEFAULT NULL,
  `email_verification_sent_at` datetime DEFAULT NULL,
  `last_ip` varchar(40) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_password_change` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `profile_image` varchar(191) DEFAULT NULL,
  `direction` varchar(3) DEFAULT NULL,
  `invoice_emails` tinyint(1) NOT NULL DEFAULT 1,
  `estimate_emails` tinyint(1) NOT NULL DEFAULT 1,
  `credit_note_emails` tinyint(1) NOT NULL DEFAULT 1,
  `contract_emails` tinyint(1) NOT NULL DEFAULT 1,
  `task_emails` tinyint(1) NOT NULL DEFAULT 1,
  `project_emails` tinyint(1) NOT NULL DEFAULT 1,
  `ticket_emails` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_contracts`
--

CREATE TABLE `tblpur_contracts` (
  `id` int(11) UNSIGNED NOT NULL,
  `contract_number` varchar(200) NOT NULL,
  `contract_name` varchar(200) NOT NULL,
  `content` longtext DEFAULT NULL,
  `vendor` int(11) NOT NULL,
  `pur_order` int(11) NOT NULL,
  `contract_value` decimal(15,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `buyer` int(11) DEFAULT NULL,
  `time_payment` date DEFAULT NULL,
  `add_from` int(11) NOT NULL,
  `signed` int(32) NOT NULL DEFAULT 0,
  `note` longtext DEFAULT NULL,
  `signer` int(11) DEFAULT NULL,
  `signed_date` date DEFAULT NULL,
  `signed_status` varchar(50) DEFAULT NULL,
  `service_category` text DEFAULT NULL,
  `project` int(11) DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `payment_amount` decimal(15,2) DEFAULT NULL,
  `payment_cycle` varchar(50) DEFAULT NULL,
  `department` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_estimates`
--

CREATE TABLE `tblpur_estimates` (
  `id` int(11) UNSIGNED NOT NULL,
  `sent` tinyint(1) NOT NULL DEFAULT 0,
  `datesend` datetime DEFAULT NULL,
  `vendor` int(11) NOT NULL,
  `deleted_vendor_name` varchar(100) DEFAULT NULL,
  `pur_request` int(11) DEFAULT NULL,
  `number` int(11) NOT NULL,
  `prefix` varchar(50) DEFAULT NULL,
  `number_format` int(11) NOT NULL DEFAULT 0,
  `hash` varchar(32) DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `date` date NOT NULL,
  `expirydate` date DEFAULT NULL,
  `currency` int(11) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `total_tax` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `adjustment` decimal(15,2) DEFAULT NULL,
  `addedfrom` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `vendornote` text DEFAULT NULL,
  `adminnote` text DEFAULT NULL,
  `discount_percent` decimal(15,2) DEFAULT 0.00,
  `discount_total` decimal(15,2) DEFAULT 0.00,
  `discount_type` varchar(30) DEFAULT NULL,
  `invoiceid` int(11) DEFAULT NULL,
  `invoiced_date` datetime DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `buyer` int(11) NOT NULL DEFAULT 0,
  `billing_street` varchar(200) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_zip` varchar(100) DEFAULT NULL,
  `billing_country` int(11) DEFAULT NULL,
  `shipping_street` varchar(200) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_zip` varchar(100) DEFAULT NULL,
  `shipping_country` int(11) DEFAULT NULL,
  `include_shipping` tinyint(1) NOT NULL,
  `show_shipping_on_estimate` tinyint(1) NOT NULL DEFAULT 1,
  `show_quantity_as` int(11) NOT NULL DEFAULT 1,
  `pipeline_order` int(11) NOT NULL DEFAULT 0,
  `is_expiry_notified` int(11) NOT NULL DEFAULT 0,
  `acceptance_firstname` varchar(50) DEFAULT NULL,
  `acceptance_lastname` varchar(50) DEFAULT NULL,
  `acceptance_email` varchar(100) DEFAULT NULL,
  `acceptance_date` datetime DEFAULT NULL,
  `acceptance_ip` varchar(40) DEFAULT NULL,
  `signature` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_estimate_detail`
--

CREATE TABLE `tblpur_estimate_detail` (
  `id` int(11) NOT NULL,
  `pur_estimate` int(11) NOT NULL,
  `item_code` varchar(100) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `unit_price` decimal(15,0) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `into_money` decimal(15,2) DEFAULT NULL,
  `tax` text DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `total_money` decimal(15,2) DEFAULT NULL,
  `discount_money` decimal(15,2) DEFAULT NULL,
  `discount_%` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_invoices`
--

CREATE TABLE `tblpur_invoices` (
  `id` int(11) UNSIGNED NOT NULL,
  `number` int(11) NOT NULL,
  `invoice_number` text DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT NULL,
  `tax_rate` int(11) DEFAULT NULL,
  `tax` decimal(15,2) DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `contract` int(11) DEFAULT NULL,
  `vendor` int(11) DEFAULT NULL,
  `transactionid` mediumtext DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `payment_request_status` varchar(30) DEFAULT NULL,
  `payment_status` varchar(30) DEFAULT NULL,
  `vendor_note` text DEFAULT NULL,
  `adminnote` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `add_from` int(11) DEFAULT NULL,
  `date_add` date DEFAULT NULL,
  `pur_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_invoice_payment`
--

CREATE TABLE `tblpur_invoice_payment` (
  `id` int(11) UNSIGNED NOT NULL,
  `pur_invoice` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paymentmode` longtext DEFAULT NULL,
  `date` date NOT NULL,
  `daterecorded` datetime NOT NULL,
  `note` text NOT NULL,
  `transactionid` mediumtext DEFAULT NULL,
  `approval_status` int(2) DEFAULT NULL,
  `requester` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_orders`
--

CREATE TABLE `tblpur_orders` (
  `id` int(11) UNSIGNED NOT NULL,
  `pur_order_name` varchar(100) NOT NULL,
  `vendor` int(11) NOT NULL,
  `estimate` int(11) NOT NULL,
  `pur_order_number` varchar(30) NOT NULL,
  `order_date` date NOT NULL,
  `status` int(32) NOT NULL DEFAULT 1,
  `approve_status` int(32) NOT NULL DEFAULT 1,
  `datecreated` datetime NOT NULL,
  `days_owed` int(11) NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `total_tax` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `addedfrom` int(11) NOT NULL,
  `vendornote` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `discount_percent` decimal(15,2) DEFAULT 0.00,
  `discount_total` decimal(15,2) DEFAULT 0.00,
  `discount_type` varchar(30) DEFAULT NULL,
  `buyer` int(11) NOT NULL DEFAULT 0,
  `status_goods` int(11) NOT NULL DEFAULT 0,
  `number` int(11) DEFAULT NULL,
  `expense_convert` int(11) DEFAULT 0,
  `hash` varchar(32) DEFAULT NULL,
  `clients` text DEFAULT NULL,
  `delivery_status` int(2) DEFAULT 0,
  `type` text DEFAULT NULL,
  `project` int(11) DEFAULT NULL,
  `pur_request` int(11) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  `tax_order_rate` decimal(15,2) DEFAULT NULL,
  `tax_order_amount` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_order_detail`
--

CREATE TABLE `tblpur_order_detail` (
  `id` int(11) NOT NULL,
  `pur_order` int(11) NOT NULL,
  `item_code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `into_money` decimal(15,2) DEFAULT NULL,
  `tax` text DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `discount_%` decimal(15,2) DEFAULT NULL,
  `discount_money` decimal(15,2) DEFAULT NULL,
  `total_money` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_order_payment`
--

CREATE TABLE `tblpur_order_payment` (
  `id` int(11) UNSIGNED NOT NULL,
  `pur_order` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paymentmode` longtext DEFAULT NULL,
  `date` date NOT NULL,
  `daterecorded` datetime NOT NULL,
  `note` text NOT NULL,
  `transactionid` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_request`
--

CREATE TABLE `tblpur_request` (
  `id` int(11) NOT NULL,
  `pur_rq_code` varchar(45) NOT NULL,
  `pur_rq_name` varchar(100) NOT NULL,
  `rq_description` text DEFAULT NULL,
  `requester` int(11) NOT NULL,
  `department` int(11) NOT NULL,
  `request_date` datetime NOT NULL,
  `status` int(11) DEFAULT NULL,
  `status_goods` int(11) NOT NULL DEFAULT 0,
  `hash` varchar(32) DEFAULT NULL,
  `type` text DEFAULT NULL,
  `project` int(11) DEFAULT NULL,
  `number` int(11) DEFAULT NULL,
  `from_items` int(2) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_request_detail`
--

CREATE TABLE `tblpur_request_detail` (
  `prd_id` int(11) NOT NULL,
  `pur_request` int(11) NOT NULL,
  `item_code` varchar(100) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `into_money` decimal(15,2) DEFAULT NULL,
  `inventory_quantity` int(11) DEFAULT 0,
  `item_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_unit`
--

CREATE TABLE `tblpur_unit` (
  `unit_id` int(11) NOT NULL,
  `unit_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_vendor`
--

CREATE TABLE `tblpur_vendor` (
  `userid` int(11) UNSIGNED NOT NULL,
  `company` varchar(200) DEFAULT NULL,
  `vat` varchar(200) DEFAULT NULL,
  `phonenumber` varchar(30) DEFAULT NULL,
  `country` int(11) NOT NULL DEFAULT 0,
  `city` varchar(100) DEFAULT NULL,
  `zip` varchar(15) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `leadid` int(11) DEFAULT NULL,
  `billing_street` varchar(200) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_zip` varchar(100) DEFAULT NULL,
  `billing_country` int(11) DEFAULT 0,
  `shipping_street` varchar(200) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_zip` varchar(100) DEFAULT NULL,
  `shipping_country` int(11) DEFAULT 0,
  `longitude` varchar(191) DEFAULT NULL,
  `latitude` varchar(191) DEFAULT NULL,
  `default_language` varchar(40) DEFAULT NULL,
  `default_currency` int(11) NOT NULL DEFAULT 0,
  `show_primary_contact` int(11) NOT NULL DEFAULT 0,
  `stripe_id` varchar(40) DEFAULT NULL,
  `registration_confirmed` int(11) NOT NULL DEFAULT 1,
  `addedfrom` int(11) NOT NULL DEFAULT 0,
  `category` text DEFAULT NULL,
  `bank_detail` text DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `vendor_code` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_vendor_admin`
--

CREATE TABLE `tblpur_vendor_admin` (
  `staff_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `date_assigned` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_vendor_cate`
--

CREATE TABLE `tblpur_vendor_cate` (
  `id` int(11) UNSIGNED NOT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblpur_vendor_items`
--

CREATE TABLE `tblpur_vendor_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `vendor` int(11) NOT NULL,
  `group_items` int(11) DEFAULT NULL,
  `items` int(11) NOT NULL,
  `add_from` int(11) DEFAULT NULL,
  `datecreate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblrelated_items`
--

CREATE TABLE `tblrelated_items` (
  `id` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(30) NOT NULL,
  `item_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblreminders`
--

CREATE TABLE `tblreminders` (
  `id` int(11) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `date` datetime NOT NULL,
  `isnotified` int(11) NOT NULL DEFAULT 0,
  `rel_id` int(11) NOT NULL,
  `staff` int(11) NOT NULL,
  `rel_type` varchar(40) NOT NULL,
  `notify_by_email` int(11) NOT NULL DEFAULT 1,
  `creator` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblroles`
--

CREATE TABLE `tblroles` (
  `roleid` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `permissions` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblroles`
--

INSERT INTO `tblroles` (`roleid`, `name`, `permissions`) VALUES
(1, 'Employee', NULL),
(2, 'General Manager ', 'a:5:{s:17:\"bulk_pdf_exporter\";a:1:{i:0;s:4:\"view\";}s:9:\"contracts\";a:1:{i:0;s:4:\"view\";}s:12:\"credit_notes\";a:3:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";}s:9:\"proposals\";a:5:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";i:4;s:18:\"view_all_templates\";}s:8:\"settings\";a:1:{i:0;s:4:\"view\";}}'),
(3, 'System Administrator', 'a:0:{}'),
(4, 'Finance Manager', 'a:0:{}'),
(5, 'Sales Manager', 'a:15:{s:17:\"bulk_pdf_exporter\";a:1:{i:0;s:4:\"view\";}s:9:\"contracts\";a:5:{i:0;s:8:\"view_own\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";i:4;s:18:\"view_all_templates\";}s:9:\"customers\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:8:\"invoices\";a:3:{i:0;s:8:\"view_own\";i:1;s:6:\"create\";i:2;s:4:\"edit\";}s:5:\"items\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:8:\"payments\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:9:\"proposals\";a:5:{i:0;s:8:\"view_own\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";i:4;s:18:\"view_all_templates\";}s:7:\"reports\";a:2:{i:0;s:4:\"view\";i:1;s:15:\"view-timesheets\";}s:8:\"settings\";a:2:{i:0;s:4:\"view\";i:1;s:4:\"edit\";}s:19:\"checklist_templates\";a:2:{i:0;s:6:\"create\";i:1;s:6:\"delete\";}s:16:\"estimate_request\";a:4:{i:0;s:8:\"view_own\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:5:\"leads\";a:2:{i:0;s:4:\"view\";i:1;s:6:\"delete\";}s:25:\"si_export_customer_matrix\";a:1:{i:0;s:4:\"view\";}s:27:\"si_export_customer_services\";a:1:{i:0;s:4:\"view\";}s:8:\"supplier\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}}'),
(6, 'Sales Executive', 'a:33:{s:17:\"bulk_pdf_exporter\";a:1:{i:0;s:4:\"view\";}s:9:\"contracts\";a:5:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";i:4;s:18:\"view_all_templates\";}s:12:\"credit_notes\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:9:\"customers\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:15:\"email_templates\";a:2:{i:0;s:4:\"view\";i:1;s:4:\"edit\";}s:9:\"estimates\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:8:\"expenses\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:8:\"invoices\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:5:\"items\";a:3:{i:0;s:6:\"create\";i:1;s:4:\"edit\";i:2;s:6:\"delete\";}s:14:\"knowledge_base\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:8:\"payments\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:8:\"projects\";a:7:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";i:4;s:17:\"create_milestones\";i:5;s:15:\"edit_milestones\";i:6;s:17:\"delete_milestones\";}s:9:\"proposals\";a:5:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";i:4;s:18:\"view_all_templates\";}s:7:\"reports\";a:2:{i:0;s:4:\"view\";i:1;s:15:\"view-timesheets\";}s:5:\"roles\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:8:\"settings\";a:2:{i:0;s:4:\"view\";i:1;s:4:\"edit\";}s:5:\"staff\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:13:\"subscriptions\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:5:\"tasks\";a:8:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";i:4;s:14:\"edit_timesheet\";i:5;s:18:\"edit_own_timesheet\";i:6;s:16:\"delete_timesheet\";i:7;s:20:\"delete_own_timesheet\";}s:19:\"checklist_templates\";a:2:{i:0;s:6:\"create\";i:1;s:6:\"delete\";}s:16:\"estimate_request\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:5:\"leads\";a:1:{i:0;s:4:\"view\";}s:20:\"accounting_dashboard\";a:1:{i:0;s:4:\"view\";}s:22:\"accounting_transaction\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:24:\"accounting_journal_entry\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:19:\"accounting_transfer\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:28:\"accounting_chart_of_accounts\";a:3:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";}s:8:\"purchase\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:18:\"si_export_customer\";a:1:{i:0;s:4:\"view\";}s:25:\"si_export_customer_matrix\";a:1:{i:0;s:4:\"view\";}s:27:\"si_export_customer_services\";a:1:{i:0;s:4:\"view\";}s:8:\"supplier\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}s:9:\"warehouse\";a:4:{i:0;s:4:\"view\";i:1;s:6:\"create\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}}'),
(7, 'Sales Representative', 'a:0:{}'),
(8, 'Store Manager', 'a:0:{}'),
(9, 'Storekeeper/Stores Clerk, Studio/Production', 'a:0:{}'),
(10, 'Receptionist/Front Desk', 'a:0:{}'),
(11, 'Purchasing Officer', 'a:0:{}');

-- --------------------------------------------------------

--
-- Table structure for table `tblsales_activity`
--

CREATE TABLE `tblsales_activity` (
  `id` int(11) NOT NULL,
  `rel_type` varchar(20) DEFAULT NULL,
  `rel_id` int(11) NOT NULL,
  `description` mediumtext NOT NULL,
  `additional_data` mediumtext DEFAULT NULL,
  `staffid` varchar(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblscheduled_emails`
--

CREATE TABLE `tblscheduled_emails` (
  `id` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(15) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `contacts` varchar(197) NOT NULL,
  `cc` mediumtext DEFAULT NULL,
  `attach_pdf` tinyint(1) NOT NULL DEFAULT 1,
  `template` varchar(197) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblservices`
--

CREATE TABLE `tblservices` (
  `serviceid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblsessions`
--

CREATE TABLE `tblsessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblsessions`
--

INSERT INTO `tblsessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES
('00d035de6a824a9ad1b4d193d950914c', '::1', 1776078013, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037383031333b5f707265765f75726c7c733a33363a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f73657474696e6773223b73746166665f757365725f69647c733a313a2232223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f327c693a313737363037373430383b617070726f76616c735f70656e64696e675f636f756e745f327c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037383036393b7d),
('05fb9809e52b53d72f694354e66f6a19', '::1', 1776069937, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036393933373b5f707265765f75726c7c733a34323a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f73746166662f6d656d6265722f33223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363036393932383b7d),
('072077e63e8ede6163d5cb98e404d0a6', '::1', 1776065468, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036353436383b5f707265765f75726c7c733a34323a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f73746166662f6d656d6265722f31223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b),
('098ffd3956ac2620b106067492a6e889', '::1', 1776067863, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036373836333b5f707265765f75726c7c733a33373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f617070726f76616c73223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363036373931343b7d),
('0d1371bab2db409cd6f74a5e4a77f45a', '::1', 1776077711, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037373731313b5f707265765f75726c7c733a33373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f70726f706f73616c73223b73746166665f757365725f69647c733a313a2232223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f327c693a313737363037373430383b617070726f76616c735f70656e64696e675f636f756e745f327c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037373734323b7d),
('0e51ad5b04bd60173c333c1351318a43', '::1', 1776079483, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037393438333b5f707265765f75726c7c733a33363a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f657870656e736573223b73746166665f757365725f69647c733a313a2232223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f327c693a313737363037393432353b617070726f76616c735f70656e64696e675f636f756e745f327c613a323a7b733a353a22636f756e74223b693a313b733a373a2265787069726573223b693a313737363037393439393b7d),
('1a289efd9945dfebb1b10bf8aa22f83a', '::1', 1776066188, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036363138383b5f707265765f75726c7c733a33353a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f6d6f64756c6573223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b),
('1f776c0412158100b3d9c3e60960ac43', '::1', 1776076535, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037363533353b5f707265765f75726c7c733a34363a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f70726f706f73616c732f70726f706f73616c223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037363532363b7d),
('28c3a3751c93cbe7a4fa1cf5a6c8139d', '::1', 1776074482, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037343438323b5f707265765f75726c7c733a33373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f617070726f76616c73223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a323b733a373a2265787069726573223b693a313737363037343437323b7d617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f317c693a313737363037333439313b),
('2c3d80616ab2e0a90e945078bf1b561a', '::1', 1776078409, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037383430393b5f707265765f75726c7c733a32373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e223b73746166665f757365725f69647c733a313a2232223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f327c693a313737363037373430383b617070726f76616c735f70656e64696e675f636f756e745f327c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037383339363b7d),
('3151275e2bf990413e7ca07567391c55', '::1', 1776064657, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036343635373b5f707265765f75726c7c733a33333a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f7374616666223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b),
('46eff0eb5731102b8bd0a0aaf2c403a4', '::1', 1776078711, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037383731313b5f707265765f75726c7c733a34303a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f726f6c65732f726f6c652f35223b73746166665f757365725f69647c733a313a2232223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f327c693a313737363037373430383b617070726f76616c735f70656e64696e675f636f756e745f327c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037383735323b7d),
('5d6629bcd9f71d569e062a0e48fbec9f', '::1', 1776068295, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036383239353b5f707265765f75726c7c733a32373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363036383330383b7d),
('5e4eb85b86234be801d5f9cd4e74e215', '::1', 1776086693, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037393438333b5f707265765f75726c7c733a32373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e223b73746166665f757365725f69647c733a313a2232223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f327c693a313737363037393538313b617070726f76616c735f70656e64696e675f636f756e745f327c613a323a7b733a353a22636f756e74223b693a323b733a373a2265787069726573223b693a313737363038363735333b7d),
('688acf8ce4504ec5b3a3273d58076b74', '::1', 1776065108, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036353130383b5f707265765f75726c7c733a34323a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f73746166662f6d656d6265722f31223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b),
('69787824ebbfe4ba6cb483173f3a79bc', '::1', 1776071762, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037313736323b5f707265765f75726c7c733a33373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f70726f706f73616c73223b73746166665f757365725f69647c733a313a2233223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f337c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037313831383b7d),
('6c3cab3d64e7e666f63c01e03ec6204e', '::1', 1776068609, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036383630393b5f707265765f75726c7c733a35343a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f70726f706f73616c732f6c6973745f70726f706f73616c732f31223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363036383636383b7d),
('743302ecf24f205d3c04b6663b2af94c', '::1', 1776073198, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037333139383b5f707265765f75726c7c733a33373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f617070726f76616c73223b73746166665f757365725f69647c733a313a2233223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f337c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037333235383b7d),
('7a65749002f0c4407d011214ad6d18f3', '::1', 1776076088, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037363038383b5f707265765f75726c7c733a35343a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f70726f706f73616c732f6c6973745f70726f706f73616c732f32223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f317c693a313737363037363035343b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037363131343b7d),
('7e965581bcea600e3c3455550006a350', '::1', 1776079143, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037393134333b5f707265765f75726c7c733a34303a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f726f6c65732f726f6c652f36223b73746166665f757365725f69647c733a313a2232223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f327c693a313737363037373430383b617070726f76616c735f70656e64696e675f636f756e745f327c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037393133343b7d),
('96112770817f2e48b4de2612ead675a1', '::1', 1776072451, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037323435313b5f707265765f75726c7c733a33373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f617070726f76616c73223b73746166665f757365725f69647c733a313a2233223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f337c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037323434383b7d),
('9a6e84ddc0232c3f29debd5842cd71b7', '::1', 1776079376, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037393337363b5f707265765f75726c7c733a35343a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f70726f706f73616c732f6c6973745f70726f706f73616c732f35223b73746166665f757365725f69647c733a313a2234223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b),
('9b232f7633f4398a9a7be9480f756a19', '::1', 1776069582, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036393538323b5f707265765f75726c7c733a34303a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f73746166662f6d656d626572223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363036393634303b7d),
('9bfda72ca438210ba7fe131ddcff3075', '::1', 1776079557, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037393337363b5f707265765f75726c7c733a35343a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f70726f706f73616c732f6c6973745f70726f706f73616c732f36223b73746166665f757365725f69647c733a313a2234223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b),
('a699107ef29be1b9575b5e40d967b0e3', '::1', 1776078695, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037383639353b5f707265765f75726c7c733a32373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e223b73746166665f757365725f69647c733a313a2234223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b),
('ac6d315be482530dbc4eeca38e342cf9', '::1', 1776066945, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036363934353b5f707265765f75726c7c733a34313a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f6163636573735f64656e696564223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b6d6573736167652d64616e6765727c733a31333a224163636573732064656e696564223b5f5f63695f766172737c613a313a7b733a31343a226d6573736167652d64616e676572223b733a333a226f6c64223b7d),
('b83cb518cdd60ab3abc89e6ac3b1bbd7', '::1', 1776079023, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037393032333b5f707265765f75726c7c733a32373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e223b73746166665f757365725f69647c733a313a2234223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b),
('c3b9a1cd7bc29732a7286541291253c6', '::1', 1776078269, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037383236393b5f707265765f75726c7c733a34343a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f617070726f76616c732f766965772f33223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037363737353b7d),
('d4385d41e4429168c4c88fa5795e99b6', '::1', 1776075759, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037353735393b5f707265765f75726c7c733a33373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f617070726f76616c73223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a323b733a373a2265787069726573223b693a313737363037353737333b7d617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f317c693a313737363037353136383b),
('d9d06ff52f2e7acb3e5ddde9bbdadb06', '::1', 1776077407, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037373430373b5f707265765f75726c7c733a33373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f617070726f76616c73223b73746166665f757365725f69647c733a313a2232223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f327c693a313737363037363537363b617070726f76616c735f70656e64696e675f636f756e745f327c613a323a7b733a353a22636f756e74223b693a303b733a373a2265787069726573223b693a313737363037363833383b7d),
('da42823d52949176ce715dfdf887133d', '::1', 1776064326, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036343332363b5f707265765f75726c7c733a32373a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b),
('fb6349df03288c21db35533799b82755', '::1', 1776066495, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363036363439353b5f707265765f75726c7c733a34323a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f73746166662f6d656d6265722f31223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c623a313b),
('ff068d6244e086d892bf5ec0ae497e06', '::1', 1776074912, 0x5f5f63695f6c6173745f726567656e65726174657c693a313737363037343931323b5f707265765f75726c7c733a34343a22687474703a2f2f6c6f63616c686f73742f6d6567612f61646d696e2f617070726f76616c732f766965772f32223b73746166665f757365725f69647c733a313a2231223b73746166665f6c6f676765645f696e7c623a313b73657475702d6d656e752d6f70656e7c733a303a22223b617070726f76616c735f70656e64696e675f636f756e745f317c613a323a7b733a353a22636f756e74223b693a323b733a373a2265787069726573223b693a313737363037343936323b7d617070726f76616c735f70726f706f73616c5f6261636b66696c6c5f6c6173745f72756e5f317c693a313737363037343438323b);

-- --------------------------------------------------------

--
-- Table structure for table `tblshared_customer_files`
--

CREATE TABLE `tblshared_customer_files` (
  `file_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblsi_export_customer_kyc_files`
--

CREATE TABLE `tblsi_export_customer_kyc_files` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `files_id` text NOT NULL,
  `profile_file_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblsi_export_customer_services`
--

CREATE TABLE `tblsi_export_customer_services` (
  `client_id` int(11) NOT NULL DEFAULT 0,
  `item_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblspam_filters`
--

CREATE TABLE `tblspam_filters` (
  `id` int(11) NOT NULL,
  `type` varchar(40) NOT NULL,
  `rel_type` varchar(10) NOT NULL,
  `value` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblstaff`
--

CREATE TABLE `tblstaff` (
  `staffid` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `facebook` longtext DEFAULT NULL,
  `linkedin` longtext DEFAULT NULL,
  `phonenumber` varchar(30) DEFAULT NULL,
  `skype` varchar(50) DEFAULT NULL,
  `password` varchar(250) NOT NULL,
  `datecreated` datetime NOT NULL,
  `profile_image` varchar(191) DEFAULT NULL,
  `last_ip` varchar(40) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `last_password_change` datetime DEFAULT NULL,
  `new_pass_key` varchar(32) DEFAULT NULL,
  `new_pass_key_requested` datetime DEFAULT NULL,
  `admin` int(11) NOT NULL DEFAULT 0,
  `role` int(11) DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `default_language` varchar(40) DEFAULT NULL,
  `direction` varchar(3) DEFAULT NULL,
  `media_path_slug` varchar(191) DEFAULT NULL,
  `is_not_staff` int(11) NOT NULL DEFAULT 0,
  `hourly_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `two_factor_auth_enabled` tinyint(1) DEFAULT 0,
  `two_factor_auth_code` varchar(100) DEFAULT NULL,
  `two_factor_auth_code_requested` datetime DEFAULT NULL,
  `email_signature` mediumtext DEFAULT NULL,
  `google_auth_secret` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblstaff`
--

INSERT INTO `tblstaff` (`staffid`, `email`, `firstname`, `lastname`, `facebook`, `linkedin`, `phonenumber`, `skype`, `password`, `datecreated`, `profile_image`, `last_ip`, `last_login`, `last_activity`, `last_password_change`, `new_pass_key`, `new_pass_key_requested`, `admin`, `role`, `active`, `default_language`, `direction`, `media_path_slug`, `is_not_staff`, `hourly_rate`, `two_factor_auth_enabled`, `two_factor_auth_code`, `two_factor_auth_code_requested`, `email_signature`, `google_auth_secret`) VALUES
(1, 'info@express.mw', 'Mega Signs', 'Ltd', '', '', '', '', '$2a$08$Gk.hRMDkZwNZI7UcJr4wBOw61o7/z14qC2rNOo4ZgoWKoUI9tcHcm', '2026-04-12 11:22:56', NULL, '::1', '2026-04-13 12:38:22', '2026-04-13 13:04:33', NULL, NULL, NULL, 1, 2, 1, '', '', NULL, 0, 0.00, 0, NULL, NULL, '', NULL),
(2, 'sales@demo.mw', 'sam', 'zarila', '', '', '', '', '$2a$08$fW.V4nV496RL8o9dfquW6.rFgqFvVU6AmVzgY58UsrNmcntJGgG9i', '2026-04-13 10:13:39', NULL, '::1', '2026-04-13 12:36:02', '2026-04-13 15:24:53', NULL, NULL, NULL, 1, 5, 1, '', '', 'sam-zarila', 0, 0.00, 0, NULL, NULL, '', NULL),
(3, 'john@gm.mw', 'John ', 'Doe', '', '', '', '', '$2a$08$rgYzP4loXIXtUwtlR19P6uk1dAynvVPRdNGioTVv2LQ2Uw36YZBoS', '2026-04-13 10:39:43', NULL, '::1', '2026-04-13 12:28:26', '2026-04-13 12:29:17', NULL, NULL, NULL, 1, 2, 1, '', '', 'john-doe', 0, 0.00, 0, NULL, NULL, '', NULL),
(4, 'sales@express.mw', 'Alex Sales Executive', 'Mayo', '', '', '', '', '$2a$08$RQzHvv0rE2LLuxf5zirhqev/OAfdlyZDECrV9EGDkah3YH0CCGy7.', '2026-04-13 13:03:39', NULL, '::1', '2026-04-13 13:04:55', '2026-04-13 13:25:56', NULL, NULL, NULL, 0, 6, 1, '', '', 'alex-sales-executive-mayo', 0, 0.00, 0, NULL, NULL, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblstaff_departments`
--

CREATE TABLE `tblstaff_departments` (
  `staffdepartmentid` int(11) NOT NULL,
  `staffid` int(11) NOT NULL,
  `departmentid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblstaff_permissions`
--

CREATE TABLE `tblstaff_permissions` (
  `staff_id` int(11) NOT NULL,
  `feature` varchar(40) NOT NULL,
  `capability` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblstaff_permissions`
--

INSERT INTO `tblstaff_permissions` (`staff_id`, `feature`, `capability`) VALUES
(2, 'bulk_pdf_exporter', 'view'),
(2, 'contracts', 'view_own'),
(2, 'contracts', 'create'),
(2, 'contracts', 'edit'),
(2, 'contracts', 'delete'),
(2, 'contracts', 'view_all_templates'),
(2, 'customers', 'view'),
(2, 'customers', 'create'),
(2, 'customers', 'edit'),
(2, 'customers', 'delete'),
(2, 'invoices', 'view_own'),
(2, 'invoices', 'create'),
(2, 'invoices', 'edit'),
(2, 'items', 'view'),
(2, 'items', 'create'),
(2, 'items', 'edit'),
(2, 'items', 'delete'),
(2, 'payments', 'view'),
(2, 'payments', 'create'),
(2, 'payments', 'edit'),
(2, 'payments', 'delete'),
(2, 'proposals', 'view_own'),
(2, 'proposals', 'create'),
(2, 'proposals', 'edit'),
(2, 'proposals', 'delete'),
(2, 'proposals', 'view_all_templates'),
(2, 'reports', 'view'),
(2, 'reports', 'view-timesheets'),
(2, 'settings', 'view'),
(2, 'settings', 'edit'),
(2, 'checklist_templates', 'create'),
(2, 'checklist_templates', 'delete'),
(2, 'estimate_request', 'view_own'),
(2, 'estimate_request', 'create'),
(2, 'estimate_request', 'edit'),
(2, 'estimate_request', 'delete'),
(2, 'leads', 'view'),
(2, 'leads', 'delete'),
(2, 'si_export_customer_matrix', 'view'),
(2, 'si_export_customer_services', 'view'),
(2, 'supplier', 'view'),
(2, 'supplier', 'create'),
(2, 'supplier', 'edit'),
(2, 'supplier', 'delete'),
(4, 'bulk_pdf_exporter', 'view'),
(4, 'contracts', 'view'),
(4, 'contracts', 'create'),
(4, 'contracts', 'edit'),
(4, 'contracts', 'delete'),
(4, 'contracts', 'view_all_templates'),
(4, 'credit_notes', 'view'),
(4, 'credit_notes', 'create'),
(4, 'credit_notes', 'edit'),
(4, 'credit_notes', 'delete'),
(4, 'customers', 'view'),
(4, 'customers', 'create'),
(4, 'customers', 'edit'),
(4, 'customers', 'delete'),
(4, 'email_templates', 'view'),
(4, 'email_templates', 'edit'),
(4, 'estimates', 'view'),
(4, 'estimates', 'create'),
(4, 'estimates', 'edit'),
(4, 'estimates', 'delete'),
(4, 'expenses', 'view'),
(4, 'expenses', 'create'),
(4, 'expenses', 'edit'),
(4, 'expenses', 'delete'),
(4, 'invoices', 'view'),
(4, 'invoices', 'create'),
(4, 'invoices', 'edit'),
(4, 'invoices', 'delete'),
(4, 'items', 'create'),
(4, 'items', 'edit'),
(4, 'items', 'delete'),
(4, 'knowledge_base', 'view'),
(4, 'knowledge_base', 'create'),
(4, 'knowledge_base', 'edit'),
(4, 'knowledge_base', 'delete'),
(4, 'payments', 'view'),
(4, 'payments', 'create'),
(4, 'payments', 'edit'),
(4, 'payments', 'delete'),
(4, 'projects', 'view'),
(4, 'projects', 'create'),
(4, 'projects', 'edit'),
(4, 'projects', 'delete'),
(4, 'projects', 'create_milestones'),
(4, 'projects', 'edit_milestones'),
(4, 'projects', 'delete_milestones'),
(4, 'proposals', 'view'),
(4, 'proposals', 'create'),
(4, 'proposals', 'edit'),
(4, 'proposals', 'delete'),
(4, 'proposals', 'view_all_templates'),
(4, 'reports', 'view'),
(4, 'reports', 'view-timesheets'),
(4, 'roles', 'view'),
(4, 'roles', 'create'),
(4, 'roles', 'edit'),
(4, 'roles', 'delete'),
(4, 'settings', 'view'),
(4, 'settings', 'edit'),
(4, 'staff', 'view'),
(4, 'staff', 'create'),
(4, 'staff', 'edit'),
(4, 'staff', 'delete'),
(4, 'subscriptions', 'view'),
(4, 'subscriptions', 'create'),
(4, 'subscriptions', 'edit'),
(4, 'subscriptions', 'delete'),
(4, 'tasks', 'view'),
(4, 'tasks', 'create'),
(4, 'tasks', 'edit'),
(4, 'tasks', 'delete'),
(4, 'tasks', 'edit_timesheet'),
(4, 'tasks', 'edit_own_timesheet'),
(4, 'tasks', 'delete_timesheet'),
(4, 'tasks', 'delete_own_timesheet'),
(4, 'checklist_templates', 'create'),
(4, 'checklist_templates', 'delete'),
(4, 'estimate_request', 'view'),
(4, 'estimate_request', 'create'),
(4, 'estimate_request', 'edit'),
(4, 'estimate_request', 'delete'),
(4, 'leads', 'view'),
(4, 'accounting_dashboard', 'view'),
(4, 'accounting_transaction', 'view'),
(4, 'accounting_transaction', 'create'),
(4, 'accounting_transaction', 'edit'),
(4, 'accounting_transaction', 'delete'),
(4, 'accounting_journal_entry', 'view'),
(4, 'accounting_journal_entry', 'create'),
(4, 'accounting_journal_entry', 'edit'),
(4, 'accounting_journal_entry', 'delete'),
(4, 'accounting_transfer', 'view'),
(4, 'accounting_transfer', 'create'),
(4, 'accounting_transfer', 'edit'),
(4, 'accounting_transfer', 'delete'),
(4, 'accounting_chart_of_accounts', 'view'),
(4, 'accounting_chart_of_accounts', 'create'),
(4, 'accounting_chart_of_accounts', 'edit'),
(4, 'purchase', 'view'),
(4, 'purchase', 'create'),
(4, 'purchase', 'edit'),
(4, 'purchase', 'delete'),
(4, 'si_export_customer', 'view'),
(4, 'si_export_customer_matrix', 'view'),
(4, 'si_export_customer_services', 'view'),
(4, 'supplier', 'view'),
(4, 'supplier', 'create'),
(4, 'supplier', 'edit'),
(4, 'supplier', 'delete'),
(4, 'warehouse', 'view'),
(4, 'warehouse', 'create'),
(4, 'warehouse', 'edit'),
(4, 'warehouse', 'delete');

-- --------------------------------------------------------

--
-- Table structure for table `tblstock_take`
--

CREATE TABLE `tblstock_take` (
  `id` int(11) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL COMMENT 'the reason stock take',
  `warehouse_id` int(11) DEFAULT NULL,
  `date_stock_take` date DEFAULT NULL,
  `stock_take_code` varchar(100) DEFAULT NULL COMMENT 'số kiểm kê kho',
  `date_add` date DEFAULT NULL,
  `hour_add` date DEFAULT NULL,
  `staff_id` varchar(100) DEFAULT NULL,
  `approval` int(11) DEFAULT 0 COMMENT 'status approval ',
  `addedfrom` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblstock_take_detail`
--

CREATE TABLE `tblstock_take_detail` (
  `id` int(11) UNSIGNED NOT NULL,
  `stock_take_id` int(11) NOT NULL,
  `commodity_code` varchar(100) DEFAULT NULL,
  `commodity_name` text DEFAULT NULL,
  `unit_id` text DEFAULT NULL,
  `unit_price` varchar(100) DEFAULT NULL,
  `quantity_stock_take` varchar(100) DEFAULT NULL,
  `quantity_accounting_book` varchar(100) DEFAULT NULL,
  `quantity_change` varchar(100) DEFAULT NULL,
  `handling` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `approval` int(11) DEFAULT 0 COMMENT 'status approval '
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblsubscriptions`
--

CREATE TABLE `tblsubscriptions` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `description_in_item` tinyint(1) NOT NULL DEFAULT 0,
  `clientid` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `terms` mediumtext DEFAULT NULL,
  `currency` int(11) NOT NULL,
  `tax_id` int(11) NOT NULL DEFAULT 0,
  `stripe_tax_id` varchar(50) DEFAULT NULL,
  `tax_id_2` int(11) NOT NULL DEFAULT 0,
  `stripe_tax_id_2` varchar(50) DEFAULT NULL,
  `stripe_plan_id` mediumtext DEFAULT NULL,
  `stripe_subscription_id` mediumtext NOT NULL,
  `next_billing_cycle` bigint(20) DEFAULT NULL,
  `ends_at` bigint(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `hash` varchar(32) NOT NULL,
  `created` datetime NOT NULL,
  `created_from` int(11) NOT NULL,
  `date_subscribed` datetime DEFAULT NULL,
  `in_test_environment` int(11) DEFAULT NULL,
  `last_sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltaggables`
--

CREATE TABLE `tbltaggables` (
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(20) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `tag_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbltaggables`
--

INSERT INTO `tbltaggables` (`rel_id`, `rel_type`, `tag_id`, `tag_order`) VALUES
(5, 'proposal', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbltags`
--

CREATE TABLE `tbltags` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbltags`
--

INSERT INTO `tbltags` (`id`, `name`) VALUES
(1, '+');

-- --------------------------------------------------------

--
-- Table structure for table `tbltasks`
--

CREATE TABLE `tbltasks` (
  `id` int(11) NOT NULL,
  `name` longtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  `dateadded` datetime NOT NULL,
  `startdate` date NOT NULL,
  `duedate` date DEFAULT NULL,
  `datefinished` datetime DEFAULT NULL,
  `addedfrom` int(11) NOT NULL,
  `is_added_from_contact` tinyint(1) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `recurring_type` varchar(10) DEFAULT NULL,
  `repeat_every` int(11) DEFAULT NULL,
  `recurring` int(11) NOT NULL DEFAULT 0,
  `is_recurring_from` int(11) DEFAULT NULL,
  `cycles` int(11) NOT NULL DEFAULT 0,
  `total_cycles` int(11) NOT NULL DEFAULT 0,
  `custom_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `last_recurring_date` date DEFAULT NULL,
  `rel_id` int(11) DEFAULT NULL,
  `rel_type` varchar(30) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `billable` tinyint(1) NOT NULL DEFAULT 0,
  `billed` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_id` int(11) NOT NULL DEFAULT 0,
  `hourly_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `milestone` int(11) DEFAULT 0,
  `kanban_order` int(11) DEFAULT 1,
  `milestone_order` int(11) NOT NULL DEFAULT 0,
  `visible_to_client` tinyint(1) NOT NULL DEFAULT 0,
  `deadline_notified` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltaskstimers`
--

CREATE TABLE `tbltaskstimers` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `start_time` varchar(64) NOT NULL,
  `end_time` varchar(64) DEFAULT NULL,
  `staff_id` int(11) NOT NULL,
  `hourly_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltasks_checklist_templates`
--

CREATE TABLE `tbltasks_checklist_templates` (
  `id` int(11) NOT NULL,
  `description` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltask_assigned`
--

CREATE TABLE `tbltask_assigned` (
  `id` int(11) NOT NULL,
  `staffid` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `assigned_from` int(11) NOT NULL DEFAULT 0,
  `is_assigned_from_contact` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltask_checklist_items`
--

CREATE TABLE `tbltask_checklist_items` (
  `id` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `description` mediumtext NOT NULL,
  `finished` int(11) NOT NULL DEFAULT 0,
  `dateadded` datetime NOT NULL,
  `addedfrom` int(11) NOT NULL,
  `finished_from` int(11) DEFAULT 0,
  `list_order` int(11) NOT NULL DEFAULT 0,
  `assigned` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltask_comments`
--

CREATE TABLE `tbltask_comments` (
  `id` int(11) NOT NULL,
  `content` mediumtext NOT NULL,
  `taskid` int(11) NOT NULL,
  `staffid` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL DEFAULT 0,
  `file_id` int(11) NOT NULL DEFAULT 0,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltask_followers`
--

CREATE TABLE `tbltask_followers` (
  `id` int(11) NOT NULL,
  `staffid` int(11) NOT NULL,
  `taskid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltaxes`
--

CREATE TABLE `tbltaxes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `taxrate` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbltaxes`
--

INSERT INTO `tbltaxes` (`id`, `name`, `taxrate`) VALUES
(1, 'nccic tax', 10.00),
(2, 'Vat', 17.50),
(3, 'Levy', 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbltemplates`
--

CREATE TABLE `tbltemplates` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `addedfrom` int(11) NOT NULL,
  `content` longtext DEFAULT NULL,
  `content_type` varchar(20) NOT NULL DEFAULT 'html'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltickets`
--

CREATE TABLE `tbltickets` (
  `ticketid` int(11) NOT NULL,
  `adminreplying` int(11) NOT NULL DEFAULT 0,
  `userid` int(11) NOT NULL,
  `contactid` int(11) NOT NULL DEFAULT 0,
  `merged_ticket_id` int(11) DEFAULT NULL,
  `email` mediumtext DEFAULT NULL,
  `name` mediumtext DEFAULT NULL,
  `department` int(11) NOT NULL,
  `priority` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `service` int(11) DEFAULT NULL,
  `ticketkey` varchar(32) NOT NULL,
  `subject` varchar(191) NOT NULL,
  `message` mediumtext DEFAULT NULL,
  `admin` int(11) DEFAULT NULL,
  `date` datetime NOT NULL,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `lastreply` datetime DEFAULT NULL,
  `clientread` int(11) NOT NULL DEFAULT 0,
  `adminread` int(11) NOT NULL DEFAULT 0,
  `assigned` int(11) NOT NULL DEFAULT 0,
  `staff_id_replying` int(11) DEFAULT NULL,
  `cc` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltickets_pipe_log`
--

CREATE TABLE `tbltickets_pipe_log` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `email_to` varchar(100) NOT NULL,
  `name` varchar(191) NOT NULL,
  `subject` varchar(191) NOT NULL,
  `message` longtext NOT NULL,
  `email` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltickets_predefined_replies`
--

CREATE TABLE `tbltickets_predefined_replies` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `message` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltickets_priorities`
--

CREATE TABLE `tbltickets_priorities` (
  `priorityid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbltickets_priorities`
--

INSERT INTO `tbltickets_priorities` (`priorityid`, `name`) VALUES
(1, 'Low'),
(2, 'Medium'),
(3, 'High');

-- --------------------------------------------------------

--
-- Table structure for table `tbltickets_status`
--

CREATE TABLE `tbltickets_status` (
  `ticketstatusid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `isdefault` int(11) NOT NULL DEFAULT 0,
  `statuscolor` varchar(7) DEFAULT NULL,
  `statusorder` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbltickets_status`
--

INSERT INTO `tbltickets_status` (`ticketstatusid`, `name`, `isdefault`, `statuscolor`, `statusorder`) VALUES
(1, 'Open', 1, '#ff2d42', 1),
(2, 'In progress', 1, '#22c55e', 2),
(3, 'Answered', 1, '#2563eb', 3),
(4, 'On Hold', 1, '#64748b', 4),
(5, 'Closed', 1, '#03a9f4', 5);

-- --------------------------------------------------------

--
-- Table structure for table `tblticket_attachments`
--

CREATE TABLE `tblticket_attachments` (
  `id` int(11) NOT NULL,
  `ticketid` int(11) NOT NULL,
  `replyid` int(11) DEFAULT NULL,
  `file_name` varchar(191) NOT NULL,
  `filetype` varchar(50) DEFAULT NULL,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblticket_replies`
--

CREATE TABLE `tblticket_replies` (
  `id` int(11) NOT NULL,
  `ticketid` int(11) NOT NULL,
  `userid` int(11) DEFAULT NULL,
  `contactid` int(11) NOT NULL DEFAULT 0,
  `name` mediumtext DEFAULT NULL,
  `email` mediumtext DEFAULT NULL,
  `date` datetime NOT NULL,
  `message` mediumtext DEFAULT NULL,
  `attachment` int(11) DEFAULT NULL,
  `admin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltodos`
--

CREATE TABLE `tbltodos` (
  `todoid` int(11) NOT NULL,
  `description` mediumtext NOT NULL,
  `staffid` int(11) NOT NULL,
  `dateadded` datetime NOT NULL,
  `finished` tinyint(1) NOT NULL,
  `datefinished` datetime DEFAULT NULL,
  `item_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltracked_mails`
--

CREATE TABLE `tbltracked_mails` (
  `id` int(11) NOT NULL,
  `uid` varchar(32) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  `email` varchar(100) NOT NULL,
  `opened` tinyint(1) NOT NULL DEFAULT 0,
  `date_opened` datetime DEFAULT NULL,
  `subject` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltwocheckout_log`
--

CREATE TABLE `tbltwocheckout_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference` varchar(64) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` varchar(25) NOT NULL,
  `created_at` datetime NOT NULL,
  `attempt_reference` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbluser_api`
--

CREATE TABLE `tbluser_api` (
  `id` int(11) NOT NULL,
  `user` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiration_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbluser_auto_login`
--

CREATE TABLE `tbluser_auto_login` (
  `key_id` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_agent` varchar(150) NOT NULL,
  `last_ip` varchar(40) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `staff` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbluser_meta`
--

CREATE TABLE `tbluser_meta` (
  `umeta_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `client_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `contact_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(191) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbluser_meta`
--

INSERT INTO `tbluser_meta` (`umeta_id`, `staff_id`, `client_id`, `contact_id`, `meta_key`, `meta_value`) VALUES
(1, 1, 0, 0, 'recent_searches', '[\"approvals\",\"ap\",\"api\",\"approv\",\"approvsl\"]');

-- --------------------------------------------------------

--
-- Table structure for table `tblvault`
--

CREATE TABLE `tblvault` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `server_address` varchar(191) NOT NULL,
  `port` int(11) DEFAULT NULL,
  `username` varchar(191) NOT NULL,
  `password` mediumtext NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `creator` int(11) NOT NULL,
  `creator_name` varchar(100) DEFAULT NULL,
  `visibility` tinyint(1) NOT NULL DEFAULT 1,
  `share_in_projects` tinyint(1) NOT NULL DEFAULT 0,
  `last_updated` datetime DEFAULT NULL,
  `last_updated_from` varchar(100) DEFAULT NULL,
  `date_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblviews_tracking`
--

CREATE TABLE `tblviews_tracking` (
  `id` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  `view_ip` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwarehouse`
--

CREATE TABLE `tblwarehouse` (
  `warehouse_id` int(11) UNSIGNED NOT NULL,
  `warehouse_code` varchar(100) DEFAULT NULL,
  `warehouse_name` text DEFAULT NULL,
  `warehouse_address` text DEFAULT NULL,
  `order` int(10) DEFAULT NULL,
  `display` int(1) DEFAULT NULL COMMENT 'display 1: display (yes)  0: not displayed (no)',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblware_body_type`
--

CREATE TABLE `tblware_body_type` (
  `body_type_id` int(11) UNSIGNED NOT NULL,
  `body_code` varchar(100) DEFAULT NULL,
  `body_name` text DEFAULT NULL,
  `order` int(10) DEFAULT NULL,
  `display` int(1) DEFAULT NULL COMMENT 'display 1: display (yes)  0: not displayed (no)',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblware_color`
--

CREATE TABLE `tblware_color` (
  `color_id` int(11) UNSIGNED NOT NULL,
  `color_code` varchar(100) DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `color_hex` text DEFAULT NULL,
  `order` int(10) DEFAULT NULL,
  `display` int(1) DEFAULT NULL COMMENT 'display 1: display (yes)  0: not displayed (no)',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblware_commodity_type`
--

CREATE TABLE `tblware_commodity_type` (
  `commodity_type_id` int(11) UNSIGNED NOT NULL,
  `commondity_code` varchar(100) DEFAULT NULL,
  `commondity_name` text DEFAULT NULL,
  `order` int(10) DEFAULT NULL,
  `display` int(1) DEFAULT NULL COMMENT 'display 1: display (yes)  0: not displayed (no)',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblware_size_type`
--

CREATE TABLE `tblware_size_type` (
  `size_type_id` int(11) UNSIGNED NOT NULL,
  `size_code` varchar(100) DEFAULT NULL,
  `size_name` text DEFAULT NULL,
  `size_symbol` text DEFAULT NULL,
  `order` int(10) DEFAULT NULL,
  `display` int(1) DEFAULT NULL COMMENT 'display 1: display (yes)  0: not displayed (no)',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblware_style_type`
--

CREATE TABLE `tblware_style_type` (
  `style_type_id` int(11) UNSIGNED NOT NULL,
  `style_code` varchar(100) DEFAULT NULL,
  `style_barcode` text DEFAULT NULL,
  `style_name` text DEFAULT NULL,
  `order` int(10) DEFAULT NULL,
  `display` int(1) DEFAULT NULL COMMENT 'display 1: display (yes)  0: not displayed (no)',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblware_unit_type`
--

CREATE TABLE `tblware_unit_type` (
  `unit_type_id` int(11) UNSIGNED NOT NULL,
  `unit_code` varchar(100) DEFAULT NULL,
  `unit_name` text DEFAULT NULL,
  `unit_symbol` text DEFAULT NULL,
  `order` int(10) DEFAULT NULL,
  `display` int(1) DEFAULT NULL COMMENT 'display 1: display (yes)  0: not displayed (no)',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblweb_to_lead`
--

CREATE TABLE `tblweb_to_lead` (
  `id` int(11) NOT NULL,
  `form_key` varchar(32) NOT NULL,
  `lead_source` int(11) NOT NULL,
  `lead_status` int(11) NOT NULL,
  `notify_lead_imported` int(11) NOT NULL DEFAULT 1,
  `notify_type` varchar(20) DEFAULT NULL,
  `notify_ids` longtext DEFAULT NULL,
  `responsible` int(11) NOT NULL DEFAULT 0,
  `name` varchar(191) NOT NULL,
  `form_data` longtext DEFAULT NULL,
  `recaptcha` int(11) NOT NULL DEFAULT 0,
  `submit_btn_name` varchar(40) DEFAULT NULL,
  `submit_btn_text_color` varchar(10) DEFAULT '#ffffff',
  `submit_btn_bg_color` varchar(10) DEFAULT '#84c529',
  `success_submit_msg` mediumtext DEFAULT NULL,
  `submit_action` int(11) DEFAULT 0,
  `lead_name_prefix` varchar(255) DEFAULT NULL,
  `submit_redirect_url` longtext DEFAULT NULL,
  `language` varchar(40) DEFAULT NULL,
  `allow_duplicate` int(11) NOT NULL DEFAULT 1,
  `mark_public` int(11) NOT NULL DEFAULT 0,
  `track_duplicate_field` varchar(20) DEFAULT NULL,
  `track_duplicate_field_and` varchar(20) DEFAULT NULL,
  `create_task_on_duplicate` int(11) NOT NULL DEFAULT 0,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwh_activity_log`
--

CREATE TABLE `tblwh_activity_log` (
  `id` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(45) NOT NULL,
  `staffid` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwh_approval_details`
--

CREATE TABLE `tblwh_approval_details` (
  `id` int(11) NOT NULL,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(45) NOT NULL,
  `staffid` varchar(45) DEFAULT NULL,
  `approve` varchar(45) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `approve_action` varchar(255) DEFAULT NULL,
  `reject_action` varchar(255) DEFAULT NULL,
  `approve_value` varchar(255) DEFAULT NULL,
  `reject_value` varchar(255) DEFAULT NULL,
  `staff_approve` int(11) DEFAULT NULL,
  `action` varchar(45) DEFAULT NULL,
  `sender` int(11) DEFAULT NULL,
  `date_send` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwh_approval_setting`
--

CREATE TABLE `tblwh_approval_setting` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `related` varchar(255) NOT NULL,
  `setting` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwh_loss_adjustment`
--

CREATE TABLE `tblwh_loss_adjustment` (
  `id` int(11) UNSIGNED NOT NULL,
  `type` varchar(15) DEFAULT NULL,
  `addfrom` int(11) DEFAULT NULL,
  `reason` longtext DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `date_create` date NOT NULL,
  `status` int(11) NOT NULL,
  `warehouses` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwh_loss_adjustment_detail`
--

CREATE TABLE `tblwh_loss_adjustment_detail` (
  `id` int(11) UNSIGNED NOT NULL,
  `items` int(11) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `current_number` int(15) DEFAULT NULL,
  `updates_number` int(15) DEFAULT NULL,
  `loss_adjustment` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwh_sub_group`
--

CREATE TABLE `tblwh_sub_group` (
  `id` int(11) UNSIGNED NOT NULL,
  `sub_group_code` varchar(100) DEFAULT NULL,
  `sub_group_name` text DEFAULT NULL,
  `order` int(10) DEFAULT NULL,
  `display` int(1) DEFAULT NULL COMMENT 'display 1: display (yes)  0: not displayed (no)',
  `note` text DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblaccount_planning`
--
ALTER TABLE `tblaccount_planning`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblaccount_planning_current_service`
--
ALTER TABLE `tblaccount_planning_current_service`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblaccount_planning_financial`
--
ALTER TABLE `tblaccount_planning_financial`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblaccount_planning_items`
--
ALTER TABLE `tblaccount_planning_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblaccount_planning_marketing_activities`
--
ALTER TABLE `tblaccount_planning_marketing_activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblaccount_planning_objective`
--
ALTER TABLE `tblaccount_planning_objective`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblaccount_planning_service_ability_offering`
--
ALTER TABLE `tblaccount_planning_service_ability_offering`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblaccount_planning_task`
--
ALTER TABLE `tblaccount_planning_task`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblaccount_planning_team`
--
ALTER TABLE `tblaccount_planning_team`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_accounts`
--
ALTER TABLE `tblacc_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_account_history`
--
ALTER TABLE `tblacc_account_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_account_type_details`
--
ALTER TABLE `tblacc_account_type_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_banking_rules`
--
ALTER TABLE `tblacc_banking_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_banking_rule_details`
--
ALTER TABLE `tblacc_banking_rule_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_budgets`
--
ALTER TABLE `tblacc_budgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_budget_details`
--
ALTER TABLE `tblacc_budget_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_expense_category_mappings`
--
ALTER TABLE `tblacc_expense_category_mappings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_item_automatics`
--
ALTER TABLE `tblacc_item_automatics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_journal_entries`
--
ALTER TABLE `tblacc_journal_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_payment_mode_mappings`
--
ALTER TABLE `tblacc_payment_mode_mappings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_reconciles`
--
ALTER TABLE `tblacc_reconciles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_tax_mappings`
--
ALTER TABLE `tblacc_tax_mappings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_transaction_bankings`
--
ALTER TABLE `tblacc_transaction_bankings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblacc_transfers`
--
ALTER TABLE `tblacc_transfers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblactivity_log`
--
ALTER TABLE `tblactivity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staffid` (`staffid`);

--
-- Indexes for table `tblannouncements`
--
ALTER TABLE `tblannouncements`
  ADD PRIMARY KEY (`announcementid`);

--
-- Indexes for table `tblclients`
--
ALTER TABLE `tblclients`
  ADD PRIMARY KEY (`userid`),
  ADD KEY `country` (`country`),
  ADD KEY `leadid` (`leadid`),
  ADD KEY `company` (`company`),
  ADD KEY `active` (`active`);

--
-- Indexes for table `tblconsents`
--
ALTER TABLE `tblconsents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purpose_id` (`purpose_id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- Indexes for table `tblconsent_purposes`
--
ALTER TABLE `tblconsent_purposes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcontacts`
--
ALTER TABLE `tblcontacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`),
  ADD KEY `firstname` (`firstname`),
  ADD KEY `lastname` (`lastname`),
  ADD KEY `email` (`email`),
  ADD KEY `is_primary` (`is_primary`);

--
-- Indexes for table `tblcontact_permissions`
--
ALTER TABLE `tblcontact_permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcontracts`
--
ALTER TABLE `tblcontracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client` (`client`),
  ADD KEY `contract_type` (`contract_type`);

--
-- Indexes for table `tblcontracts_types`
--
ALTER TABLE `tblcontracts_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcontract_comments`
--
ALTER TABLE `tblcontract_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcontract_renewals`
--
ALTER TABLE `tblcontract_renewals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcountries`
--
ALTER TABLE `tblcountries`
  ADD PRIMARY KEY (`country_id`);

--
-- Indexes for table `tblcreditnotes`
--
ALTER TABLE `tblcreditnotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `currency` (`currency`),
  ADD KEY `clientid` (`clientid`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `formatted_number` (`formatted_number`);

--
-- Indexes for table `tblcreditnote_refunds`
--
ALTER TABLE `tblcreditnote_refunds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcredits`
--
ALTER TABLE `tblcredits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcurrencies`
--
ALTER TABLE `tblcurrencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcustomers_groups`
--
ALTER TABLE `tblcustomers_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tblcustomer_admins`
--
ALTER TABLE `tblcustomer_admins`
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `tblcustomer_groups`
--
ALTER TABLE `tblcustomer_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `groupid` (`groupid`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `tblcustomfields`
--
ALTER TABLE `tblcustomfields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcustomfieldsvalues`
--
ALTER TABLE `tblcustomfieldsvalues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `relid` (`relid`),
  ADD KEY `fieldto` (`fieldto`),
  ADD KEY `fieldid` (`fieldid`);

--
-- Indexes for table `tblcustom_templates`
--
ALTER TABLE `tblcustom_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbldepartments`
--
ALTER TABLE `tbldepartments`
  ADD PRIMARY KEY (`departmentid`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tbldismissed_announcements`
--
ALTER TABLE `tbldismissed_announcements`
  ADD PRIMARY KEY (`dismissedannouncementid`),
  ADD KEY `announcementid` (`announcementid`),
  ADD KEY `staff` (`staff`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `tblemailtemplates`
--
ALTER TABLE `tblemailtemplates`
  ADD PRIMARY KEY (`emailtemplateid`);

--
-- Indexes for table `tblestimates`
--
ALTER TABLE `tblestimates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clientid` (`clientid`),
  ADD KEY `currency` (`currency`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `sale_agent` (`sale_agent`),
  ADD KEY `status` (`status`),
  ADD KEY `formatted_number` (`formatted_number`);

--
-- Indexes for table `tblestimate_requests`
--
ALTER TABLE `tblestimate_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblestimate_request_forms`
--
ALTER TABLE `tblestimate_request_forms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblestimate_request_status`
--
ALTER TABLE `tblestimate_request_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblevents`
--
ALTER TABLE `tblevents`
  ADD PRIMARY KEY (`eventid`);

--
-- Indexes for table `tblexpenses`
--
ALTER TABLE `tblexpenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clientid` (`clientid`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `category` (`category`),
  ADD KEY `currency` (`currency`);

--
-- Indexes for table `tblexpenses_categories`
--
ALTER TABLE `tblexpenses_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblfiles`
--
ALTER TABLE `tblfiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rel_id` (`rel_id`),
  ADD KEY `rel_type` (`rel_type`);

--
-- Indexes for table `tblfilters`
--
ALTER TABLE `tblfilters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblfilter_defaults`
--
ALTER TABLE `tblfilter_defaults`
  ADD KEY `filter_id` (`filter_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `tblform_questions`
--
ALTER TABLE `tblform_questions`
  ADD PRIMARY KEY (`questionid`);

--
-- Indexes for table `tblform_question_box`
--
ALTER TABLE `tblform_question_box`
  ADD PRIMARY KEY (`boxid`);

--
-- Indexes for table `tblform_question_box_description`
--
ALTER TABLE `tblform_question_box_description`
  ADD PRIMARY KEY (`questionboxdescriptionid`);

--
-- Indexes for table `tblform_results`
--
ALTER TABLE `tblform_results`
  ADD PRIMARY KEY (`resultid`);

--
-- Indexes for table `tblgdpr_requests`
--
ALTER TABLE `tblgdpr_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblgoods_delivery`
--
ALTER TABLE `tblgoods_delivery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblgoods_delivery_detail`
--
ALTER TABLE `tblgoods_delivery_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblgoods_receipt`
--
ALTER TABLE `tblgoods_receipt`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblgoods_receipt_detail`
--
ALTER TABLE `tblgoods_receipt_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblgoods_transaction_detail`
--
ALTER TABLE `tblgoods_transaction_detail`
  ADD PRIMARY KEY (`id`,`commodity_id`,`warehouse_id`);

--
-- Indexes for table `tblinventory_commodity_min`
--
ALTER TABLE `tblinventory_commodity_min`
  ADD PRIMARY KEY (`id`,`commodity_id`);

--
-- Indexes for table `tblinventory_manage`
--
ALTER TABLE `tblinventory_manage`
  ADD PRIMARY KEY (`id`,`commodity_id`,`warehouse_id`);

--
-- Indexes for table `tblinvoicepaymentrecords`
--
ALTER TABLE `tblinvoicepaymentrecords`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoiceid` (`invoiceid`),
  ADD KEY `paymentmethod` (`paymentmethod`);

--
-- Indexes for table `tblinvoices`
--
ALTER TABLE `tblinvoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `currency` (`currency`),
  ADD KEY `clientid` (`clientid`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `sale_agent` (`sale_agent`),
  ADD KEY `total` (`total`),
  ADD KEY `status` (`status`),
  ADD KEY `formatted_number` (`formatted_number`);

--
-- Indexes for table `tblipms_approval_actions`
--
ALTER TABLE `tblipms_approval_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_approval_request_id` (`approval_request_id`);

--
-- Indexes for table `tblipms_approval_notifications`
--
ALTER TABLE `tblipms_approval_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_notification` (`approval_request_id`,`notification_type`);

--
-- Indexes for table `tblipms_approval_requests`
--
ALTER TABLE `tblipms_approval_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_ref` (`request_ref`),
  ADD KEY `idx_document` (`document_type`,`document_id`),
  ADD KEY `idx_current_status` (`current_approver_id`,`status`);

--
-- Indexes for table `tblipms_approval_thresholds`
--
ALTER TABLE `tblipms_approval_thresholds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_type` (`document_type`);

--
-- Indexes for table `tblitemable`
--
ALTER TABLE `tblitemable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rel_id` (`rel_id`),
  ADD KEY `rel_type` (`rel_type`),
  ADD KEY `qty` (`qty`),
  ADD KEY `rate` (`rate`);

--
-- Indexes for table `tblitems`
--
ALTER TABLE `tblitems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tax` (`tax`),
  ADD KEY `tax2` (`tax2`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `tblitems_groups`
--
ALTER TABLE `tblitems_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblitem_tax`
--
ALTER TABLE `tblitem_tax`
  ADD PRIMARY KEY (`id`),
  ADD KEY `itemid` (`itemid`),
  ADD KEY `rel_id` (`rel_id`);

--
-- Indexes for table `tblknowedge_base_article_feedback`
--
ALTER TABLE `tblknowedge_base_article_feedback`
  ADD PRIMARY KEY (`articleanswerid`);

--
-- Indexes for table `tblknowledge_base`
--
ALTER TABLE `tblknowledge_base`
  ADD PRIMARY KEY (`articleid`);

--
-- Indexes for table `tblknowledge_base_groups`
--
ALTER TABLE `tblknowledge_base_groups`
  ADD PRIMARY KEY (`groupid`);

--
-- Indexes for table `tblleads`
--
ALTER TABLE `tblleads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `company` (`company`),
  ADD KEY `email` (`email`),
  ADD KEY `assigned` (`assigned`),
  ADD KEY `status` (`status`),
  ADD KEY `source` (`source`),
  ADD KEY `lastcontact` (`lastcontact`),
  ADD KEY `dateadded` (`dateadded`),
  ADD KEY `leadorder` (`leadorder`),
  ADD KEY `from_form_id` (`from_form_id`);

--
-- Indexes for table `tblleads_email_integration`
--
ALTER TABLE `tblleads_email_integration`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblleads_sources`
--
ALTER TABLE `tblleads_sources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tblleads_status`
--
ALTER TABLE `tblleads_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tbllead_activity_log`
--
ALTER TABLE `tbllead_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbllead_integration_emails`
--
ALTER TABLE `tbllead_integration_emails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblmail_queue`
--
ALTER TABLE `tblmail_queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblmilestones`
--
ALTER TABLE `tblmilestones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblmodules`
--
ALTER TABLE `tblmodules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblnewsfeed_comment_likes`
--
ALTER TABLE `tblnewsfeed_comment_likes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblnewsfeed_posts`
--
ALTER TABLE `tblnewsfeed_posts`
  ADD PRIMARY KEY (`postid`);

--
-- Indexes for table `tblnewsfeed_post_comments`
--
ALTER TABLE `tblnewsfeed_post_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblnewsfeed_post_likes`
--
ALTER TABLE `tblnewsfeed_post_likes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblnotes`
--
ALTER TABLE `tblnotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rel_id` (`rel_id`),
  ADD KEY `rel_type` (`rel_type`);

--
-- Indexes for table `tblnotifications`
--
ALTER TABLE `tblnotifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbloptions`
--
ALTER TABLE `tbloptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tblpayment_attempts`
--
ALTER TABLE `tblpayment_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpayment_modes`
--
ALTER TABLE `tblpayment_modes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpinned_projects`
--
ALTER TABLE `tblpinned_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `tblprojectdiscussioncomments`
--
ALTER TABLE `tblprojectdiscussioncomments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblprojectdiscussions`
--
ALTER TABLE `tblprojectdiscussions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblprojects`
--
ALTER TABLE `tblprojects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clientid` (`clientid`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tblproject_activity`
--
ALTER TABLE `tblproject_activity`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblproject_files`
--
ALTER TABLE `tblproject_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblproject_members`
--
ALTER TABLE `tblproject_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `tblproject_notes`
--
ALTER TABLE `tblproject_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblproject_settings`
--
ALTER TABLE `tblproject_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `tblproposals`
--
ALTER TABLE `tblproposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tblproposal_comments`
--
ALTER TABLE `tblproposal_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpurchase_option`
--
ALTER TABLE `tblpurchase_option`
  ADD PRIMARY KEY (`option_id`);

--
-- Indexes for table `tblpur_approval_details`
--
ALTER TABLE `tblpur_approval_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_approval_setting`
--
ALTER TABLE `tblpur_approval_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_contacts`
--
ALTER TABLE `tblpur_contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_contracts`
--
ALTER TABLE `tblpur_contracts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_estimates`
--
ALTER TABLE `tblpur_estimates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_estimate_detail`
--
ALTER TABLE `tblpur_estimate_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_invoices`
--
ALTER TABLE `tblpur_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_invoice_payment`
--
ALTER TABLE `tblpur_invoice_payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_orders`
--
ALTER TABLE `tblpur_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_order_detail`
--
ALTER TABLE `tblpur_order_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_order_payment`
--
ALTER TABLE `tblpur_order_payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_request`
--
ALTER TABLE `tblpur_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_request_detail`
--
ALTER TABLE `tblpur_request_detail`
  ADD PRIMARY KEY (`prd_id`);

--
-- Indexes for table `tblpur_unit`
--
ALTER TABLE `tblpur_unit`
  ADD PRIMARY KEY (`unit_id`);

--
-- Indexes for table `tblpur_vendor`
--
ALTER TABLE `tblpur_vendor`
  ADD PRIMARY KEY (`userid`);

--
-- Indexes for table `tblpur_vendor_cate`
--
ALTER TABLE `tblpur_vendor_cate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblpur_vendor_items`
--
ALTER TABLE `tblpur_vendor_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblrelated_items`
--
ALTER TABLE `tblrelated_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblreminders`
--
ALTER TABLE `tblreminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rel_id` (`rel_id`),
  ADD KEY `rel_type` (`rel_type`),
  ADD KEY `staff` (`staff`);

--
-- Indexes for table `tblroles`
--
ALTER TABLE `tblroles`
  ADD PRIMARY KEY (`roleid`);

--
-- Indexes for table `tblsales_activity`
--
ALTER TABLE `tblsales_activity`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblscheduled_emails`
--
ALTER TABLE `tblscheduled_emails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblservices`
--
ALTER TABLE `tblservices`
  ADD PRIMARY KEY (`serviceid`);

--
-- Indexes for table `tblsessions`
--
ALTER TABLE `tblsessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ci_sessions_timestamp` (`timestamp`);

--
-- Indexes for table `tblsi_export_customer_kyc_files`
--
ALTER TABLE `tblsi_export_customer_kyc_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `tblsi_export_customer_services`
--
ALTER TABLE `tblsi_export_customer_services`
  ADD PRIMARY KEY (`client_id`,`item_id`);

--
-- Indexes for table `tblspam_filters`
--
ALTER TABLE `tblspam_filters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblstaff`
--
ALTER TABLE `tblstaff`
  ADD PRIMARY KEY (`staffid`),
  ADD KEY `firstname` (`firstname`),
  ADD KEY `lastname` (`lastname`);

--
-- Indexes for table `tblstaff_departments`
--
ALTER TABLE `tblstaff_departments`
  ADD PRIMARY KEY (`staffdepartmentid`);

--
-- Indexes for table `tblstock_take`
--
ALTER TABLE `tblstock_take`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblstock_take_detail`
--
ALTER TABLE `tblstock_take_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblsubscriptions`
--
ALTER TABLE `tblsubscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clientid` (`clientid`),
  ADD KEY `currency` (`currency`),
  ADD KEY `tax_id` (`tax_id`);

--
-- Indexes for table `tbltaggables`
--
ALTER TABLE `tbltaggables`
  ADD KEY `rel_id` (`rel_id`),
  ADD KEY `rel_type` (`rel_type`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `tbltags`
--
ALTER TABLE `tbltags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `tbltasks`
--
ALTER TABLE `tbltasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rel_id` (`rel_id`),
  ADD KEY `rel_type` (`rel_type`),
  ADD KEY `milestone` (`milestone`),
  ADD KEY `kanban_order` (`kanban_order`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbltaskstimers`
--
ALTER TABLE `tbltaskstimers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `tbltasks_checklist_templates`
--
ALTER TABLE `tbltasks_checklist_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltask_assigned`
--
ALTER TABLE `tbltask_assigned`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taskid` (`taskid`),
  ADD KEY `staffid` (`staffid`);

--
-- Indexes for table `tbltask_checklist_items`
--
ALTER TABLE `tbltask_checklist_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taskid` (`taskid`);

--
-- Indexes for table `tbltask_comments`
--
ALTER TABLE `tbltask_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `taskid` (`taskid`);

--
-- Indexes for table `tbltask_followers`
--
ALTER TABLE `tbltask_followers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltaxes`
--
ALTER TABLE `tbltaxes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltemplates`
--
ALTER TABLE `tbltemplates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltickets`
--
ALTER TABLE `tbltickets`
  ADD PRIMARY KEY (`ticketid`),
  ADD KEY `service` (`service`),
  ADD KEY `department` (`department`),
  ADD KEY `status` (`status`),
  ADD KEY `userid` (`userid`),
  ADD KEY `priority` (`priority`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `contactid` (`contactid`);

--
-- Indexes for table `tbltickets_pipe_log`
--
ALTER TABLE `tbltickets_pipe_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltickets_predefined_replies`
--
ALTER TABLE `tbltickets_predefined_replies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltickets_priorities`
--
ALTER TABLE `tbltickets_priorities`
  ADD PRIMARY KEY (`priorityid`);

--
-- Indexes for table `tbltickets_status`
--
ALTER TABLE `tbltickets_status`
  ADD PRIMARY KEY (`ticketstatusid`);

--
-- Indexes for table `tblticket_attachments`
--
ALTER TABLE `tblticket_attachments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblticket_replies`
--
ALTER TABLE `tblticket_replies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltodos`
--
ALTER TABLE `tbltodos`
  ADD PRIMARY KEY (`todoid`);

--
-- Indexes for table `tbltracked_mails`
--
ALTER TABLE `tbltracked_mails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltwocheckout_log`
--
ALTER TABLE `tbltwocheckout_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `tbluser_api`
--
ALTER TABLE `tbluser_api`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbluser_meta`
--
ALTER TABLE `tbluser_meta`
  ADD PRIMARY KEY (`umeta_id`);

--
-- Indexes for table `tblvault`
--
ALTER TABLE `tblvault`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblviews_tracking`
--
ALTER TABLE `tblviews_tracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblwarehouse`
--
ALTER TABLE `tblwarehouse`
  ADD PRIMARY KEY (`warehouse_id`);

--
-- Indexes for table `tblware_body_type`
--
ALTER TABLE `tblware_body_type`
  ADD PRIMARY KEY (`body_type_id`);

--
-- Indexes for table `tblware_color`
--
ALTER TABLE `tblware_color`
  ADD PRIMARY KEY (`color_id`);

--
-- Indexes for table `tblware_commodity_type`
--
ALTER TABLE `tblware_commodity_type`
  ADD PRIMARY KEY (`commodity_type_id`);

--
-- Indexes for table `tblware_size_type`
--
ALTER TABLE `tblware_size_type`
  ADD PRIMARY KEY (`size_type_id`);

--
-- Indexes for table `tblware_style_type`
--
ALTER TABLE `tblware_style_type`
  ADD PRIMARY KEY (`style_type_id`);

--
-- Indexes for table `tblware_unit_type`
--
ALTER TABLE `tblware_unit_type`
  ADD PRIMARY KEY (`unit_type_id`);

--
-- Indexes for table `tblweb_to_lead`
--
ALTER TABLE `tblweb_to_lead`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblwh_activity_log`
--
ALTER TABLE `tblwh_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblwh_approval_details`
--
ALTER TABLE `tblwh_approval_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblwh_approval_setting`
--
ALTER TABLE `tblwh_approval_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblwh_loss_adjustment`
--
ALTER TABLE `tblwh_loss_adjustment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblwh_loss_adjustment_detail`
--
ALTER TABLE `tblwh_loss_adjustment_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblwh_sub_group`
--
ALTER TABLE `tblwh_sub_group`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblaccount_planning`
--
ALTER TABLE `tblaccount_planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblaccount_planning_current_service`
--
ALTER TABLE `tblaccount_planning_current_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblaccount_planning_financial`
--
ALTER TABLE `tblaccount_planning_financial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblaccount_planning_items`
--
ALTER TABLE `tblaccount_planning_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblaccount_planning_marketing_activities`
--
ALTER TABLE `tblaccount_planning_marketing_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblaccount_planning_objective`
--
ALTER TABLE `tblaccount_planning_objective`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblaccount_planning_service_ability_offering`
--
ALTER TABLE `tblaccount_planning_service_ability_offering`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblaccount_planning_task`
--
ALTER TABLE `tblaccount_planning_task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblaccount_planning_team`
--
ALTER TABLE `tblaccount_planning_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_accounts`
--
ALTER TABLE `tblacc_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `tblacc_account_history`
--
ALTER TABLE `tblacc_account_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_account_type_details`
--
ALTER TABLE `tblacc_account_type_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `tblacc_banking_rules`
--
ALTER TABLE `tblacc_banking_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_banking_rule_details`
--
ALTER TABLE `tblacc_banking_rule_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_budgets`
--
ALTER TABLE `tblacc_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_budget_details`
--
ALTER TABLE `tblacc_budget_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_expense_category_mappings`
--
ALTER TABLE `tblacc_expense_category_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_item_automatics`
--
ALTER TABLE `tblacc_item_automatics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_journal_entries`
--
ALTER TABLE `tblacc_journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_payment_mode_mappings`
--
ALTER TABLE `tblacc_payment_mode_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_reconciles`
--
ALTER TABLE `tblacc_reconciles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_tax_mappings`
--
ALTER TABLE `tblacc_tax_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_transaction_bankings`
--
ALTER TABLE `tblacc_transaction_bankings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblacc_transfers`
--
ALTER TABLE `tblacc_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblactivity_log`
--
ALTER TABLE `tblactivity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `tblannouncements`
--
ALTER TABLE `tblannouncements`
  MODIFY `announcementid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblclients`
--
ALTER TABLE `tblclients`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblconsents`
--
ALTER TABLE `tblconsents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblconsent_purposes`
--
ALTER TABLE `tblconsent_purposes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcontacts`
--
ALTER TABLE `tblcontacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcontact_permissions`
--
ALTER TABLE `tblcontact_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcontracts`
--
ALTER TABLE `tblcontracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcontracts_types`
--
ALTER TABLE `tblcontracts_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcontract_comments`
--
ALTER TABLE `tblcontract_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcontract_renewals`
--
ALTER TABLE `tblcontract_renewals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcountries`
--
ALTER TABLE `tblcountries`
  MODIFY `country_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251;

--
-- AUTO_INCREMENT for table `tblcreditnotes`
--
ALTER TABLE `tblcreditnotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcreditnote_refunds`
--
ALTER TABLE `tblcreditnote_refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcredits`
--
ALTER TABLE `tblcredits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcurrencies`
--
ALTER TABLE `tblcurrencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblcustomers_groups`
--
ALTER TABLE `tblcustomers_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcustomer_groups`
--
ALTER TABLE `tblcustomer_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcustomfields`
--
ALTER TABLE `tblcustomfields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcustomfieldsvalues`
--
ALTER TABLE `tblcustomfieldsvalues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcustom_templates`
--
ALTER TABLE `tblcustom_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbldepartments`
--
ALTER TABLE `tbldepartments`
  MODIFY `departmentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbldismissed_announcements`
--
ALTER TABLE `tbldismissed_announcements`
  MODIFY `dismissedannouncementid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblemailtemplates`
--
ALTER TABLE `tblemailtemplates`
  MODIFY `emailtemplateid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `tblestimates`
--
ALTER TABLE `tblestimates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblestimate_requests`
--
ALTER TABLE `tblestimate_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblestimate_request_forms`
--
ALTER TABLE `tblestimate_request_forms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblestimate_request_status`
--
ALTER TABLE `tblestimate_request_status`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblevents`
--
ALTER TABLE `tblevents`
  MODIFY `eventid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblexpenses`
--
ALTER TABLE `tblexpenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblexpenses_categories`
--
ALTER TABLE `tblexpenses_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblfiles`
--
ALTER TABLE `tblfiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblfilters`
--
ALTER TABLE `tblfilters`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblform_questions`
--
ALTER TABLE `tblform_questions`
  MODIFY `questionid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblform_question_box`
--
ALTER TABLE `tblform_question_box`
  MODIFY `boxid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblform_question_box_description`
--
ALTER TABLE `tblform_question_box_description`
  MODIFY `questionboxdescriptionid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblform_results`
--
ALTER TABLE `tblform_results`
  MODIFY `resultid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblgdpr_requests`
--
ALTER TABLE `tblgdpr_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblgoods_delivery`
--
ALTER TABLE `tblgoods_delivery`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblgoods_delivery_detail`
--
ALTER TABLE `tblgoods_delivery_detail`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblgoods_receipt`
--
ALTER TABLE `tblgoods_receipt`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblgoods_receipt_detail`
--
ALTER TABLE `tblgoods_receipt_detail`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblgoods_transaction_detail`
--
ALTER TABLE `tblgoods_transaction_detail`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblinventory_commodity_min`
--
ALTER TABLE `tblinventory_commodity_min`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblinventory_manage`
--
ALTER TABLE `tblinventory_manage`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblinvoicepaymentrecords`
--
ALTER TABLE `tblinvoicepaymentrecords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblinvoices`
--
ALTER TABLE `tblinvoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblipms_approval_actions`
--
ALTER TABLE `tblipms_approval_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tblipms_approval_notifications`
--
ALTER TABLE `tblipms_approval_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tblipms_approval_requests`
--
ALTER TABLE `tblipms_approval_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblipms_approval_thresholds`
--
ALTER TABLE `tblipms_approval_thresholds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblitemable`
--
ALTER TABLE `tblitemable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tblitems`
--
ALTER TABLE `tblitems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblitems_groups`
--
ALTER TABLE `tblitems_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblitem_tax`
--
ALTER TABLE `tblitem_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblknowedge_base_article_feedback`
--
ALTER TABLE `tblknowedge_base_article_feedback`
  MODIFY `articleanswerid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblknowledge_base`
--
ALTER TABLE `tblknowledge_base`
  MODIFY `articleid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblknowledge_base_groups`
--
ALTER TABLE `tblknowledge_base_groups`
  MODIFY `groupid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblleads`
--
ALTER TABLE `tblleads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblleads_email_integration`
--
ALTER TABLE `tblleads_email_integration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'the ID always must be 1', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblleads_sources`
--
ALTER TABLE `tblleads_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblleads_status`
--
ALTER TABLE `tblleads_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbllead_activity_log`
--
ALTER TABLE `tbllead_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbllead_integration_emails`
--
ALTER TABLE `tbllead_integration_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblmail_queue`
--
ALTER TABLE `tblmail_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblmilestones`
--
ALTER TABLE `tblmilestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblmodules`
--
ALTER TABLE `tblmodules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tblnewsfeed_comment_likes`
--
ALTER TABLE `tblnewsfeed_comment_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblnewsfeed_posts`
--
ALTER TABLE `tblnewsfeed_posts`
  MODIFY `postid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblnewsfeed_post_comments`
--
ALTER TABLE `tblnewsfeed_post_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblnewsfeed_post_likes`
--
ALTER TABLE `tblnewsfeed_post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblnotes`
--
ALTER TABLE `tblnotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblnotifications`
--
ALTER TABLE `tblnotifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbloptions`
--
ALTER TABLE `tbloptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=564;

--
-- AUTO_INCREMENT for table `tblpayment_attempts`
--
ALTER TABLE `tblpayment_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpayment_modes`
--
ALTER TABLE `tblpayment_modes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblpinned_projects`
--
ALTER TABLE `tblpinned_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblprojectdiscussioncomments`
--
ALTER TABLE `tblprojectdiscussioncomments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblprojectdiscussions`
--
ALTER TABLE `tblprojectdiscussions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblprojects`
--
ALTER TABLE `tblprojects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblproject_activity`
--
ALTER TABLE `tblproject_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblproject_files`
--
ALTER TABLE `tblproject_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblproject_members`
--
ALTER TABLE `tblproject_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblproject_notes`
--
ALTER TABLE `tblproject_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblproject_settings`
--
ALTER TABLE `tblproject_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblproposals`
--
ALTER TABLE `tblproposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tblproposal_comments`
--
ALTER TABLE `tblproposal_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpurchase_option`
--
ALTER TABLE `tblpurchase_option`
  MODIFY `option_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tblpur_approval_details`
--
ALTER TABLE `tblpur_approval_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_approval_setting`
--
ALTER TABLE `tblpur_approval_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_contacts`
--
ALTER TABLE `tblpur_contacts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_contracts`
--
ALTER TABLE `tblpur_contracts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_estimates`
--
ALTER TABLE `tblpur_estimates`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_estimate_detail`
--
ALTER TABLE `tblpur_estimate_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_invoices`
--
ALTER TABLE `tblpur_invoices`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_invoice_payment`
--
ALTER TABLE `tblpur_invoice_payment`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_orders`
--
ALTER TABLE `tblpur_orders`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_order_detail`
--
ALTER TABLE `tblpur_order_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_order_payment`
--
ALTER TABLE `tblpur_order_payment`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_request`
--
ALTER TABLE `tblpur_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_request_detail`
--
ALTER TABLE `tblpur_request_detail`
  MODIFY `prd_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_unit`
--
ALTER TABLE `tblpur_unit`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_vendor`
--
ALTER TABLE `tblpur_vendor`
  MODIFY `userid` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_vendor_cate`
--
ALTER TABLE `tblpur_vendor_cate`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblpur_vendor_items`
--
ALTER TABLE `tblpur_vendor_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblrelated_items`
--
ALTER TABLE `tblrelated_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblreminders`
--
ALTER TABLE `tblreminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblroles`
--
ALTER TABLE `tblroles`
  MODIFY `roleid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tblsales_activity`
--
ALTER TABLE `tblsales_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblscheduled_emails`
--
ALTER TABLE `tblscheduled_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblservices`
--
ALTER TABLE `tblservices`
  MODIFY `serviceid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblsi_export_customer_kyc_files`
--
ALTER TABLE `tblsi_export_customer_kyc_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblspam_filters`
--
ALTER TABLE `tblspam_filters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblstaff`
--
ALTER TABLE `tblstaff`
  MODIFY `staffid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tblstaff_departments`
--
ALTER TABLE `tblstaff_departments`
  MODIFY `staffdepartmentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblstock_take`
--
ALTER TABLE `tblstock_take`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblstock_take_detail`
--
ALTER TABLE `tblstock_take_detail`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblsubscriptions`
--
ALTER TABLE `tblsubscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltags`
--
ALTER TABLE `tbltags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbltasks`
--
ALTER TABLE `tbltasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltaskstimers`
--
ALTER TABLE `tbltaskstimers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltasks_checklist_templates`
--
ALTER TABLE `tbltasks_checklist_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltask_assigned`
--
ALTER TABLE `tbltask_assigned`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltask_checklist_items`
--
ALTER TABLE `tbltask_checklist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltask_comments`
--
ALTER TABLE `tbltask_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltask_followers`
--
ALTER TABLE `tbltask_followers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltaxes`
--
ALTER TABLE `tbltaxes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbltemplates`
--
ALTER TABLE `tbltemplates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltickets`
--
ALTER TABLE `tbltickets`
  MODIFY `ticketid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltickets_pipe_log`
--
ALTER TABLE `tbltickets_pipe_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltickets_predefined_replies`
--
ALTER TABLE `tbltickets_predefined_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltickets_priorities`
--
ALTER TABLE `tbltickets_priorities`
  MODIFY `priorityid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbltickets_status`
--
ALTER TABLE `tbltickets_status`
  MODIFY `ticketstatusid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblticket_attachments`
--
ALTER TABLE `tblticket_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblticket_replies`
--
ALTER TABLE `tblticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltodos`
--
ALTER TABLE `tbltodos`
  MODIFY `todoid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltracked_mails`
--
ALTER TABLE `tbltracked_mails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltwocheckout_log`
--
ALTER TABLE `tbltwocheckout_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbluser_api`
--
ALTER TABLE `tbluser_api`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbluser_meta`
--
ALTER TABLE `tbluser_meta`
  MODIFY `umeta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblvault`
--
ALTER TABLE `tblvault`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblviews_tracking`
--
ALTER TABLE `tblviews_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwarehouse`
--
ALTER TABLE `tblwarehouse`
  MODIFY `warehouse_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblware_body_type`
--
ALTER TABLE `tblware_body_type`
  MODIFY `body_type_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblware_color`
--
ALTER TABLE `tblware_color`
  MODIFY `color_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblware_commodity_type`
--
ALTER TABLE `tblware_commodity_type`
  MODIFY `commodity_type_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblware_size_type`
--
ALTER TABLE `tblware_size_type`
  MODIFY `size_type_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblware_style_type`
--
ALTER TABLE `tblware_style_type`
  MODIFY `style_type_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblware_unit_type`
--
ALTER TABLE `tblware_unit_type`
  MODIFY `unit_type_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblweb_to_lead`
--
ALTER TABLE `tblweb_to_lead`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwh_activity_log`
--
ALTER TABLE `tblwh_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwh_approval_details`
--
ALTER TABLE `tblwh_approval_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwh_approval_setting`
--
ALTER TABLE `tblwh_approval_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwh_loss_adjustment`
--
ALTER TABLE `tblwh_loss_adjustment`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwh_loss_adjustment_detail`
--
ALTER TABLE `tblwh_loss_adjustment_detail`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwh_sub_group`
--
ALTER TABLE `tblwh_sub_group`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblfilter_defaults`
--
ALTER TABLE `tblfilter_defaults`
  ADD CONSTRAINT `tblfilter_defaults_ibfk_1` FOREIGN KEY (`filter_id`) REFERENCES `tblfilters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tblfilter_defaults_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `tblstaff` (`staffid`) ON DELETE CASCADE;

--
-- Constraints for table `tbltwocheckout_log`
--
ALTER TABLE `tbltwocheckout_log`
  ADD CONSTRAINT `tbltwocheckout_log_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `tblinvoices` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
