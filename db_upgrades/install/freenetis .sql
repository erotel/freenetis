-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Počítač: localhost
-- Vytvořeno: Pon 15. pro 2025, 10:56
-- Verze serveru: 11.8.3-MariaDB-0+deb13u1 from Debian
-- Verze PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `freenetis`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `account_attribute_id` int(11) NOT NULL,
  `balance` double DEFAULT 0 COMMENT 'Account balance, value is updated with respect to transfers of account',
  `comment` varchar(254) DEFAULT NULL,
  `comments_thread_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `accounts_bank_accounts`
--

CREATE TABLE `accounts_bank_accounts` (
  `account_id` int(11) NOT NULL,
  `bank_account_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `account_attributes`
--

CREATE TABLE `account_attributes` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(230) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL COMMENT 'aktivní, pasivní, daňový, nedaňový',
  `kind` tinyint(4) DEFAULT NULL COMMENT 'rozvahový, výsledovkový, závěrkový, podrozvahový',
  `activity` tinyint(4) DEFAULT NULL COMMENT 'hlavní/hospodářská činnost',
  `track_balance` tinyint(4) DEFAULT NULL COMMENT 'true, false',
  `line` tinyint(4) DEFAULT NULL COMMENT 'řádek plné výsledovky',
  `line_short` tinyint(4) DEFAULT NULL COMMENT 'řádek zkrácené výsledovky',
  `line_credits` tinyint(4) DEFAULT NULL COMMENT 'řádek pasiv',
  `line_credits_short` tinyint(4) DEFAULT NULL COMMENT 'řádek zkrácených pasiv ',
  `comment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `acl`
--

CREATE TABLE `acl` (
  `id` int(11) NOT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `aco`
--

CREATE TABLE `aco` (
  `id` int(11) NOT NULL DEFAULT 0,
  `value` varchar(240) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `aco_map`
--

CREATE TABLE `aco_map` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `value` varchar(230) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `address_points`
--

CREATE TABLE `address_points` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `country_id` smallint(6) NOT NULL,
  `town_id` int(11) NOT NULL,
  `street_id` int(11) DEFAULT NULL,
  `street_number` varchar(50) DEFAULT NULL,
  `gps` point DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `allowed_subnets`
--

CREATE TABLE `allowed_subnets` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `subnet_id` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `allowed_subnets_counts`
--

CREATE TABLE `allowed_subnets_counts` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `approval_templates`
--

CREATE TABLE `approval_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `comment` mediumtext NOT NULL,
  `state` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `approval_template_items`
--

