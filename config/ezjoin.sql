-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- 생성 시간: 26-01-13 07:09
-- 서버 버전: 10.4.32-MariaDB
-- PHP 버전: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 데이터베이스: `ezjoin`
--

-- --------------------------------------------------------

--
-- 테이블 구조 `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `attachmented_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `complex` int(11) DEFAULT NULL,
  `building` int(11) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` enum('PROGRESS','DONE') DEFAULT 'PROGRESS',
  `current_signer_order` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contract_attachments`
--

CREATE TABLE `contract_attachments` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contract_fields`
--

CREATE TABLE `contract_fields` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `page_no` int(11) DEFAULT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `field_type` enum('TEXT','CHECK','SIGN','STAMP','NUM','DATE') DEFAULT NULL,
  `x` bigint(20) DEFAULT NULL,
  `y` bigint(20) DEFAULT NULL,
  `width` bigint(20) DEFAULT NULL,
  `height` bigint(20) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `required` tinyint(1) DEFAULT NULL,
  `ch_plural` tinyint(1) DEFAULT NULL,
  `ch_min` int(11) DEFAULT NULL,
  `ch_max` int(11) DEFAULT NULL,
  `t_style` varchar(50) DEFAULT NULL,
  `t_size` varchar(50) DEFAULT NULL,
  `t_array` enum('LEFT','CENTER','RIGHT','BOTH') DEFAULT NULL,
  `t_color` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contract_signers`
--

CREATE TABLE `contract_signers` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `signer_type` enum('USER','GUEST') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_identity_id` int(11) DEFAULT NULL,
  `is_proxy` tinyint(1) DEFAULT 0,
  `display_name` varchar(100) NOT NULL,
  `display_phone` varchar(20) NOT NULL,
  `proxy_identity_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contract_trash`
--

CREATE TABLE `contract_trash` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL,
  `deleted_by_role` enum('SUPER','OPERATOR','STAFF') DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contract_values`
--

