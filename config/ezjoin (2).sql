-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- 생성 시간: 26-01-16 05:03
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
  `id` bigint(20) UNSIGNED NOT NULL,
  `contract_id` bigint(20) UNSIGNED NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `attachmented_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contracts`
--

CREATE TABLE `contracts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `template_id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `complex` int(11) DEFAULT NULL,
  `building` int(11) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` enum('PROGRESS','DONE') DEFAULT 'PROGRESS',
  `current_signer_order` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contract_attachments`
--

CREATE TABLE `contract_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contract_id` bigint(20) UNSIGNED NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `required` tinyint(4) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contract_fields`
--

CREATE TABLE `contract_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contract_id` bigint(20) UNSIGNED NOT NULL,
  `page_no` int(11) DEFAULT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `field_type` enum('TEXT','CHECK','SIGN','STAMP','NUM','DATE') DEFAULT NULL,
  `x` bigint(20) DEFAULT NULL,
  `y` bigint(20) DEFAULT NULL,
  `width` bigint(20) DEFAULT NULL,
  `height` bigint(20) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `required` tinyint(4) DEFAULT NULL,
  `ch_plural` tinyint(4) DEFAULT NULL,
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
  `id` bigint(20) UNSIGNED NOT NULL,
  `contract_id` bigint(20) UNSIGNED NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `signer_type` enum('USER','GUEST') NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `guest_identity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_proxy` tinyint(4) DEFAULT 0,
  `display_name` varchar(100) DEFAULT NULL,
  `display_phone` varchar(20) DEFAULT NULL,
  `proxy_identity_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contract_trash`
--

CREATE TABLE `contract_trash` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contract_id` bigint(20) UNSIGNED NOT NULL,
  `deleted_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_by_role` enum('ADMIN','OPERATOR','STAFF') DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `contract_values`
--

CREATE TABLE `contract_values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contract_field_id` bigint(20) UNSIGNED NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `value_ch` tinyint(4) DEFAULT NULL,
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
  `id` bigint(20) UNSIGNED NOT NULL,
  `ci` varchar(255) DEFAULT NULL,
  `di` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `carrier` enum('SKT','KT','LGU','MVNO') DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `operator_agency`
--

CREATE TABLE `operator_agency` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `manager_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `operator_agency`
--

INSERT INTO `operator_agency` (`id`, `site_id`, `manager_name`, `manager_phone`) VALUES
(1, 1, '담당자1', '01011111234'),
(2, 2, '담당자2', '01022221234'),
(3, 3, '담당자3', '01033331234');

-- --------------------------------------------------------

--
-- 테이블 구조 `operator_company`
--

CREATE TABLE `operator_company` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `ceo_name` varchar(100) DEFAULT NULL,
  `company_phone` varchar(100) DEFAULT NULL,
  `office_address` varchar(255) DEFAULT NULL,
  `modelhouse_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `operator_company`
--

INSERT INTO `operator_company` (`id`, `site_id`, `company_name`, `position`, `ceo_name`, `company_phone`, `office_address`, `modelhouse_address`) VALUES
(1, 1, '1번상호', '팀장', '대표자1', '021111', '1번_시군구_사무실', '1번_시군구_모델하우스'),
(2, 2, '2번상호', '팀장', '대표자2', '022222', '2번_시군구_사무실', '2번_시군구_모델하우스'),
(3, 3, '3번상호', '팀장', '대표자3', '023333', '3번_시군구_사무실', '3번_시군구_모델하우스');

-- --------------------------------------------------------

--
-- 테이블 구조 `sites`
--

CREATE TABLE `sites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `operator_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `sites`
--

INSERT INTO `sites` (`id`, `name`, `operator_id`, `created_at`) VALUES
(1, '1번_시군구_현장', 2, '2026-01-15 01:30:06'),
(2, '2번_시군구_현장', 3, '2026-01-15 01:31:19'),
(3, '3번_시군구_현장', 4, '2026-01-15 01:32:19');

-- --------------------------------------------------------

--
-- 테이블 구조 `site_service`
--

CREATE TABLE `site_service` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `service_start` timestamp NULL DEFAULT NULL,
  `service_end` timestamp NULL DEFAULT NULL,
  `is_enabled` tinyint(4) DEFAULT 1,
  `max_contract_count` bigint(20) DEFAULT NULL,
  `used_contract_count` bigint(20) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `site_staff`
--

CREATE TABLE `site_staff` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `stamp`
--