CREATE TABLE `approval_template_items` (
  `id` int(11) NOT NULL,
  `approval_template_id` int(11) DEFAULT NULL,
  `approval_type_id` int(11) NOT NULL,
  `priority` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `approval_types`
--

CREATE TABLE `approval_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `comment` mediumtext NOT NULL,
  `type` int(11) NOT NULL,
  `majority_percent` int(11) NOT NULL,
  `aro_group_id` int(11) NOT NULL,
  `interval` datetime NOT NULL,
  `default_vote` tinyint(1) DEFAULT NULL,
  `min_suggest_amount` int(11) NOT NULL,
  `one_vote` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Only one vote (approve/reject) required to end voting.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `aro_groups`
--

CREATE TABLE `aro_groups` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `lft` int(11) NOT NULL DEFAULT 0,
  `rgt` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `aro_groups_map`
--

CREATE TABLE `aro_groups_map` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `axo`
--

CREATE TABLE `axo` (
  `id` int(11) NOT NULL DEFAULT 0,
  `section_value` varchar(240) NOT NULL DEFAULT '0',
  `value` varchar(240) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `axo_map`
--

CREATE TABLE `axo_map` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `section_value` varchar(230) NOT NULL DEFAULT '0',
  `value` varchar(230) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `axo_sections`
--

CREATE TABLE `axo_sections` (
  `id` int(11) NOT NULL DEFAULT 0,
  `value` varchar(230) NOT NULL,
  `name` varchar(230) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(254) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `account_nr` varchar(254) DEFAULT NULL,
  `bank_nr` varchar(20) DEFAULT NULL,
  `type` int(11) NOT NULL DEFAULT 0,
  `settings` text DEFAULT NULL COMMENT 'JSON',
  `IBAN` varchar(254) DEFAULT NULL,
  `SWIFT` varchar(254) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `bank_accounts_automatical_downloads`
--

CREATE TABLE `bank_accounts_automatical_downloads` (
  `id` int(11) NOT NULL,
  `bank_account_id` int(11) NOT NULL,
  `type` smallint(6) NOT NULL,
  `attribute` varchar(255) DEFAULT NULL,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `bank_statements`
--

CREATE TABLE `bank_statements` (
  `id` int(11) NOT NULL,
  `bank_account_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `statement_number` int(11) DEFAULT NULL,
  `type` varchar(40) DEFAULT NULL,
  `from` datetime DEFAULT NULL,
  `to` datetime DEFAULT NULL,
  `opening_balance` double DEFAULT NULL,
  `closing_balance` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `bank_transfers`
--

CREATE TABLE `bank_transfers` (
  `id` int(11) NOT NULL,
  `origin_id` int(11) DEFAULT NULL COMMENT 'id of the origin bank account in bank_accounts table',
  `destination_id` int(11) DEFAULT NULL COMMENT 'id of the destination bank account in bank_accounts table',
  `transfer_id` int(11) DEFAULT NULL,
  `bank_statement_id` int(11) DEFAULT NULL,
  `transaction_code` bigint(20) DEFAULT NULL,
  `number` int(11) DEFAULT NULL COMMENT 'Line number or number of the bank listing item',
  `variable_symbol` bigint(20) DEFAULT NULL,
  `constant_symbol` bigint(20) DEFAULT NULL,
  `specific_symbol` bigint(20) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `cash`
--

CREATE TABLE `cash` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='implements cash deposits and withdrawals ';

-- --------------------------------------------------------

--
-- Struktura tabulky `clouds`
--

CREATE TABLE `clouds` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `clouds_subnets`
--

CREATE TABLE `clouds_subnets` (
  `cloud_id` int(11) NOT NULL,
  `subnet_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Join table between subnets and clouds.';

-- --------------------------------------------------------

--
-- Struktura tabulky `clouds_users`
--

CREATE TABLE `clouds_users` (
  `cloud_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Join table between users and clouds.';

-- --------------------------------------------------------

--
-- Struktura tabulky `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `comments_thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `comments_threads`
--

CREATE TABLE `comments_threads` (
  `id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `config`
--

CREATE TABLE `config` (
  `name` varchar(100) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `connection_requests`
--

CREATE TABLE `connection_requests` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL COMMENT 'Owner of the connection',
  `added_user_id` int(11) DEFAULT NULL COMMENT 'Who made request',
  `decided_user_id` int(11) DEFAULT NULL COMMENT 'User who approve/reject this connection request.',
  `state` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `decided_at` datetime DEFAULT NULL,
  `ip_address` varchar(39) NOT NULL,
  `subnet_id` int(11) NOT NULL,
  `mac_address` varchar(17) NOT NULL,
  `device_id` int(11) DEFAULT NULL COMMENT 'ID of the device that was created from this connection request or null.',
  `device_type_id` int(11) DEFAULT NULL,
  `device_template_id` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL COMMENT 'Comment of user who made request',
  `comments_thread_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `contacts_countries`
--

CREATE TABLE `contacts_countries` (
  `contact_id` int(11) NOT NULL,
  `country_id` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Relation between phone number in contacts to countries.';

-- --------------------------------------------------------

--
-- Struktura tabulky `countries`
--

CREATE TABLE `countries` (
  `id` smallint(6) NOT NULL,
  `country_name` varchar(100) NOT NULL COMMENT 'Country name in english',
  `country_iso` char(3) NOT NULL COMMENT 'ISO 3166-1 alpha-3',
  `country_code` varchar(4) NOT NULL COMMENT 'Telefone prefix of country',
  `enabled` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `address_point_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` int(11) NOT NULL,
  `trade_name` varchar(50) DEFAULT NULL,
  `operating_system` tinyint(4) DEFAULT NULL,
  `PPPoE_logging_in` tinyint(4) DEFAULT NULL,
  `login` varchar(30) DEFAULT NULL,
  `password` varchar(30) DEFAULT NULL,
  `access_time` datetime DEFAULT NULL,
  `price` double DEFAULT NULL,
  `payment_rate` double DEFAULT NULL,
  `buy_date` date DEFAULT NULL,
  `comment` varchar(254) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `device_active_links`
--

CREATE TABLE `device_active_links` (
  `id` int(11) NOT NULL,
  `url_pattern` varchar(255) NOT NULL,
  `name` varchar(50) NOT NULL,
  `title` varchar(50) NOT NULL,
  `show_in_user_grid` tinyint(4) NOT NULL,
  `show_in_grid` tinyint(4) NOT NULL,
  `as_form` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `device_active_links_map`
--

CREATE TABLE `device_active_links_map` (
  `device_active_link_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `type` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `device_admins`
--

CREATE TABLE `device_admins` (
  `id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `device_engineers`
--

CREATE TABLE `device_engineers` (
  `id` int(10) NOT NULL,
  `device_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `device_templates`
--

CREATE TABLE `device_templates` (
  `id` int(11) NOT NULL,
  `enum_type_id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `values` text NOT NULL,
  `default` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `email_queues`
--

CREATE TABLE `email_queues` (
  `id` int(11) NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `state` tinyint(4) NOT NULL,
  `access_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `email_queue_attachments`
--

CREATE TABLE `email_queue_attachments` (
  `id` int(11) NOT NULL,
  `email_queue_id` int(11) NOT NULL,
  `path` varchar(1024) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `enum_types`
--

CREATE TABLE `enum_types` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `value` varchar(254) DEFAULT NULL,
  `read_only` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `enum_type_names`
--

CREATE TABLE `enum_type_names` (
  `id` int(11) NOT NULL,
  `type_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `readonly` tinyint(1) NOT NULL,
  `fee` double NOT NULL,
  `from` date NOT NULL,
  `to` date NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL COMMENT 'Optional name for fee, useable as tariff name in case of regular member fee',
  `special_type_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `filter_queries`
--

CREATE TABLE `filter_queries` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `values` text NOT NULL,
  `default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `groups_aro_map`
--

CREATE TABLE `groups_aro_map` (
  `group_id` int(11) NOT NULL DEFAULT 0,
  `aro_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ifaces`
--

CREATE TABLE `ifaces` (
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `link_id` int(11) DEFAULT NULL,
  `mac` varchar(20) DEFAULT NULL,
  `name` varchar(254) DEFAULT NULL,
  `number` int(11) DEFAULT NULL,
  `wireless_mode` int(11) DEFAULT NULL,
  `wireless_antenna` int(11) DEFAULT NULL,
  `port_mode` int(11) DEFAULT NULL,
  `comment` varchar(254) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ifaces_relationships`
--

CREATE TABLE `ifaces_relationships` (
  `id` int(11) NOT NULL,
  `parent_iface_id` int(11) NOT NULL,
  `iface_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ifaces_vlans`
--

CREATE TABLE `ifaces_vlans` (
  `id` int(11) NOT NULL,
  `iface_id` int(11) NOT NULL,
  `vlan_id` int(11) NOT NULL,
  `tagged` tinyint(1) DEFAULT NULL,
  `port_vlan` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `partner_company` varchar(100) DEFAULT NULL,
  `partner_name` varchar(100) DEFAULT NULL,
  `partner_street` varchar(30) DEFAULT NULL,
  `partner_street_number` varchar(50) DEFAULT NULL,
  `partner_town` varchar(50) DEFAULT NULL,
  `partner_zip_code` varchar(10) DEFAULT NULL,
  `partner_country` varchar(100) DEFAULT NULL,
  `organization_identifier` varchar(20) DEFAULT NULL,
  `vat_organization_identifier` varchar(30) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `invoice_nr` double NOT NULL,
  `invoice_type` tinyint(1) NOT NULL,
  `account_nr` varchar(254) DEFAULT NULL,
  `var_sym` double NOT NULL,
  `con_sym` double DEFAULT NULL,
  `date_inv` date NOT NULL,
  `date_due` date NOT NULL,
  `date_vat` date NOT NULL,
  `vat` tinyint(1) NOT NULL,
  `order_nr` double DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `note` varchar(240) DEFAULT NULL,
  `pdf_filename` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `quantity` double NOT NULL,
  `author_fee` double NOT NULL,
  `contractual_increase` double NOT NULL,
  `service` tinyint(1) NOT NULL,
  `price` double NOT NULL,
  `vat` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `invoice_templates`
--

CREATE TABLE `invoice_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `invoices` varchar(255) DEFAULT NULL,
  `sup_company` text DEFAULT NULL,
  `sup_name` text DEFAULT NULL,
  `sup_street` varchar(255) DEFAULT NULL,
  `sup_street_number` varchar(255) DEFAULT NULL,
  `sup_town` varchar(255) DEFAULT NULL,
  `sup_zip_code` varchar(255) DEFAULT NULL,
  `sup_country` varchar(255) DEFAULT NULL,
  `sup_organization_identifier` varchar(255) DEFAULT NULL,
  `sup_vat_organization_identifier` varchar(255) DEFAULT NULL,
  `sup_phone_number` varchar(255) DEFAULT NULL,
  `sup_email` varchar(255) DEFAULT NULL,
  `cus_company` text DEFAULT NULL,
  `cus_name` text DEFAULT NULL,
  `cus_street` varchar(255) DEFAULT NULL,
  `cus_street_number` varchar(255) DEFAULT NULL,
  `cus_town` varchar(255) DEFAULT NULL,
  `cus_zip_code` varchar(255) DEFAULT NULL,
  `cus_country` varchar(255) DEFAULT NULL,
  `cus_organization_identifier` varchar(255) DEFAULT NULL,
  `cus_phone_number` varchar(255) DEFAULT NULL,
  `cus_email` varchar(255) DEFAULT NULL,
  `org_id` varchar(255) DEFAULT NULL,
  `invoice_nr` varchar(255) DEFAULT NULL,
  `invoice_type` varchar(255) DEFAULT NULL,
  `invoice_type_issued` varchar(255) DEFAULT NULL,
  `account_nr` text DEFAULT NULL,
  `var_sym` varchar(255) DEFAULT NULL,
  `con_sym` varchar(255) DEFAULT NULL,
  `date_inv` varchar(255) DEFAULT NULL,
  `date_due` varchar(255) DEFAULT NULL,
  `date_vat` varchar(255) DEFAULT NULL,
  `vat` varchar(255) DEFAULT NULL,
  `order_nr` varchar(255) DEFAULT NULL,
  `price` text DEFAULT NULL,
  `price_vat` text DEFAULT NULL,
  `currency` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `items` varchar(255) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `item_code` varchar(255) DEFAULT NULL,
  `item_quantity` varchar(255) DEFAULT NULL,
  `item_price` varchar(255) DEFAULT NULL,
  `item_vat` varchar(255) DEFAULT NULL,
  `charset` varchar(100) DEFAULT NULL,
  `namespace` text DEFAULT NULL,
  `vat_variables` text DEFAULT NULL,
  `type` tinyint(1) NOT NULL,
  `begin_tag` varchar(100) DEFAULT NULL,
  `end_tag` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ip6_addresses`
--

CREATE TABLE `ip6_addresses` (
  `iface_id` int(11) DEFAULT NULL,
  `subnet_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `dhcp` tinyint(4) DEFAULT NULL,
  `gateway` tinyint(4) DEFAULT NULL,
  `service` tinyint(4) NOT NULL DEFAULT 0,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ip_addresses`
--

CREATE TABLE `ip_addresses` (
  `iface_id` int(11) DEFAULT NULL,
  `subnet_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `ip_address` varchar(15) DEFAULT NULL,
  `dhcp` tinyint(4) DEFAULT NULL,
  `gateway` tinyint(4) DEFAULT NULL,
  `service` tinyint(4) NOT NULL DEFAULT 0,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ip_addresses_traffics`
--

CREATE TABLE `ip_addresses_traffics` (
  `ip_address` varchar(15) NOT NULL,
  `upload` int(11) UNSIGNED NOT NULL,
  `download` int(11) UNSIGNED NOT NULL,
  `local_upload` int(11) NOT NULL,
  `local_download` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `job_report_id` int(11) DEFAULT NULL COMMENT 'belongs to job report',
  `user_id` int(11) NOT NULL,
  `previous_rejected_work_id` int(11) DEFAULT NULL,
  `added_by_id` int(11) DEFAULT NULL,
  `approval_template_id` int(11) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `suggest_amount` int(11) NOT NULL COMMENT 'suggest amount by user',
  `date` date NOT NULL,
  `create_date` datetime NOT NULL COMMENT 'date of creation of work in IS',
  `hours` float DEFAULT NULL,
  `km` int(11) NOT NULL,
  `state` tinyint(1) NOT NULL,
  `transfer_id` int(11) DEFAULT NULL,
  `comments_thread_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `job_reports`
--

CREATE TABLE `job_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `added_by_id` int(11) DEFAULT NULL,
  `transfer_id` int(11) DEFAULT NULL,
  `approval_template_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `type` char(7) DEFAULT NULL COMMENT 'If null then type is gouped report, else type is monthly report and should contains date string in form ''YYYY-mm''',
  `price_per_hour` double NOT NULL COMMENT 'Price per hour for each work in report.',
  `price_per_km` double NOT NULL COMMENT 'Price per kilometre for each work in report.',
  `concept` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Concept is displayed only to owner',
  `payment_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Specified payment, 0 is credit payment, 1 is cash payment, see model for more details'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `links`
--

CREATE TABLE `links` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `medium` int(11) NOT NULL,
  `bitrate` bigint(20) DEFAULT NULL,
  `duplex` tinyint(4) DEFAULT NULL,
  `wireless_ssid` varchar(255) DEFAULT NULL,
  `wireless_norm` int(11) DEFAULT NULL,
  `wireless_frequency` int(11) DEFAULT NULL,
  `wireless_channel` int(11) DEFAULT NULL,
  `wireless_channel_width` int(11) DEFAULT NULL,
  `wireless_polarization` int(11) DEFAULT NULL,
  `comment` varchar(254) DEFAULT NULL,
  `gps` linestring DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `local_subnets`
--

CREATE TABLE `local_subnets` (
  `id` int(11) NOT NULL,
  `network_address` varchar(15) DEFAULT NULL,
  `netmask` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` datetime NOT NULL,
  `IP_address` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Struktura tabulky `log_queues`
--

CREATE TABLE `log_queues` (
  `id` int(11) NOT NULL,
  `type` smallint(6) NOT NULL,
  `state` smallint(6) NOT NULL,
  `created_at` datetime NOT NULL,
  `closed_by_user_id` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `description` text NOT NULL,
  `exception_backtrace` text DEFAULT NULL,
  `comments_thread_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `mail_messages`
--

CREATE TABLE `mail_messages` (
  `id` int(11) NOT NULL,
  `from_id` int(11) NOT NULL,
  `to_id` int(11) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `time` datetime NOT NULL,
  `readed` tinyint(1) NOT NULL DEFAULT 0,
  `from_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `to_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'id of user who added member',
  `registration` tinyint(1) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address_point_id` int(11) NOT NULL,
  `type` tinyint(4) DEFAULT NULL,
  `external_type` tinyint(4) DEFAULT NULL,
  `organization_identifier` varchar(20) DEFAULT NULL,
  `vat_organization_identifier` varchar(30) DEFAULT NULL,
  `speed_class_id` int(11) DEFAULT NULL,
  `entrance_fee` double DEFAULT NULL,
  `debt_payment_rate` double DEFAULT NULL,
  `entrance_fee_left` double DEFAULT NULL,
  `entrance_fee_date` date DEFAULT NULL,
  `entrance_date` date DEFAULT NULL,
  `entrance_form_received` date DEFAULT NULL,
  `entrance_form_accepted` date DEFAULT NULL,
  `leaving_date` date NOT NULL,
  `applicant_registration_datetime` datetime DEFAULT NULL COMMENT 'Used only for members who were registered by registration form by them self.',
  `applicant_connected_from` date DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT 0,
  `voip_billing_limit` int(11) NOT NULL DEFAULT 0,
  `voip_billing_type` varchar(10) NOT NULL DEFAULT 'prepaid',
  `comment` varchar(250) DEFAULT NULL,
  `notification_by_redirection` tinyint(4) NOT NULL DEFAULT 1,
  `notification_by_email` tinyint(4) NOT NULL DEFAULT 1,
  `notification_by_sms` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `membership_interrupts`
--

CREATE TABLE `membership_interrupts` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `members_fee_id` int(11) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `end_after_interrupt_end` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `membership_transfers`
--

CREATE TABLE `membership_transfers` (
  `id` int(11) NOT NULL,
  `from_member_id` int(11) NOT NULL,
  `to_member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `members_domiciles`
--

CREATE TABLE `members_domiciles` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `address_point_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `members_fees`
--

CREATE TABLE `members_fees` (
  `id` int(11) NOT NULL,
  `fee_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `activation_date` date NOT NULL DEFAULT '0000-00-00',
  `deactivation_date` date DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 1,
  `comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci COMMENT='Junction table between fees and members, used primarily for different tariffs';

-- --------------------------------------------------------

--
-- Struktura tabulky `members_traffics_daily`
--

CREATE TABLE `members_traffics_daily` (
  `member_id` int(11) NOT NULL,
  `upload` bigint(20) UNSIGNED NOT NULL,
  `download` bigint(20) UNSIGNED NOT NULL,
  `local_upload` bigint(20) UNSIGNED NOT NULL,
  `local_download` bigint(20) UNSIGNED NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci
PARTITION BY RANGE (to_days(`date`))
(
PARTITION p_first VALUES LESS THAN (719528) ENGINE=InnoDB,
PARTITION p_2025_07_18 VALUES LESS THAN (739816) ENGINE=InnoDB,
PARTITION p_2025_07_19 VALUES LESS THAN (739817) ENGINE=InnoDB,
PARTITION p_2025_07_20 VALUES LESS THAN (739818) ENGINE=InnoDB,
PARTITION p_2025_07_21 VALUES LESS THAN (739819) ENGINE=InnoDB,
PARTITION p_2025_07_22 VALUES LESS THAN (739820) ENGINE=InnoDB,
PARTITION p_2025_07_23 VALUES LESS THAN (739821) ENGINE=InnoDB,
PARTITION p_2025_07_24 VALUES LESS THAN (739822) ENGINE=InnoDB,
PARTITION p_2025_07_25 VALUES LESS THAN (739823) ENGINE=InnoDB,
PARTITION p_2025_07_26 VALUES LESS THAN (739824) ENGINE=InnoDB,
PARTITION p_2025_07_27 VALUES LESS THAN (739825) ENGINE=InnoDB,
PARTITION p_2025_07_28 VALUES LESS THAN (739826) ENGINE=InnoDB,
PARTITION p_2025_07_29 VALUES LESS THAN (739827) ENGINE=InnoDB,
PARTITION p_2025_07_30 VALUES LESS THAN (739828) ENGINE=InnoDB,
PARTITION p_2025_07_31 VALUES LESS THAN (739829) ENGINE=InnoDB,
PARTITION p_2025_08_01 VALUES LESS THAN (739830) ENGINE=InnoDB,
PARTITION p_2025_08_02 VALUES LESS THAN (739831) ENGINE=InnoDB,
PARTITION p_2025_08_03 VALUES LESS THAN (739832) ENGINE=InnoDB,
PARTITION p_2025_08_04 VALUES LESS THAN (739833) ENGINE=InnoDB,
PARTITION p_2025_08_05 VALUES LESS THAN (739834) ENGINE=InnoDB,
PARTITION p_2025_08_06 VALUES LESS THAN (739835) ENGINE=InnoDB,
PARTITION p_2025_08_07 VALUES LESS THAN (739836) ENGINE=InnoDB,
PARTITION p_2025_08_08 VALUES LESS THAN (739837) ENGINE=InnoDB,
PARTITION p_2025_08_09 VALUES LESS THAN (739838) ENGINE=InnoDB,
PARTITION p_2025_08_10 VALUES LESS THAN (739839) ENGINE=InnoDB,
PARTITION p_2025_08_11 VALUES LESS THAN (739840) ENGINE=InnoDB,
PARTITION p_2025_08_12 VALUES LESS THAN (739841) ENGINE=InnoDB,
PARTITION p_2025_08_13 VALUES LESS THAN (739842) ENGINE=InnoDB,
PARTITION p_2025_08_14 VALUES LESS THAN (739843) ENGINE=InnoDB,
PARTITION p_2025_08_15 VALUES LESS THAN (739844) ENGINE=InnoDB,
PARTITION p_2025_08_16 VALUES LESS THAN (739845) ENGINE=InnoDB,
PARTITION p_2025_08_17 VALUES LESS THAN (739846) ENGINE=InnoDB,
PARTITION p_2025_08_18 VALUES LESS THAN (739847) ENGINE=InnoDB,
PARTITION p_2025_08_19 VALUES LESS THAN (739848) ENGINE=InnoDB,
PARTITION p_2025_08_20 VALUES LESS THAN (739849) ENGINE=InnoDB,
PARTITION p_2025_08_21 VALUES LESS THAN (739850) ENGINE=InnoDB,
PARTITION p_2025_08_22 VALUES LESS THAN (739851) ENGINE=InnoDB,
PARTITION p_2025_08_23 VALUES LESS THAN (739852) ENGINE=InnoDB,
PARTITION p_2025_08_24 VALUES LESS THAN (739853) ENGINE=InnoDB,
PARTITION p_2025_08_25 VALUES LESS THAN (739854) ENGINE=InnoDB,
PARTITION p_2025_08_26 VALUES LESS THAN (739855) ENGINE=InnoDB,
PARTITION p_2025_08_27 VALUES LESS THAN (739856) ENGINE=InnoDB,
PARTITION p_2025_08_28 VALUES LESS THAN (739857) ENGINE=InnoDB,
PARTITION p_2025_08_29 VALUES LESS THAN (739858) ENGINE=InnoDB,
PARTITION p_2025_08_30 VALUES LESS THAN (739859) ENGINE=InnoDB,
PARTITION p_2025_08_31 VALUES LESS THAN (739860) ENGINE=InnoDB,
PARTITION p_2025_09_01 VALUES LESS THAN (739861) ENGINE=InnoDB,
PARTITION p_2025_09_02 VALUES LESS THAN (739862) ENGINE=InnoDB,
PARTITION p_2025_09_03 VALUES LESS THAN (739863) ENGINE=InnoDB,
PARTITION p_2025_09_04 VALUES LESS THAN (739864) ENGINE=InnoDB,
PARTITION p_2025_09_05 VALUES LESS THAN (739865) ENGINE=InnoDB,
PARTITION p_2025_09_06 VALUES LESS THAN (739866) ENGINE=InnoDB,
PARTITION p_2025_09_07 VALUES LESS THAN (739867) ENGINE=InnoDB,
PARTITION p_2025_09_08 VALUES LESS THAN (739868) ENGINE=InnoDB,
PARTITION p_2025_09_09 VALUES LESS THAN (739869) ENGINE=InnoDB,
PARTITION p_2025_09_10 VALUES LESS THAN (739870) ENGINE=InnoDB,
PARTITION p_2025_09_11 VALUES LESS THAN (739871) ENGINE=InnoDB,
PARTITION p_2025_09_12 VALUES LESS THAN (739872) ENGINE=InnoDB,
PARTITION p_2025_09_13 VALUES LESS THAN (739873) ENGINE=InnoDB,
PARTITION p_2025_09_14 VALUES LESS THAN (739874) ENGINE=InnoDB,
PARTITION p_2025_09_15 VALUES LESS THAN (739875) ENGINE=InnoDB,
PARTITION p_2025_09_16 VALUES LESS THAN (739876) ENGINE=InnoDB,
PARTITION p_2025_09_17 VALUES LESS THAN (739877) ENGINE=InnoDB,
PARTITION p_2025_09_18 VALUES LESS THAN (739878) ENGINE=InnoDB
);

-- --------------------------------------------------------

--
-- Struktura tabulky `members_traffics_monthly`
--

CREATE TABLE `members_traffics_monthly` (
  `member_id` int(11) NOT NULL,
  `upload` bigint(20) UNSIGNED NOT NULL,
  `download` bigint(20) UNSIGNED NOT NULL,
  `local_upload` bigint(20) UNSIGNED NOT NULL,
  `local_download` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci
PARTITION BY RANGE (to_days(`date`))
(
PARTITION p_first VALUES LESS THAN (719528) ENGINE=InnoDB,
PARTITION p_2023_09_01 VALUES LESS THAN (739159) ENGINE=InnoDB,
PARTITION p_2023_10_01 VALUES LESS THAN (739190) ENGINE=InnoDB,
PARTITION p_2023_11_01 VALUES LESS THAN (739220) ENGINE=InnoDB,
PARTITION p_2023_12_01 VALUES LESS THAN (739251) ENGINE=InnoDB,
PARTITION p_2024_01_01 VALUES LESS THAN (739282) ENGINE=InnoDB,
PARTITION p_2024_02_01 VALUES LESS THAN (739311) ENGINE=InnoDB,
PARTITION p_2024_03_01 VALUES LESS THAN (739342) ENGINE=InnoDB,
PARTITION p_2024_04_01 VALUES LESS THAN (739372) ENGINE=InnoDB,
PARTITION p_2024_05_01 VALUES LESS THAN (739403) ENGINE=InnoDB,
PARTITION p_2024_06_01 VALUES LESS THAN (739433) ENGINE=InnoDB,
PARTITION p_2024_07_01 VALUES LESS THAN (739464) ENGINE=InnoDB,
PARTITION p_2024_08_01 VALUES LESS THAN (739495) ENGINE=InnoDB,
PARTITION p_2024_09_01 VALUES LESS THAN (739525) ENGINE=InnoDB,
PARTITION p_2024_10_01 VALUES LESS THAN (739556) ENGINE=InnoDB,
PARTITION p_2024_11_01 VALUES LESS THAN (739586) ENGINE=InnoDB,
PARTITION p_2024_12_01 VALUES LESS THAN (739617) ENGINE=InnoDB,
PARTITION p_2025_01_01 VALUES LESS THAN (739648) ENGINE=InnoDB,
PARTITION p_2025_02_01 VALUES LESS THAN (739676) ENGINE=InnoDB,
PARTITION p_2025_03_01 VALUES LESS THAN (739707) ENGINE=InnoDB,
PARTITION p_2025_04_01 VALUES LESS THAN (739737) ENGINE=InnoDB,
PARTITION p_2025_05_01 VALUES LESS THAN (739768) ENGINE=InnoDB,
PARTITION p_2025_06_01 VALUES LESS THAN (739798) ENGINE=InnoDB,
PARTITION p_2025_07_01 VALUES LESS THAN (739829) ENGINE=InnoDB,
PARTITION p_2025_08_01 VALUES LESS THAN (739860) ENGINE=InnoDB,
PARTITION p_2025_09_01 VALUES LESS THAN (739890) ENGINE=InnoDB
);

-- --------------------------------------------------------

--
-- Struktura tabulky `members_traffics_yearly`
--

CREATE TABLE `members_traffics_yearly` (
  `member_id` int(11) NOT NULL,
  `upload` bigint(20) UNSIGNED NOT NULL,
  `download` bigint(20) UNSIGNED NOT NULL,
  `local_upload` bigint(20) UNSIGNED NOT NULL,
  `local_download` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `members_whitelists`
--

CREATE TABLE `members_whitelists` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `permanent` tinyint(1) NOT NULL,
  `since` date NOT NULL,
  `until` date NOT NULL,
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='Redirection member white list.';

-- --------------------------------------------------------

--
-- Struktura tabulky `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Name of the redirection message',
  `text` text DEFAULT NULL COMMENT 'Content of message, can contain HTML code.',
  `email_text` text DEFAULT NULL,
  `sms_text` varchar(760) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL COMMENT 'Types of messages are saved in Mesage_Model.',
  `self_cancel` tinyint(4) DEFAULT NULL COMMENT 'Possibility of self-canceling of redirection message.',
  `ignore_whitelist` tinyint(4) DEFAULT 0 COMMENT 'If set, than IP address in whitelist are also redirected.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='messages used for redirection';

-- --------------------------------------------------------

--
-- Struktura tabulky `messages_automatical_activations`
--

CREATE TABLE `messages_automatical_activations` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `type` smallint(6) NOT NULL,
  `attribute` varchar(255) DEFAULT NULL,
  `redirection_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `send_activation_to_email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `messages_ip_addresses`
--

CREATE TABLE `messages_ip_addresses` (
  `message_id` int(11) NOT NULL COMMENT 'redirection message',
  `ip_address_id` int(11) NOT NULL COMMENT 'redirected ip address',
  `user_id` int(11) DEFAULT NULL COMMENT 'user id of admin who redirects',
  `comment` text DEFAULT NULL COMMENT 'personal comment from admin for redirected user',
  `datetime` datetime NOT NULL COMMENT 'Date and time of setting redirection'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='junction table between redirected ip address and message of redirection';

-- --------------------------------------------------------

--
-- Struktura tabulky `monitor_hosts`
--

CREATE TABLE `monitor_hosts` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `state` tinyint(1) NOT NULL,
  `state_changed` tinyint(1) NOT NULL,
  `state_changed_date` datetime NOT NULL,
  `last_attempt_date` datetime NOT NULL,
  `last_notification_date` datetime DEFAULT NULL,
  `latency_current` float DEFAULT NULL,
  `latency_min` float DEFAULT NULL,
  `latency_max` float DEFAULT NULL,
  `latency_avg` float DEFAULT NULL,
  `polls_total` bigint(11) NOT NULL,
  `polls_failed` bigint(11) NOT NULL,
  `availability` float NOT NULL,
  `priority` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_calls`
--

CREATE TABLE `phone_calls` (
  `phone_invoice_user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `datetime` datetime NOT NULL COMMENT 'Call started at',
  `price` float NOT NULL,
  `length` time NOT NULL,
  `number` varchar(15) NOT NULL COMMENT 'Called',
  `period` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `private` tinyint(1) DEFAULT 1 COMMENT 'Was call private?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Table of call service of phone invoice';

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_connections`
--

CREATE TABLE `phone_connections` (
  `phone_invoice_user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `datetime` datetime NOT NULL COMMENT 'Connection started at',
  `price` float NOT NULL,
  `transfered` int(11) NOT NULL COMMENT 'In kB',
  `period` varchar(100) DEFAULT NULL,
  `apn` varchar(100) DEFAULT NULL,
  `private` tinyint(1) DEFAULT 1 COMMENT 'Was it private?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Table of data service(internet connection) of phone invoice';

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_fixed_calls`
--

CREATE TABLE `phone_fixed_calls` (
  `phone_invoice_user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `datetime` datetime NOT NULL COMMENT 'Call started at',
  `price` float NOT NULL,
  `length` time NOT NULL,
  `number` varchar(15) NOT NULL COMMENT 'Called',
  `period` varchar(100) DEFAULT NULL,
  `destiny` varchar(200) DEFAULT NULL,
  `private` tinyint(1) DEFAULT 1 COMMENT 'Was fised call private?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Table of fixed call service of phone invoice';

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_invoices`
--

CREATE TABLE `phone_invoices` (
  `id` int(11) NOT NULL,
  `date_of_issuance` date NOT NULL,
  `billing_period_from` date NOT NULL,
  `billing_period_to` date NOT NULL,
  `variable_symbol` bigint(11) NOT NULL,
  `specific_symbol` bigint(11) NOT NULL,
  `total_price` float NOT NULL COMMENT 'Price without taxes',
  `tax` float NOT NULL COMMENT 'DPH',
  `tax_rate` smallint(6) NOT NULL COMMENT 'DPH in percents',
  `locked` tinyint(1) DEFAULT 0 COMMENT 'Indicate if invoice is locked for editing from users (not admin)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Table of phone invoices';

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_invoice_users`
--

CREATE TABLE `phone_invoice_users` (
  `user_id` int(11) DEFAULT NULL COMMENT 'ID of user or NULL if user was not assigned yet',
  `phone_invoice_id` int(11) NOT NULL,
  `transfer_id` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `phone_number` varchar(15) NOT NULL COMMENT 'Phone nuber with prefix with leading plus',
  `locked` tinyint(1) UNSIGNED NOT NULL COMMENT 'User locked his invoice as filled in.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Table of user invoices for phone';

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_operators`
--

CREATE TABLE `phone_operators` (
  `id` int(11) NOT NULL,
  `country_id` smallint(6) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone_number_length` smallint(2) DEFAULT 6,
  `sms_enabled` tinyint(1) DEFAULT 0 COMMENT 'Allows SMS sendind for this operator?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_operator_prefixes`
--

CREATE TABLE `phone_operator_prefixes` (
  `id` int(11) NOT NULL,
  `phone_operator_id` int(11) NOT NULL,
  `prefix` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_pays`
--

CREATE TABLE `phone_pays` (
  `phone_invoice_user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `datetime` datetime NOT NULL COMMENT 'Payed at',
  `price` float NOT NULL,
  `number` varchar(15) NOT NULL COMMENT 'Pay to',
  `description` varchar(200) DEFAULT NULL,
  `private` tinyint(4) DEFAULT 1 COMMENT 'Was pay private?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Table of pays of phone invoice';

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_roaming_sms_messages`
--

CREATE TABLE `phone_roaming_sms_messages` (
  `phone_invoice_user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `datetime` datetime NOT NULL COMMENT 'Message send at',
  `price` float NOT NULL,
  `roaming_zone` varchar(200) DEFAULT NULL,
  `private` tinyint(4) DEFAULT 1 COMMENT 'Was message private?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Table of sms messages service of phone invoice';

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_sms_messages`
--

CREATE TABLE `phone_sms_messages` (
  `phone_invoice_user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `datetime` datetime NOT NULL COMMENT 'Message send at',
  `price` float NOT NULL,
  `number` varchar(15) NOT NULL COMMENT 'Send to',
  `period` varchar(100) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL,
  `private` tinyint(4) DEFAULT 1 COMMENT 'Was message private?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Table of sms messages service of phone invoice';

-- --------------------------------------------------------

--
-- Struktura tabulky `phone_vpn_calls`
--

CREATE TABLE `phone_vpn_calls` (
  `phone_invoice_user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `datetime` datetime NOT NULL COMMENT 'Call started at',
  `price` float NOT NULL,
  `length` time NOT NULL,
  `number` varchar(15) NOT NULL COMMENT 'Called to',
  `period` varchar(100) DEFAULT NULL,
  `group` varchar(200) DEFAULT NULL,
  `private` tinyint(1) DEFAULT 1 COMMENT 'Was call private?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Table of vpn call service of phone invoice';

-- --------------------------------------------------------

--
-- Struktura tabulky `private_users_contacts`
--

CREATE TABLE `private_users_contacts` (
  `id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL COMMENT 'User who has private contact',
  `contact_id` int(10) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Private address book of each user';

-- --------------------------------------------------------

--
-- Struktura tabulky `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `approval_template_id` int(11) DEFAULT NULL,
  `type` smallint(6) NOT NULL DEFAULT 0,
  `description` mediumtext DEFAULT NULL,
  `suggest_amount` int(11) NOT NULL COMMENT 'suggest amount by user',
  `date` date NOT NULL,
  `state` tinyint(1) NOT NULL,
  `comments_thread_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `sms_messages`
--

CREATE TABLE `sms_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sms_message_id` int(11) DEFAULT NULL,
  `stamp` datetime NOT NULL,
  `send_date` datetime NOT NULL,
  `text` varchar(800) NOT NULL,
  `sender` varchar(14) NOT NULL,
  `receiver` varchar(14) NOT NULL,
  `driver` tinyint(4) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `message` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `speed_classes`
--

CREATE TABLE `speed_classes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `d_ceil` bigint(11) NOT NULL COMMENT 'QoS download ceil in bytes',
  `d_rate` bigint(11) NOT NULL COMMENT 'QoS download rate in bytes',
  `u_ceil` bigint(11) NOT NULL COMMENT 'QoS upload ceil in bytes',
  `u_rate` bigint(11) NOT NULL COMMENT 'QoS upload rate in bytes',
  `regular_member_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Is this class default for regular members?',
  `applicant_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Is this class default for applicants?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='Defines speed classes for QoS';

-- --------------------------------------------------------

--
-- Struktura tabulky `streets`
--

CREATE TABLE `streets` (
  `id` int(11) NOT NULL,
  `town_id` int(11) DEFAULT NULL,
  `street` varchar(30) NOT NULL,
  `gps` polygon DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `subnets`
--

CREATE TABLE `subnets` (
  `id` int(11) NOT NULL,
  `OSPF_area_id` int(11) DEFAULT NULL,
  `name` varchar(254) DEFAULT NULL,
  `network_address` varchar(15) DEFAULT NULL,
  `netmask` varchar(15) DEFAULT NULL,
  `redirect` tinyint(4) DEFAULT NULL COMMENT 'Bit mask for types of redirect',
  `dhcp` tinyint(1) NOT NULL DEFAULT 0,
  `dhcp_expired` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If DHCP is enabled on this subnet, this value indicates if any of its record was updated and not synchronized to DHCP server.',
  `dns` tinyint(1) NOT NULL DEFAULT 0,
  `qos` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `subnets_owners`
--

CREATE TABLE `subnets_owners` (
  `id` int(11) NOT NULL,
  `subnet_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `redirect` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `towns`
--

CREATE TABLE `towns` (
  `id` int(11) NOT NULL,
  `zip_code` varchar(10) NOT NULL,
  `town` varchar(50) NOT NULL,
  `quarter` varchar(50) DEFAULT NULL,
  `gps` polygon DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `transfers`
--

CREATE TABLE `transfers` (
  `id` int(11) NOT NULL,
  `origin_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `previous_transfer_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL COMMENT 'id of the member, to whom the transaction is assigned',
  `user_id` int(11) DEFAULT NULL COMMENT 'id of user, who added transfer',
  `type` tinyint(4) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  `creation_datetime` datetime NOT NULL,
  `text` varchar(254) DEFAULT NULL,
  `amount` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `translations`
--

CREATE TABLE `translations` (
  `id` int(11) NOT NULL,
  `original_term` varchar(254) NOT NULL,
  `translated_term` varchar(254) NOT NULL,
  `lang` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ulog2_ct`
--

CREATE TABLE `ulog2_ct` (
  `_ct_id` tinyint(4) DEFAULT NULL,
  `orig_ip_saddr` int(10) UNSIGNED DEFAULT NULL,
  `orig_ip_daddr` int(10) UNSIGNED DEFAULT NULL,
  `orig_ip_protocol` tinyint(3) UNSIGNED DEFAULT NULL,
  `orig_l4_sport` smallint(5) UNSIGNED DEFAULT NULL,
  `orig_l4_dport` smallint(5) UNSIGNED DEFAULT NULL,
  `orig_raw_pktlen` bigint(20) DEFAULT 0,
  `orig_raw_pktcount` int(10) UNSIGNED DEFAULT 0,
  `reply_ip_daddr` int(10) UNSIGNED DEFAULT NULL,
  `reply_l4_dport` smallint(5) UNSIGNED DEFAULT NULL,
  `reply_raw_pktlen` bigint(20) DEFAULT 0,
  `reply_raw_pktcount` int(10) UNSIGNED DEFAULT 0,
  `icmp_code` tinyint(3) DEFAULT NULL,
  `icmp_type` tinyint(3) DEFAULT NULL,
  `flow_start_sec` int(10) DEFAULT 0,
  `flow_end_sec` int(10) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE `users` (
  `member_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `login` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `password_request` varchar(10) DEFAULT NULL,
  `name` varchar(30) DEFAULT NULL,
  `middle_name` varchar(30) DEFAULT NULL,
  `surname` varchar(60) DEFAULT NULL,
  `pre_title` varchar(40) DEFAULT NULL,
  `post_title` varchar(30) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `type` tinyint(4) NOT NULL,
  `comment` varchar(250) DEFAULT NULL,
  `application_password` varchar(50) NOT NULL,
  `settings` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `users_contacts`
--

CREATE TABLE `users_contacts` (
  `user_id` int(10) NOT NULL,
  `contact_id` int(10) NOT NULL,
  `mail_redirection` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'In condition that this contact is an e-mail, this indicator specifies whether the inner system mail is redirected to this user''s e-mail box.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Pivot table';

-- --------------------------------------------------------

--
-- Struktura tabulky `users_keys`
--

CREATE TABLE `users_keys` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `key` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `user_favourite_pages`
--

CREATE TABLE `user_favourite_pages` (
  `id` int(11) NOT NULL,
  `user_id` int(30) NOT NULL,
  `title` varchar(50) NOT NULL,
  `page` varchar(255) NOT NULL,
  `default_page` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `variable_symbols`
--

CREATE TABLE `variable_symbols` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `variable_symbol` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `vlans`
--

CREATE TABLE `vlans` (
  `id` int(11) NOT NULL,
  `name` varchar(254) DEFAULT NULL,
  `tag_802_1q` int(11) DEFAULT NULL,
  `comment` varchar(254) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `voip_sips`
--

CREATE TABLE `voip_sips` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `accountcode` varchar(20) DEFAULT NULL,
  `amaflags` varchar(13) DEFAULT NULL,
  `callgroup` varchar(10) DEFAULT NULL,
  `callerid` varchar(80) NOT NULL,
  `canreinvite` char(3) DEFAULT 'no',
  `context` varchar(80) DEFAULT 'internal',
  `defaultip` varchar(15) DEFAULT NULL,
  `dtmfmode` varchar(7) DEFAULT NULL,
  `fromuser` varchar(80) DEFAULT NULL,
  `fromdomain` varchar(80) DEFAULT NULL,
  `fullcontact` varchar(80) DEFAULT NULL,
  `host` varchar(31) NOT NULL DEFAULT 'dynamic',
  `insecure` varchar(4) DEFAULT NULL,
  `language` char(2) DEFAULT 'cz',
  `mailbox` varchar(50) NOT NULL,
  `md5secret` varchar(80) DEFAULT NULL,
  `nat` varchar(5) NOT NULL DEFAULT 'yes',
  `deny` varchar(95) DEFAULT NULL,
  `permit` varchar(95) DEFAULT NULL,
  `mask` varchar(95) DEFAULT NULL,
  `pickupgroup` varchar(10) DEFAULT NULL,
  `port` varchar(5) DEFAULT NULL,
  `qualify` char(3) DEFAULT NULL,
  `restrictcid` char(1) DEFAULT NULL,
  `rtptimeout` char(3) DEFAULT NULL,
  `rtpholdtimeout` char(3) DEFAULT NULL,
  `secret` varchar(80) NOT NULL,
  `type` varchar(6) NOT NULL DEFAULT 'friend',
  `username` varchar(80) NOT NULL,
  `disallow` varchar(100) DEFAULT NULL,
  `allow` varchar(100) DEFAULT NULL,
  `musiconhold` varchar(100) DEFAULT NULL,
  `regseconds` int(11) NOT NULL DEFAULT 0,
  `ipaddr` varchar(15) NOT NULL,
  `regexten` varchar(80) NOT NULL,
  `cancallforward` char(3) DEFAULT 'yes',
  `setvar` varchar(100) NOT NULL,
  `auth` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Struktura tabulky `voip_voicemail_users`
--

CREATE TABLE `voip_voicemail_users` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `context` varchar(50) NOT NULL,
  `mailbox` varchar(10) NOT NULL DEFAULT '0',
  `password` varchar(4) NOT NULL DEFAULT '0',
  `fullname` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `pager` varchar(50) NOT NULL,
  `stamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'id of voter',
  `type` int(11) NOT NULL COMMENT 'type of vote',
  `fk_id` int(11) NOT NULL COMMENT 'id of foreign key',
  `aro_group_id` int(11) NOT NULL,
  `priority` int(11) NOT NULL,
  `vote` tinyint(1) DEFAULT NULL COMMENT 'value of user vote',
  `time` datetime NOT NULL,
  `comment` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `watchers`
--

CREATE TABLE `watchers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `fk_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

--
-- Indexy pro exportované tabulky
--

--
-- Indexy pro tabulku `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_owned_by` (`member_id`),
  ADD KEY `comments_thread_id` (`comments_thread_id`),
  ADD KEY `account_attribute_id` (`account_attribute_id`),
  ADD KEY `balance` (`balance`);

--
-- Indexy pro tabulku `accounts_bank_accounts`
--
ALTER TABLE `accounts_bank_accounts`
  ADD PRIMARY KEY (`account_id`,`bank_account_id`),
  ADD KEY `bank_account_id` (`bank_account_id`);

--
-- Indexy pro tabulku `account_attributes`
--
ALTER TABLE `account_attributes`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `acl`
--
ALTER TABLE `acl`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `aco`
--
ALTER TABLE `aco`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_value_value_aco` (`value`);

--
-- Indexy pro tabulku `aco_map`
--
ALTER TABLE `aco_map`
  ADD PRIMARY KEY (`acl_id`,`value`);

--
-- Indexy pro tabulku `address_points`
--
ALTER TABLE `address_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `street_id` (`street_id`),
  ADD KEY `town_id` (`town_id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexy pro tabulku `allowed_subnets`
--
ALTER TABLE `allowed_subnets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `subnet_id` (`subnet_id`);

--
-- Indexy pro tabulku `allowed_subnets_counts`
--
ALTER TABLE `allowed_subnets_counts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexy pro tabulku `approval_templates`
--
ALTER TABLE `approval_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `approval_template_items`
--
ALTER TABLE `approval_template_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approval_template_id` (`approval_template_id`),
  ADD KEY `approval_type_id` (`approval_type_id`);

--
-- Indexy pro tabulku `approval_types`
--
ALTER TABLE `approval_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `aro_groups`
--
ALTER TABLE `aro_groups`
  ADD PRIMARY KEY (`id`,`value`),
  ADD UNIQUE KEY `value_aro_groups` (`value`),
  ADD KEY `parent_id_aro_groups` (`parent_id`),
  ADD KEY `lft_rgt_aro_groups` (`lft`,`rgt`);

--
-- Indexy pro tabulku `aro_groups_map`
--
ALTER TABLE `aro_groups_map`
  ADD PRIMARY KEY (`acl_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexy pro tabulku `axo`
--
ALTER TABLE `axo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_value_value_axo` (`section_value`,`value`),
  ADD KEY `value` (`value`);

--
-- Indexy pro tabulku `axo_map`
--
ALTER TABLE `axo_map`
  ADD PRIMARY KEY (`acl_id`,`section_value`,`value`);

--
-- Indexy pro tabulku `axo_sections`
--
ALTER TABLE `axo_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `value_axo_sections` (`value`);

--
-- Indexy pro tabulku `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexy pro tabulku `bank_accounts_automatical_downloads`
--
ALTER TABLE `bank_accounts_automatical_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_account_id_fk` (`bank_account_id`);

--
-- Indexy pro tabulku `bank_statements`
--
ALTER TABLE `bank_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_account_id` (`bank_account_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `bank_transfers`
--
ALTER TABLE `bank_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `origin_id` (`origin_id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `transfer_id` (`transfer_id`),
  ADD KEY `number` (`number`,`variable_symbol`),
  ADD KEY `bank_statement_id` (`bank_statement_id`);

--
-- Indexy pro tabulku `cash`
--
ALTER TABLE `cash`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `transfer_id` (`transfer_id`);

--
-- Indexy pro tabulku `clouds`
--
ALTER TABLE `clouds`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `clouds_subnets`
--
ALTER TABLE `clouds_subnets`
  ADD PRIMARY KEY (`cloud_id`,`subnet_id`),
  ADD KEY `subnet_id` (`subnet_id`);

--
-- Indexy pro tabulku `clouds_users`
--
ALTER TABLE `clouds_users`
  ADD PRIMARY KEY (`cloud_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `comments_thread_id` (`comments_thread_id`);

--
-- Indexy pro tabulku `comments_threads`
--
ALTER TABLE `comments_threads`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`name`);

--
-- Indexy pro tabulku `connection_requests`
--
ALTER TABLE `connection_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id_fk` (`member_id`),
  ADD KEY `added_user_id_fk` (`added_user_id`),
  ADD KEY `decided_user_id_fk` (`decided_user_id`),
  ADD KEY `subnet_id_fk` (`subnet_id`),
  ADD KEY `device_id_fk` (`device_id`),
  ADD KEY `device_type_id_fk` (`device_type_id`),
  ADD KEY `device_template_id_fk` (`device_template_id`),
  ADD KEY `comments_thread_id_fk` (`comments_thread_id`);

--
-- Indexy pro tabulku `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `value` (`value`);

--
-- Indexy pro tabulku `contacts_countries`
--
ALTER TABLE `contacts_countries`
  ADD PRIMARY KEY (`contact_id`,`country_id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexy pro tabulku `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_point_id` (`address_point_id`);

--
-- Indexy pro tabulku `device_active_links`
--
ALTER TABLE `device_active_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `device_active_links_map`
--
ALTER TABLE `device_active_links_map`
  ADD KEY `device_active_link_id` (`device_active_link_id`,`device_id`);

--
-- Indexy pro tabulku `device_admins`
--
ALTER TABLE `device_admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexy pro tabulku `device_engineers`
--
ALTER TABLE `device_engineers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexy pro tabulku `device_templates`
--
ALTER TABLE `device_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_templates_category_id` (`enum_type_id`);

--
-- Indexy pro tabulku `email_queues`
--
ALTER TABLE `email_queues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from` (`from`,`to`);

--
-- Indexy pro tabulku `email_queue_attachments`
--
ALTER TABLE `email_queue_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_queue_id` (`email_queue_id`);

--
-- Indexy pro tabulku `enum_types`
--
ALTER TABLE `enum_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexy pro tabulku `enum_type_names`
--
ALTER TABLE `enum_type_names`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexy pro tabulku `filter_queries`
--
ALTER TABLE `filter_queries`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `groups_aro_map`
--
ALTER TABLE `groups_aro_map`
  ADD PRIMARY KEY (`group_id`,`aro_id`),
  ADD KEY `aro_id` (`aro_id`);

--
-- Indexy pro tabulku `ifaces`
--
ALTER TABLE `ifaces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_iface` (`device_id`),
  ADD KEY `segment_iface` (`link_id`),
  ADD KEY `type` (`type`);

--
-- Indexy pro tabulku `ifaces_relationships`
--
ALTER TABLE `ifaces_relationships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_iface_id` (`iface_id`),
  ADD KEY `iface_id` (`iface_id`),
  ADD KEY `parent_iface_id_2` (`parent_iface_id`);

--
-- Indexy pro tabulku `ifaces_vlans`
--
ALTER TABLE `ifaces_vlans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `iface_id` (`iface_id`),
  ADD KEY `vlan_id` (`vlan_id`);

--
-- Indexy pro tabulku `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexy pro tabulku `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexy pro tabulku `invoice_templates`
--
ALTER TABLE `invoice_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `ip6_addresses`
--
ALTER TABLE `ip6_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `ip_addresses_key_iface_id` (`iface_id`),
  ADD KEY `ip_addresses_key_subnet_id` (`subnet_id`),
  ADD KEY `ip_address` (`ip_address`);

--
-- Indexy pro tabulku `ip_addresses`
--
ALTER TABLE `ip_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `ip_addresses_key_iface_id` (`iface_id`),
  ADD KEY `ip_addresses_key_subnet_id` (`subnet_id`),
  ADD KEY `ip_address` (`ip_address`);

--
-- Indexy pro tabulku `ip_addresses_traffics`
--
ALTER TABLE `ip_addresses_traffics`
  ADD PRIMARY KEY (`ip_address`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexy pro tabulku `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commited_by` (`user_id`),
  ADD KEY `transfer_salary` (`transfer_id`),
  ADD KEY `comments_thread_id` (`comments_thread_id`),
  ADD KEY `job_report_id` (`job_report_id`),
  ADD KEY `added_by_id` (`added_by_id`),
  ADD KEY `approval_template_id` (`approval_template_id`),
  ADD KEY `previous_rejected_work_id` (`previous_rejected_work_id`);

--
-- Indexy pro tabulku `job_reports`
--
ALTER TABLE `job_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approval_template_id` (`approval_template_id`),
  ADD KEY `transfer_id` (`transfer_id`),
  ADD KEY `added_by_id` (`added_by_id`);

--
-- Indexy pro tabulku `links`
--
ALTER TABLE `links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Indexy pro tabulku `local_subnets`
--
ALTER TABLE `local_subnets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `network_address_2` (`network_address`),
  ADD KEY `network_address` (`network_address`),
  ADD KEY `netmask` (`netmask`);

--
-- Indexy pro tabulku `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time` (`time`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `log_queues`
--
ALTER TABLE `log_queues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `closed_by_user_id_fk` (`closed_by_user_id`),
  ADD KEY `log_queues_comments_thread_id_fk` (`comments_thread_id`);

--
-- Indexy pro tabulku `mail_messages`
--
ALTER TABLE `mail_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_id` (`from_id`),
  ADD KEY `to_id` (`to_id`);

--
-- Indexy pro tabulku `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `address_point_id` (`address_point_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `speed_class_id_fk` (`speed_class_id`);

--
-- Indexy pro tabulku `membership_interrupts`
--
ALTER TABLE `membership_interrupts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `members_fee_id` (`members_fee_id`);

--
-- Indexy pro tabulku `membership_transfers`
--
ALTER TABLE `membership_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_member_id` (`from_member_id`),
  ADD KEY `to_member_id` (`to_member_id`);

--
-- Indexy pro tabulku `members_domiciles`
--
ALTER TABLE `members_domiciles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`,`address_point_id`),
  ADD KEY `address_point_id` (`address_point_id`);

--
-- Indexy pro tabulku `members_fees`
--
ALTER TABLE `members_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `fee_id` (`fee_id`),
  ADD KEY `activation_date` (`activation_date`,`deactivation_date`);

--
-- Indexy pro tabulku `members_traffics_daily`
--
ALTER TABLE `members_traffics_daily`
  ADD PRIMARY KEY (`member_id`,`date`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `date` (`date`);

--
-- Indexy pro tabulku `members_traffics_monthly`
--
ALTER TABLE `members_traffics_monthly`
  ADD PRIMARY KEY (`member_id`,`date`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `date` (`date`);

--
-- Indexy pro tabulku `members_traffics_yearly`
--
ALTER TABLE `members_traffics_yearly`
  ADD PRIMARY KEY (`member_id`,`date`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `date` (`date`);

--
-- Indexy pro tabulku `members_whitelists`
--
ALTER TABLE `members_whitelists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `since_index` (`since`),
  ADD KEY `until` (`until`),
  ADD KEY `members_whitelists_member_id_fk` (`member_id`);

--
-- Indexy pro tabulku `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `messages_automatical_activations`
--
ALTER TABLE `messages_automatical_activations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id_fk` (`message_id`);

--
-- Indexy pro tabulku `messages_ip_addresses`
--
ALTER TABLE `messages_ip_addresses`
  ADD PRIMARY KEY (`message_id`,`ip_address_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ip_address_id` (`ip_address_id`);

--
-- Indexy pro tabulku `monitor_hosts`
--
ALTER TABLE `monitor_hosts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexy pro tabulku `phone_calls`
--
ALTER TABLE `phone_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_invoice_users_id` (`phone_invoice_user_id`),
  ADD KEY `datetime` (`datetime`),
  ADD KEY `number` (`number`);

--
-- Indexy pro tabulku `phone_connections`
--
ALTER TABLE `phone_connections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_invoice_users_id` (`phone_invoice_user_id`),
  ADD KEY `datetime` (`datetime`);

--
-- Indexy pro tabulku `phone_fixed_calls`
--
ALTER TABLE `phone_fixed_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_invoice_users_id` (`phone_invoice_user_id`),
  ADD KEY `datetime` (`datetime`),
  ADD KEY `number` (`number`);

--
-- Indexy pro tabulku `phone_invoices`
--
ALTER TABLE `phone_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `phone_invoice_users`
--
ALTER TABLE `phone_invoice_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`phone_invoice_id`),
  ADD KEY `phone_invoice_id` (`phone_invoice_id`),
  ADD KEY `phone_number` (`phone_number`),
  ADD KEY `transfer_id` (`transfer_id`);

--
-- Indexy pro tabulku `phone_operators`
--
ALTER TABLE `phone_operators`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexy pro tabulku `phone_operator_prefixes`
--
ALTER TABLE `phone_operator_prefixes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prefix` (`prefix`),
  ADD KEY `phone_operator_id` (`phone_operator_id`);

--
-- Indexy pro tabulku `phone_pays`
--
ALTER TABLE `phone_pays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_invoice_users_id` (`phone_invoice_user_id`),
  ADD KEY `datetime` (`datetime`),
  ADD KEY `number` (`number`);

--
-- Indexy pro tabulku `phone_roaming_sms_messages`
--
ALTER TABLE `phone_roaming_sms_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_invoice_users_id` (`phone_invoice_user_id`),
  ADD KEY `datetime` (`datetime`);

--
-- Indexy pro tabulku `phone_sms_messages`
--
ALTER TABLE `phone_sms_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_invoice_users_id` (`phone_invoice_user_id`),
  ADD KEY `datetime` (`datetime`),
  ADD KEY `number` (`number`);

--
-- Indexy pro tabulku `phone_vpn_calls`
--
ALTER TABLE `phone_vpn_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_invoice_users_id` (`phone_invoice_user_id`),
  ADD KEY `datetime` (`datetime`),
  ADD KEY `number` (`number`);

--
-- Indexy pro tabulku `private_users_contacts`
--
ALTER TABLE `private_users_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`contact_id`),
  ADD KEY `private_users_contacts_ibfk_2` (`contact_id`);

--
-- Indexy pro tabulku `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `comments_thread_id` (`comments_thread_id`),
  ADD KEY `approval_template_id` (`approval_template_id`);

--
-- Indexy pro tabulku `sms_messages`
--
ALTER TABLE `sms_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sms_message_id` (`sms_message_id`),
  ADD KEY `sender` (`sender`),
  ADD KEY `receiver` (`receiver`);

--
-- Indexy pro tabulku `speed_classes`
--
ALTER TABLE `speed_classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `streets`
--
ALTER TABLE `streets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `street` (`street`),
  ADD KEY `town_id` (`town_id`);

--
-- Indexy pro tabulku `subnets`
--
ALTER TABLE `subnets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `network_address` (`network_address`),
  ADD KEY `netmask` (`netmask`);

--
-- Indexy pro tabulku `subnets_owners`
--
ALTER TABLE `subnets_owners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `redirect` (`redirect`),
  ADD KEY `subnet_id` (`subnet_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexy pro tabulku `towns`
--
ALTER TABLE `towns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `town` (`town`),
  ADD KEY `zip_code` (`zip_code`);

--
-- Indexy pro tabulku `transfers`
--
ALTER TABLE `transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `datetime` (`datetime`,`text`),
  ADD KEY `origin_id` (`origin_id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `translations`
--
ALTER TABLE `translations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `original_term` (`original_term`);

--
-- Indexy pro tabulku `ulog2_ct`
--
ALTER TABLE `ulog2_ct`
  ADD KEY `ct_tuple` (`flow_start_sec`,`orig_ip_daddr`,`orig_l4_dport`,`reply_l4_dport`);

--
-- Indexy pro tabulku `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `name` (`name`),
  ADD KEY `surname` (`surname`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexy pro tabulku `users_contacts`
--
ALTER TABLE `users_contacts`
  ADD PRIMARY KEY (`user_id`,`contact_id`),
  ADD KEY `users_contacts_ibfk_2` (`contact_id`);

--
-- Indexy pro tabulku `users_keys`
--
ALTER TABLE `users_keys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `user_favourite_pages`
--
ALTER TABLE `user_favourite_pages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `variable_symbols`
--
ALTER TABLE `variable_symbols`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `variable_symbol` (`variable_symbol`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexy pro tabulku `vlans`
--
ALTER TABLE `vlans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag_802_1q` (`tag_802_1q`),
  ADD KEY `name` (`name`);

--
-- Indexy pro tabulku `voip_sips`
--
ALTER TABLE `voip_sips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexy pro tabulku `voip_voicemail_users`
--
ALTER TABLE `voip_voicemail_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`);

--
-- Indexy pro tabulku `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_id` (`fk_id`),
  ADD KEY `aro_group_id` (`aro_group_id`);

--
-- Indexy pro tabulku `watchers`
--
ALTER TABLE `watchers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`type`,`fk_id`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `acl`
--
ALTER TABLE `acl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `address_points`
--
ALTER TABLE `address_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `allowed_subnets`
--
ALTER TABLE `allowed_subnets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `allowed_subnets_counts`
--
ALTER TABLE `allowed_subnets_counts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `approval_templates`
--
ALTER TABLE `approval_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `approval_template_items`
--
ALTER TABLE `approval_template_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `approval_types`
--
ALTER TABLE `approval_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `aro_groups`
--
ALTER TABLE `aro_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `bank_accounts_automatical_downloads`
--
ALTER TABLE `bank_accounts_automatical_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `bank_statements`
--
ALTER TABLE `bank_statements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `bank_transfers`
--
ALTER TABLE `bank_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `cash`
--
ALTER TABLE `cash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `clouds`
--
ALTER TABLE `clouds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `comments_threads`
--
ALTER TABLE `comments_threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `connection_requests`
--
ALTER TABLE `connection_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `countries`
--
ALTER TABLE `countries`
  MODIFY `id` smallint(6) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `device_active_links`
--
ALTER TABLE `device_active_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `device_admins`
--
ALTER TABLE `device_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `device_engineers`
--
ALTER TABLE `device_engineers`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `device_templates`
--
ALTER TABLE `device_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `email_queues`
--
ALTER TABLE `email_queues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `email_queue_attachments`
--
ALTER TABLE `email_queue_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `enum_types`
--
ALTER TABLE `enum_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `enum_type_names`
--
ALTER TABLE `enum_type_names`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `filter_queries`
--
ALTER TABLE `filter_queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `ifaces`
--
ALTER TABLE `ifaces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `ifaces_relationships`
--
ALTER TABLE `ifaces_relationships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `ifaces_vlans`
--
ALTER TABLE `ifaces_vlans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `invoice_templates`
--
ALTER TABLE `invoice_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `ip6_addresses`
--
ALTER TABLE `ip6_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `ip_addresses`
--
ALTER TABLE `ip_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `job_reports`
--
ALTER TABLE `job_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `links`
--
ALTER TABLE `links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `local_subnets`
--
ALTER TABLE `local_subnets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `log_queues`
--
ALTER TABLE `log_queues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `mail_messages`
--
ALTER TABLE `mail_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `membership_interrupts`
--
ALTER TABLE `membership_interrupts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `membership_transfers`
--
ALTER TABLE `membership_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `members_domiciles`
--
ALTER TABLE `members_domiciles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `members_fees`
--
ALTER TABLE `members_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `members_whitelists`
--
ALTER TABLE `members_whitelists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `messages_automatical_activations`
--
ALTER TABLE `messages_automatical_activations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `monitor_hosts`
--
ALTER TABLE `monitor_hosts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_calls`
--
ALTER TABLE `phone_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_connections`
--
ALTER TABLE `phone_connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_fixed_calls`
--
ALTER TABLE `phone_fixed_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_invoices`
--
ALTER TABLE `phone_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_invoice_users`
--
ALTER TABLE `phone_invoice_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_operators`
--
ALTER TABLE `phone_operators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_operator_prefixes`
--
ALTER TABLE `phone_operator_prefixes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_pays`
--
ALTER TABLE `phone_pays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_roaming_sms_messages`
--
ALTER TABLE `phone_roaming_sms_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_sms_messages`
--
ALTER TABLE `phone_sms_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `phone_vpn_calls`
--
ALTER TABLE `phone_vpn_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `private_users_contacts`
--
ALTER TABLE `private_users_contacts`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `sms_messages`
--
ALTER TABLE `sms_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `speed_classes`
--
ALTER TABLE `speed_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `streets`
--
ALTER TABLE `streets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `subnets`
--
ALTER TABLE `subnets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `subnets_owners`
--
ALTER TABLE `subnets_owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `towns`
--
ALTER TABLE `towns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `transfers`
--
ALTER TABLE `transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `translations`
--
ALTER TABLE `translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `users_keys`
--
ALTER TABLE `users_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `user_favourite_pages`
--
ALTER TABLE `user_favourite_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `variable_symbols`
--
ALTER TABLE `variable_symbols`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `vlans`
--
ALTER TABLE `vlans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `voip_sips`
--
ALTER TABLE `voip_sips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `voip_voicemail_users`
--
ALTER TABLE `voip_voicemail_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `watchers`
--
ALTER TABLE `watchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `accounts_ibfk_2` FOREIGN KEY (`account_attribute_id`) REFERENCES `account_attributes` (`id`),
  ADD CONSTRAINT `accounts_ibfk_3` FOREIGN KEY (`comments_thread_id`) REFERENCES `comments_threads` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `accounts_bank_accounts`
--
ALTER TABLE `accounts_bank_accounts`
  ADD CONSTRAINT `accounts_bank_accounts_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accounts_bank_accounts_ibfk_2` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `aco_map`
--
ALTER TABLE `aco_map`
  ADD CONSTRAINT `aco_map_ibfk_1` FOREIGN KEY (`acl_id`) REFERENCES `acl` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `address_points`
--
ALTER TABLE `address_points`
  ADD CONSTRAINT `address_points_ibfk_1` FOREIGN KEY (`street_id`) REFERENCES `streets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `address_points_ibfk_2` FOREIGN KEY (`town_id`) REFERENCES `towns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `address_points_ibfk_3` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `allowed_subnets`
--
ALTER TABLE `allowed_subnets`
  ADD CONSTRAINT `allowed_subnets_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `allowed_subnets_ibfk_2` FOREIGN KEY (`subnet_id`) REFERENCES `subnets` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `allowed_subnets_counts`
--
ALTER TABLE `allowed_subnets_counts`
  ADD CONSTRAINT `allowed_subnets_counts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `approval_template_items`
--
ALTER TABLE `approval_template_items`
  ADD CONSTRAINT `approval_template_items_ibfk_1` FOREIGN KEY (`approval_template_id`) REFERENCES `approval_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `approval_template_items_ibfk_2` FOREIGN KEY (`approval_type_id`) REFERENCES `approval_types` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `aro_groups_map`
--
ALTER TABLE `aro_groups_map`
  ADD CONSTRAINT `aro_groups_map_ibfk_1` FOREIGN KEY (`acl_id`) REFERENCES `acl` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aro_groups_map_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `aro_groups` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `axo_map`
--
ALTER TABLE `axo_map`
  ADD CONSTRAINT `axo_map_ibfk_1` FOREIGN KEY (`acl_id`) REFERENCES `acl` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD CONSTRAINT `bank_accounts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `bank_accounts_automatical_downloads`
--
ALTER TABLE `bank_accounts_automatical_downloads`
  ADD CONSTRAINT `bank_account_id_fk` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `bank_statements`
--
ALTER TABLE `bank_statements`
  ADD CONSTRAINT `bank_statements_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`),
  ADD CONSTRAINT `bank_statements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `bank_transfers`
--
ALTER TABLE `bank_transfers`
  ADD CONSTRAINT `bank_transfers_ibfk_1` FOREIGN KEY (`origin_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bank_transfers_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bank_transfers_ibfk_3` FOREIGN KEY (`transfer_id`) REFERENCES `transfers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bank_transfers_ibfk_4` FOREIGN KEY (`bank_statement_id`) REFERENCES `bank_statements` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `cash`
--
ALTER TABLE `cash`
  ADD CONSTRAINT `cash_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_ibfk_2` FOREIGN KEY (`transfer_id`) REFERENCES `transfers` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `clouds_subnets`
--
ALTER TABLE `clouds_subnets`
  ADD CONSTRAINT `clouds_subnets_ibfk_1` FOREIGN KEY (`cloud_id`) REFERENCES `clouds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clouds_subnets_ibfk_2` FOREIGN KEY (`subnet_id`) REFERENCES `subnets` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `clouds_users`
--
ALTER TABLE `clouds_users`
  ADD CONSTRAINT `clouds_users_ibfk_1` FOREIGN KEY (`cloud_id`) REFERENCES `clouds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clouds_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`comments_thread_id`) REFERENCES `comments_threads` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `connection_requests`
--
ALTER TABLE `connection_requests`
  ADD CONSTRAINT `added_user_id_fk` FOREIGN KEY (`added_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `comments_thread_id_fk` FOREIGN KEY (`comments_thread_id`) REFERENCES `comments_threads` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `decided_user_id_fk` FOREIGN KEY (`decided_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `device_id_fk` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `device_template_id_fk` FOREIGN KEY (`device_template_id`) REFERENCES `device_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `device_type_id_fk` FOREIGN KEY (`device_type_id`) REFERENCES `enum_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `member_id_fk` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subnet_id_fk` FOREIGN KEY (`subnet_id`) REFERENCES `subnets` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `contacts_countries`
--
ALTER TABLE `contacts_countries`
  ADD CONSTRAINT `contacts_countries_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_countries_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_countries_ibfk_3` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_countries_ibfk_4` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devices_ibfk_2` FOREIGN KEY (`address_point_id`) REFERENCES `address_points` (`id`);

--
-- Omezení pro tabulku `device_active_links_map`
--
ALTER TABLE `device_active_links_map`
  ADD CONSTRAINT `device_active_links_map_ibfk_1` FOREIGN KEY (`device_active_link_id`) REFERENCES `device_active_links` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `device_admins`
--
ALTER TABLE `device_admins`
  ADD CONSTRAINT `device_admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `device_admins_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `device_engineers`
--
ALTER TABLE `device_engineers`
  ADD CONSTRAINT `device_engineers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `device_engineers_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `device_templates`
--
ALTER TABLE `device_templates`
  ADD CONSTRAINT `device_templates_ibfk_enum_type` FOREIGN KEY (`enum_type_id`) REFERENCES `enum_types` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `enum_types`
--
ALTER TABLE `enum_types`
  ADD CONSTRAINT `enum_types_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `enum_type_names` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `enum_types` (`id`);

--
-- Omezení pro tabulku `groups_aro_map`
--
ALTER TABLE `groups_aro_map`
  ADD CONSTRAINT `groups_aro_map_ibfk_1` FOREIGN KEY (`aro_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `groups_aro_map_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `aro_groups` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `ifaces`
--
ALTER TABLE `ifaces`
  ADD CONSTRAINT `ifaces_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ifaces_ibfk_2` FOREIGN KEY (`link_id`) REFERENCES `links` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `ifaces_relationships`
--
ALTER TABLE `ifaces_relationships`
  ADD CONSTRAINT `ifaces_relationships_ibfk_1` FOREIGN KEY (`parent_iface_id`) REFERENCES `ifaces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ifaces_relationships_ibfk_2` FOREIGN KEY (`iface_id`) REFERENCES `ifaces` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `ifaces_vlans`
--
ALTER TABLE `ifaces_vlans`
  ADD CONSTRAINT `ifaces_vlans_ibfk_1` FOREIGN KEY (`iface_id`) REFERENCES `ifaces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ifaces_vlans_ibfk_2` FOREIGN KEY (`vlan_id`) REFERENCES `vlans` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `ip_addresses`
--
ALTER TABLE `ip_addresses`
  ADD CONSTRAINT `ip_addresses_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ip_addresses_ibfk_5` FOREIGN KEY (`iface_id`) REFERENCES `ifaces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ip_addresses_ibfk_6` FOREIGN KEY (`subnet_id`) REFERENCES `subnets` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`job_report_id`) REFERENCES `job_reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jobs_ibfk_3` FOREIGN KEY (`added_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jobs_ibfk_4` FOREIGN KEY (`approval_template_id`) REFERENCES `approval_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jobs_ibfk_5` FOREIGN KEY (`transfer_id`) REFERENCES `transfers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jobs_ibfk_6` FOREIGN KEY (`comments_thread_id`) REFERENCES `comments_threads` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jobs_ibfk_7` FOREIGN KEY (`previous_rejected_work_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `job_reports`
--
ALTER TABLE `job_reports`
  ADD CONSTRAINT `job_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_reports_ibfk_2` FOREIGN KEY (`approval_template_id`) REFERENCES `approval_templates` (`id`),
  ADD CONSTRAINT `job_reports_ibfk_4` FOREIGN KEY (`added_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `job_reports_ibfk_5` FOREIGN KEY (`transfer_id`) REFERENCES `transfers` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `log_queues`
--
ALTER TABLE `log_queues`
  ADD CONSTRAINT `closed_by_user_id_fk` FOREIGN KEY (`closed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `log_queues_comments_thread_id_fk` FOREIGN KEY (`comments_thread_id`) REFERENCES `comments_threads` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `mail_messages`
--
ALTER TABLE `mail_messages`
  ADD CONSTRAINT `mail_messages_ibfk_1` FOREIGN KEY (`from_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mail_messages_ibfk_2` FOREIGN KEY (`to_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `members_ibfk_2` FOREIGN KEY (`address_point_id`) REFERENCES `address_points` (`id`),
  ADD CONSTRAINT `speed_class_id_fk` FOREIGN KEY (`speed_class_id`) REFERENCES `speed_classes` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `membership_interrupts`
--
ALTER TABLE `membership_interrupts`
  ADD CONSTRAINT `membership_interrupts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `membership_interrupts_ibfk_2` FOREIGN KEY (`members_fee_id`) REFERENCES `members_fees` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `membership_transfers`
--
ALTER TABLE `membership_transfers`
  ADD CONSTRAINT `from_member_id_fk` FOREIGN KEY (`from_member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `to_member_id_fk` FOREIGN KEY (`to_member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `members_domiciles`
--
ALTER TABLE `members_domiciles`
  ADD CONSTRAINT `members_domiciles_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_domiciles_ibfk_2` FOREIGN KEY (`address_point_id`) REFERENCES `address_points` (`id`);

--
-- Omezení pro tabulku `members_fees`
--
ALTER TABLE `members_fees`
  ADD CONSTRAINT `members_fees_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_fees_ibfk_2` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `members_whitelists`
--
ALTER TABLE `members_whitelists`
  ADD CONSTRAINT `members_whitelists_member_id_fk` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `messages_automatical_activations`
--
ALTER TABLE `messages_automatical_activations`
  ADD CONSTRAINT `message_id_fk` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `messages_ip_addresses`
--
ALTER TABLE `messages_ip_addresses`
  ADD CONSTRAINT `messages_ip_addresses_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ip_addresses_ibfk_2` FOREIGN KEY (`ip_address_id`) REFERENCES `ip_addresses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ip_addresses_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_ip_addresses_ibfk_4` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ip_addresses_ibfk_5` FOREIGN KEY (`ip_address_id`) REFERENCES `ip_addresses` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `monitor_hosts`
--
ALTER TABLE `monitor_hosts`
  ADD CONSTRAINT `monitor_hosts_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `phone_calls`
--
ALTER TABLE `phone_calls`
  ADD CONSTRAINT `phone_calls_ibfk_1` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_calls_ibfk_2` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `phone_connections`
--
ALTER TABLE `phone_connections`
  ADD CONSTRAINT `phone_connections_ibfk_1` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_connections_ibfk_2` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `phone_fixed_calls`
--
ALTER TABLE `phone_fixed_calls`
  ADD CONSTRAINT `phone_fixed_calls_ibfk_1` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_fixed_calls_ibfk_2` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `phone_invoice_users`
--
ALTER TABLE `phone_invoice_users`
  ADD CONSTRAINT `phone_invoice_users_ibfk_1` FOREIGN KEY (`phone_invoice_id`) REFERENCES `phone_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_invoice_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_invoice_users_ibfk_3` FOREIGN KEY (`phone_invoice_id`) REFERENCES `phone_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_invoice_users_ibfk_4` FOREIGN KEY (`transfer_id`) REFERENCES `transfers` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `phone_operators`
--
ALTER TABLE `phone_operators`
  ADD CONSTRAINT `phone_operators_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `phone_operator_prefixes`
--
ALTER TABLE `phone_operator_prefixes`
  ADD CONSTRAINT `phone_operator_prefixes_ibfk_1` FOREIGN KEY (`phone_operator_id`) REFERENCES `phone_operators` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `phone_pays`
--
ALTER TABLE `phone_pays`
  ADD CONSTRAINT `phone_pays_ibfk_1` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_pays_ibfk_2` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `phone_roaming_sms_messages`
--
ALTER TABLE `phone_roaming_sms_messages`
  ADD CONSTRAINT `phone_roaming_sms_messages_ibfk_1` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_roaming_sms_messages_ibfk_2` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `phone_sms_messages`
--
ALTER TABLE `phone_sms_messages`
  ADD CONSTRAINT `phone_sms_messages_ibfk_1` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_sms_messages_ibfk_2` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `phone_vpn_calls`
--
ALTER TABLE `phone_vpn_calls`
  ADD CONSTRAINT `phone_vpn_calls_ibfk_1` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_vpn_calls_ibfk_2` FOREIGN KEY (`phone_invoice_user_id`) REFERENCES `phone_invoice_users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `private_users_contacts`
--
ALTER TABLE `private_users_contacts`
  ADD CONSTRAINT `private_users_contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_users_contacts_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`approval_template_id`) REFERENCES `approval_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`comments_thread_id`) REFERENCES `comments` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `sms_messages`
--
ALTER TABLE `sms_messages`
  ADD CONSTRAINT `sms_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sms_messages_ibfk_2` FOREIGN KEY (`sms_message_id`) REFERENCES `sms_messages` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `streets`
--
ALTER TABLE `streets`
  ADD CONSTRAINT `streets_ibfk_1` FOREIGN KEY (`town_id`) REFERENCES `towns` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `subnets_owners`
--
ALTER TABLE `subnets_owners`
  ADD CONSTRAINT `subnets_owners_ibfk_1` FOREIGN KEY (`subnet_id`) REFERENCES `subnets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subnets_owners_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `transfers`
--
ALTER TABLE `transfers`
  ADD CONSTRAINT `transfers_ibfk_1` FOREIGN KEY (`origin_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transfers_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transfers_ibfk_3` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transfers_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Omezení pro tabulku `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `users_contacts`
--
ALTER TABLE `users_contacts`
  ADD CONSTRAINT `users_contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_contacts_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `users_keys`
--
ALTER TABLE `users_keys`
  ADD CONSTRAINT `users_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `user_favourite_pages`
--
ALTER TABLE `user_favourite_pages`
  ADD CONSTRAINT `user_favourite_pages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `variable_symbols`
--
ALTER TABLE `variable_symbols`
  ADD CONSTRAINT `variable_symbols_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `voip_sips`
--
ALTER TABLE `voip_sips`
  ADD CONSTRAINT `voip_sips_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `votes_ibfk_3` FOREIGN KEY (`aro_group_id`) REFERENCES `aro_groups` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `watchers`
--
ALTER TABLE `watchers`
  ADD CONSTRAINT `watchers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