CREATE TABLE `contract_values` (
  `id` int(11) NOT NULL,
  `contract_field_id` int(11) NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `value_ch` tinyint(1) DEFAULT NULL,
  `value_t` varchar(255) DEFAULT NULL,
  `value_n` bigint(20) DEFAULT NULL,
  `value_sign_origin_path` varchar(255) DEFAULT NULL,
  `value_sign_path` varchar(255) DEFAULT NULL,
  `value_stamp_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `guest_identity_logs`
--

CREATE TABLE `guest_identity_logs` (
  `id` int(11) NOT NULL,
  `ci` varchar(255) DEFAULT NULL,
  `di` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `carrier` enum('SKT','KT','LGU','MVNO') DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `operator_agency`
--

CREATE TABLE `operator_agency` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `manager_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `operator_company`
--

CREATE TABLE `operator_company` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `ceo_name` varchar(100) DEFAULT NULL,
  `office_address` varchar(255) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `sites`
--

CREATE TABLE `sites` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `operator_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `site_service`
--

CREATE TABLE `site_service` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `service_start` timestamp NULL DEFAULT NULL,
  `service_end` timestamp NULL DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `max_contract_count` bigint(20) DEFAULT NULL,
  `used_contract_count` bigint(20) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `site_staff`
--

CREATE TABLE `site_staff` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `stamp`
--

CREATE TABLE `stamp` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `is_hided` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `page_count` bigint(20) DEFAULT NULL,
  `is_favorite` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `company_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `template_attachments`
--

CREATE TABLE `template_attachments` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `template_categories`
--

CREATE TABLE `template_categories` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sort_order` bigint(20) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `template_fields`
--

CREATE TABLE `template_fields` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `page_no` int(11) DEFAULT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `field_type` enum('TEXT','CHECK','SIGN','STAMP','NUM','DATE') DEFAULT NULL,
  `x` bigint(20) DEFAULT NULL,
  `y` bigint(20) DEFAULT NULL,
  `width` bigint(20) DEFAULT NULL,
  `height` bigint(20) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `required` tinyint(1) DEFAULT NULL,
  `ch_plural` tinyint(1) DEFAULT NULL,
  `ch_min` int(11) DEFAULT NULL,
  `ch_max` int(11) DEFAULT NULL,
  `t_style` varchar(50) DEFAULT NULL,
  `t_size` varchar(50) DEFAULT NULL,
  `t_array` enum('LEFT','CENTER','RIGHT','BOTH') DEFAULT NULL,
  `t_color` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `template_signers`
--

CREATE TABLE `template_signers` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `signer_role` enum('SITE','GUEST') DEFAULT NULL,
  `company_name` enum('COMPANY','AGENCY') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('SUPER','OPERATOR','STAFF') NOT NULL,
  `status` enum('ACTIVE','INACTIVE','WITHDRAWN') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `user_identity_logs`
--

CREATE TABLE `user_identity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ci` varchar(255) DEFAULT NULL,
  `di` varchar(255) DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 덤프된 테이블의 인덱스
--

--
-- 테이블의 인덱스 `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_contract` (`contract_id`);

--
-- 테이블의 인덱스 `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_contract_site` (`site_id`),
  ADD KEY `fk_contract_template` (`template_id`);

--
-- 테이블의 인덱스 `contract_attachments`
--
ALTER TABLE `contract_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_attachment_contract` (`contract_id`);

--
-- 테이블의 인덱스 `contract_fields`
--
ALTER TABLE `contract_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_contract_field_contract` (`contract_id`);

--
-- 테이블의 인덱스 `contract_signers`
--
ALTER TABLE `contract_signers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_signer_contract` (`contract_id`);

--
-- 테이블의 인덱스 `contract_trash`
--
ALTER TABLE `contract_trash`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trash_contract` (`contract_id`);

--
-- 테이블의 인덱스 `contract_values`
--
ALTER TABLE `contract_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_value_field` (`contract_field_id`);

--
-- 테이블의 인덱스 `guest_identity_logs`
--
ALTER TABLE `guest_identity_logs`
  ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `operator_agency`
--
ALTER TABLE `operator_agency`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_agency_site` (`site_id`);

--
-- 테이블의 인덱스 `operator_company`
--
ALTER TABLE `operator_company`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_company_site` (`site_id`);

--
-- 테이블의 인덱스 `sites`
--
ALTER TABLE `sites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_site_operator` (`operator_id`);

--
-- 테이블의 인덱스 `site_service`
--
ALTER TABLE `site_service`
  ADD PRIMARY KEY (`id`),
  ADD KEY `site_id` (`site_id`);

--
-- 테이블의 인덱스 `site_staff`
--
ALTER TABLE `site_staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_staff_site` (`site_id`),
  ADD KEY `fk_staff_user` (`user_id`);

--
-- 테이블의 인덱스 `stamp`
--
ALTER TABLE `stamp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stamp_site` (`site_id`);

--
-- 테이블의 인덱스 `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_template_site` (`site_id`),
  ADD KEY `fk_template_category` (`category_id`);

--
-- 테이블의 인덱스 `template_attachments`
--
ALTER TABLE `template_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_attachment_template` (`template_id`);

--
-- 테이블의 인덱스 `template_categories`
--
ALTER TABLE `template_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category_site` (`site_id`);

--
-- 테이블의 인덱스 `template_fields`
--
ALTER TABLE `template_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_field_template` (`template_id`);

--
-- 테이블의 인덱스 `template_signers`
--
ALTER TABLE `template_signers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_signer_template` (`template_id`);

--
-- 테이블의 인덱스 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `user_identity_logs`
--
ALTER TABLE `user_identity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_identity_user` (`user_id`);

--
-- 덤프된 테이블의 AUTO_INCREMENT
--

--
-- 테이블의 AUTO_INCREMENT `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_attachments`
--
ALTER TABLE `contract_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_fields`
--
ALTER TABLE `contract_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_signers`
--
ALTER TABLE `contract_signers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_trash`
--
ALTER TABLE `contract_trash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_values`
--
ALTER TABLE `contract_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `guest_identity_logs`
--
ALTER TABLE `guest_identity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `operator_agency`
--
ALTER TABLE `operator_agency`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `operator_company`
--
ALTER TABLE `operator_company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `sites`
--
ALTER TABLE `sites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `site_service`
--
ALTER TABLE `site_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `site_staff`
--
ALTER TABLE `site_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `stamp`
--
ALTER TABLE `stamp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `template_attachments`
--
ALTER TABLE `template_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `template_categories`
--
ALTER TABLE `template_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `template_fields`
--
ALTER TABLE `template_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `template_signers`
--
ALTER TABLE `template_signers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `user_identity_logs`
--
ALTER TABLE `user_identity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 덤프된 테이블의 제약사항
--

--
-- 테이블의 제약사항 `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `fk_contract_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`),
  ADD CONSTRAINT `fk_contract_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`);

--
-- 테이블의 제약사항 `contract_attachments`
--
ALTER TABLE `contract_attachments`
  ADD CONSTRAINT `fk_attachment_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contract_fields`
--
ALTER TABLE `contract_fields`
  ADD CONSTRAINT `fk_contract_field_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contract_signers`
--
ALTER TABLE `contract_signers`
  ADD CONSTRAINT `fk_signer_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contract_trash`
--
ALTER TABLE `contract_trash`
  ADD CONSTRAINT `fk_trash_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contract_values`
--
ALTER TABLE `contract_values`
  ADD CONSTRAINT `fk_value_field` FOREIGN KEY (`contract_field_id`) REFERENCES `contract_fields` (`id`);

--
-- 테이블의 제약사항 `operator_agency`
--
ALTER TABLE `operator_agency`
  ADD CONSTRAINT `fk_agency_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

--
-- 테이블의 제약사항 `operator_company`
--
ALTER TABLE `operator_company`
  ADD CONSTRAINT `fk_company_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

--
-- 테이블의 제약사항 `sites`
--
ALTER TABLE `sites`
  ADD CONSTRAINT `fk_site_operator` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `site_service`
--
ALTER TABLE `site_service`
  ADD CONSTRAINT `site_service_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

--
-- 테이블의 제약사항 `site_staff`
--
ALTER TABLE `site_staff`
  ADD CONSTRAINT `fk_staff_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`),
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `stamp`
--
ALTER TABLE `stamp`
  ADD CONSTRAINT `fk_stamp_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

--
-- 테이블의 제약사항 `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `fk_template_category` FOREIGN KEY (`category_id`) REFERENCES `template_categories` (`id`),
  ADD CONSTRAINT `fk_template_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

--
-- 테이블의 제약사항 `template_attachments`
--
ALTER TABLE `template_attachments`
  ADD CONSTRAINT `fk_attachment_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`);

--
-- 테이블의 제약사항 `template_categories`
--
ALTER TABLE `template_categories`
  ADD CONSTRAINT `fk_category_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

--
-- 테이블의 제약사항 `template_fields`
--
ALTER TABLE `template_fields`
  ADD CONSTRAINT `fk_field_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`);

--
-- 테이블의 제약사항 `template_signers`
--
ALTER TABLE `template_signers`
  ADD CONSTRAINT `fk_signer_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`);

--
-- 테이블의 제약사항 `user_identity_logs`
--
ALTER TABLE `user_identity_logs`
  ADD CONSTRAINT `fk_identity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