CREATE TABLE `stamp` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `is_hided` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `templates`
--

CREATE TABLE `templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `company_name` enum('COMPANY','AGENCY') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `page_count` bigint(20) DEFAULT NULL,
  `is_favorite` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `sort_order` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `template_attachments`
--

CREATE TABLE `template_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `template_id` bigint(20) UNSIGNED NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `required` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `template_categories`
--

CREATE TABLE `template_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `site_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sort_order` bigint(20) DEFAULT NULL,
  `is_deleted` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `template_fields`
--

CREATE TABLE `template_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `template_id` bigint(20) UNSIGNED NOT NULL,
  `page_no` int(11) DEFAULT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `field_type` enum('TEXT','CHECK','SIGN','STAMP','NUM','DATE') DEFAULT NULL,
  `x` bigint(20) DEFAULT NULL,
  `y` bigint(20) DEFAULT NULL,
  `width` bigint(20) DEFAULT NULL,
  `height` bigint(20) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `required` tinyint(4) DEFAULT NULL,
  `ch_plural` tinyint(4) DEFAULT NULL,
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
  `id` bigint(20) UNSIGNED NOT NULL,
  `template_id` bigint(20) UNSIGNED NOT NULL,
  `signer_order` int(11) DEFAULT NULL,
  `signer_role` enum('SITE','GUEST') DEFAULT NULL,
  `company_name` enum('COMPANY','AGENCY') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('ADMIN','OPERATOR','STAFF') NOT NULL,
  `status` enum('ACTIVE','INACTIVE','WITHDRAWN') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 테이블의 덤프 데이터 `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin@admin.com', '$2y$10$9sWI0oPtzZzFmSsdFFARt.3GSqIcHghegj3x1DacpjxJ4zE4QJM8O', '관리자', '01012341234', 'ADMIN', 'ACTIVE', '2026-01-15 01:26:14', '2026-01-15 01:26:31'),
(2, 'oper1@oper.com', '$2y$10$DMMIB8MBR7.YndJmGmU9veTn4yTyz5dE/l1XrChbPCoytWSPZShw6', '운영자1', '01012341111', 'OPERATOR', 'ACTIVE', '2026-01-15 01:30:06', '2026-01-15 01:30:06'),
(3, 'oper2@oper.com', '$2y$10$2fprHr2YsK2AnR5p64vpXucoruX8UjUoYLfkDFnlkhB3RM.sgQdVW', '운영자2', '01012342222', 'OPERATOR', 'ACTIVE', '2026-01-15 01:31:19', '2026-01-15 01:31:19'),
(4, 'oper3@oper.com', '$2y$10$XYHLF2Chl9NBNRHoJJQm7uK1kkQWj9HMWcMR.Tka3IJ5kqNGq8BLu', '운영자3', '01012343333', 'OPERATOR', 'ACTIVE', '2026-01-15 01:32:19', '2026-01-15 01:32:19'),
(5, 'staff1@staff.com', '$2y$10$EW3pUTVheW7pP/dOnwYdZe1l3Xp8mIpRkHG3uvY2LtjyJUcoAAHcC', '직원1', '01011111234', 'STAFF', 'ACTIVE', '2026-01-15 01:33:23', '2026-01-15 01:34:22'),
(6, 'staff2@staff.com', '$2y$10$tN2ivvY.DP56121dmL.VVeJYPIYpm/7IKj/N9rTny2M5Gb4gFRhTa', '직원2', '01022221234', 'STAFF', 'ACTIVE', '2026-01-15 01:34:06', '2026-01-15 01:34:06');

-- --------------------------------------------------------

--
-- 테이블 구조 `user_identity_logs`
--

CREATE TABLE `user_identity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ci` varchar(255) DEFAULT NULL,
  `di` varchar(255) DEFAULT NULL,
  `carrier` enum('SKT','KT','LGU','MVNO') DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 덤프된 테이블의 인덱스
--

--
-- 테이블의 인덱스 `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- 테이블의 인덱스 `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `template_id` (`template_id`);

--
-- 테이블의 인덱스 `contract_attachments`
--
ALTER TABLE `contract_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- 테이블의 인덱스 `contract_fields`
--
ALTER TABLE `contract_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- 테이블의 인덱스 `contract_signers`
--
ALTER TABLE `contract_signers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- 테이블의 인덱스 `contract_trash`
--
ALTER TABLE `contract_trash`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- 테이블의 인덱스 `contract_values`
--
ALTER TABLE `contract_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_field_id` (`contract_field_id`);

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
  ADD KEY `site_id` (`site_id`);

--
-- 테이블의 인덱스 `operator_company`
--
ALTER TABLE `operator_company`
  ADD PRIMARY KEY (`id`),
  ADD KEY `site_id` (`site_id`);

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
  ADD KEY `site_id` (`site_id`),
  ADD KEY `user_id` (`user_id`);

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
  ADD KEY `site_id` (`site_id`),
  ADD KEY `category_id` (`category_id`);

--
-- 테이블의 인덱스 `template_attachments`
--
ALTER TABLE `template_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`);

--
-- 테이블의 인덱스 `template_categories`
--
ALTER TABLE `template_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `site_id` (`site_id`);

--
-- 테이블의 인덱스 `template_fields`
--
ALTER TABLE `template_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`);

--
-- 테이블의 인덱스 `template_signers`
--
ALTER TABLE `template_signers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_attachments`
--
ALTER TABLE `contract_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_fields`
--
ALTER TABLE `contract_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_signers`
--
ALTER TABLE `contract_signers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_trash`
--
ALTER TABLE `contract_trash`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `contract_values`
--
ALTER TABLE `contract_values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `guest_identity_logs`
--
ALTER TABLE `guest_identity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `operator_agency`
--
ALTER TABLE `operator_agency`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 테이블의 AUTO_INCREMENT `operator_company`
--
ALTER TABLE `operator_company`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 테이블의 AUTO_INCREMENT `sites`
--
ALTER TABLE `sites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 테이블의 AUTO_INCREMENT `site_service`
--
ALTER TABLE `site_service`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `site_staff`
--
ALTER TABLE `site_staff`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `stamp`
--
ALTER TABLE `stamp`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `templates`
--
ALTER TABLE `templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `template_attachments`
--
ALTER TABLE `template_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `template_categories`
--
ALTER TABLE `template_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `template_fields`
--
ALTER TABLE `template_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `template_signers`
--
ALTER TABLE `template_signers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 테이블의 AUTO_INCREMENT `user_identity_logs`
--
ALTER TABLE `user_identity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 덤프된 테이블의 제약사항
--

--
-- 테이블의 제약사항 `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`),
  ADD CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`);

--
-- 테이블의 제약사항 `contract_attachments`
--
ALTER TABLE `contract_attachments`
  ADD CONSTRAINT `contract_attachments_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contract_fields`
--
ALTER TABLE `contract_fields`
  ADD CONSTRAINT `contract_fields_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contract_signers`
--
ALTER TABLE `contract_signers`
  ADD CONSTRAINT `contract_signers_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contract_trash`
--
ALTER TABLE `contract_trash`
  ADD CONSTRAINT `contract_trash_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);

--
-- 테이블의 제약사항 `contract_values`
--
ALTER TABLE `contract_values`
  ADD CONSTRAINT `contract_values_ibfk_1` FOREIGN KEY (`contract_field_id`) REFERENCES `contract_fields` (`id`);

--
-- 테이블의 제약사항 `operator_agency`
--
ALTER TABLE `operator_agency`
  ADD CONSTRAINT `operator_agency_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

--
-- 테이블의 제약사항 `operator_company`
--
ALTER TABLE `operator_company`
  ADD CONSTRAINT `operator_company_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

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
  ADD CONSTRAINT `site_staff_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`),
  ADD CONSTRAINT `site_staff_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `stamp`
--
ALTER TABLE `stamp`
  ADD CONSTRAINT `fk_stamp_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

--
-- 테이블의 제약사항 `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `templates_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`),
  ADD CONSTRAINT `templates_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `template_categories` (`id`);

--
-- 테이블의 제약사항 `template_attachments`
--
ALTER TABLE `template_attachments`
  ADD CONSTRAINT `template_attachments_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`);

--
-- 테이블의 제약사항 `template_categories`
--
ALTER TABLE `template_categories`
  ADD CONSTRAINT `template_categories_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`);

--
-- 테이블의 제약사항 `template_fields`
--
ALTER TABLE `template_fields`
  ADD CONSTRAINT `template_fields_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`);

--
-- 테이블의 제약사항 `template_signers`
--
ALTER TABLE `template_signers`
  ADD CONSTRAINT `template_signers_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`);

--
-- 테이블의 제약사항 `user_identity_logs`
--
ALTER TABLE `user_identity_logs`
  ADD CONSTRAINT `fk_identity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
