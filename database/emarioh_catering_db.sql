CREATE DATABASE IF NOT EXISTS `emarioh_catering_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `emarioh_catering_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(150) NOT NULL,
    `mobile` VARCHAR(20) NOT NULL,
    `role` ENUM('admin', 'client') NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` BIGINT UNSIGNED NOT NULL,
    `updated_at` BIGINT UNSIGNED NOT NULL,
    `last_login_at` BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_mobile` (`mobile`),
    KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mobile` VARCHAR(20) NOT NULL,
    `purpose` VARCHAR(50) NOT NULL,
    `code_hash` VARCHAR(64) NOT NULL,
    `expires_at` BIGINT UNSIGNED NOT NULL,
    `verified_at` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_otp_codes_mobile_purpose` (`mobile`, `purpose`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `selector` VARCHAR(32) NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` BIGINT UNSIGNED NOT NULL,
    `created_at` BIGINT UNSIGNED NOT NULL,
    `last_used_at` BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_remember_tokens_selector` (`selector`),
    KEY `idx_remember_tokens_user` (`user_id`),
    CONSTRAINT `fk_remember_tokens_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `client_profiles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `email` VARCHAR(190) DEFAULT NULL,
    `alternate_contact` VARCHAR(190) DEFAULT NULL,
    `preferred_contact` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `last_activity_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_client_profiles_user` (`user_id`),
    KEY `idx_client_profiles_email` (`email`),
    CONSTRAINT `fk_client_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `website_inquiries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` VARCHAR(40) NOT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `mobile` VARCHAR(20) DEFAULT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'General Inquiry',
    `source` VARCHAR(100) NOT NULL DEFAULT 'Public Website',
    `subject` VARCHAR(190) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'read', 'archived') NOT NULL DEFAULT 'unread',
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_website_inquiries_reference` (`reference`),
    KEY `idx_website_inquiries_user` (`user_id`),
    KEY `idx_website_inquiries_status` (`status`),
    KEY `idx_website_inquiries_submitted` (`submitted_at`),
    CONSTRAINT `fk_website_inquiries_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_packages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_code` VARCHAR(100) NOT NULL,
    `group_key` ENUM('per-head', 'celebration') NOT NULL DEFAULT 'per-head',
    `name` VARCHAR(190) NOT NULL,
    `category_label` VARCHAR(120) NOT NULL,
    `guest_label` VARCHAR(120) NOT NULL,
    `rate_label` VARCHAR(120) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active', 'review', 'inactive') NOT NULL DEFAULT 'review',
    `allow_down_payment` TINYINT(1) NOT NULL DEFAULT 0,
    `down_payment_amount` VARCHAR(100) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_service_packages_code` (`package_code`),
    KEY `idx_service_packages_group_status` (`group_key`, `status`),
    KEY `idx_service_packages_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `package_pricing_tiers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT UNSIGNED NOT NULL,
    `tier_label` VARCHAR(100) NOT NULL,
    `guest_count` INT UNSIGNED DEFAULT NULL,
    `price_label` VARCHAR(100) NOT NULL,
    `price_amount` DECIMAL(12,2) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_package_pricing_tiers_package` (`package_id`),
    KEY `idx_package_pricing_tiers_sort_order` (`sort_order`),
    CONSTRAINT `fk_package_pricing_tiers_package`
        FOREIGN KEY (`package_id`) REFERENCES `service_packages` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `package_inclusions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT UNSIGNED NOT NULL,
    `inclusion_text` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_package_inclusions_package` (`package_id`),
    KEY `idx_package_inclusions_sort_order` (`sort_order`),
    CONSTRAINT `fk_package_inclusions_package`
        FOREIGN KEY (`package_id`) REFERENCES `service_packages` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `package_tags` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` BIGINT UNSIGNED NOT NULL,
    `tag_text` VARCHAR(100) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_package_tags_package` (`package_id`),
    KEY `idx_package_tags_sort_order` (`sort_order`),
    CONSTRAINT `fk_package_tags_package`
        FOREIGN KEY (`package_id`) REFERENCES `service_packages` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `public_site_settings` (
    `id` TINYINT UNSIGNED NOT NULL,
    `hero_image_path` VARCHAR(255) DEFAULT NULL,
    `hero_image_alt` VARCHAR(190) DEFAULT NULL,
    `primary_mobile` VARCHAR(20) DEFAULT NULL,
    `secondary_mobile` VARCHAR(20) DEFAULT NULL,
    `public_email` VARCHAR(190) DEFAULT NULL,
    `inquiry_email` VARCHAR(190) DEFAULT NULL,
    `facebook_url` VARCHAR(255) DEFAULT NULL,
    `messenger_url` VARCHAR(255) DEFAULT NULL,
    `service_area` VARCHAR(255) DEFAULT NULL,
    `business_hours` VARCHAR(190) DEFAULT NULL,
    `business_address` VARCHAR(255) DEFAULT NULL,
    `map_embed_url` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `public_service_cards` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slot_key` VARCHAR(50) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `image_alt` VARCHAR(190) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_public_service_cards_slot` (`slot_key`),
    KEY `idx_public_service_cards_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(150) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `file_name` VARCHAR(255) DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `image_alt` VARCHAR(190) DEFAULT NULL,
    `placement_label` VARCHAR(100) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `status` ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_gallery_items_category_status` (`category`, `status`),
    KEY `idx_gallery_items_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `booking_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` VARCHAR(40) NOT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `inquiry_id` BIGINT UNSIGNED DEFAULT NULL,
    `event_type` VARCHAR(150) NOT NULL,
    `event_date` DATE NOT NULL,
    `event_time` TIME NOT NULL,
    `guest_count` INT UNSIGNED NOT NULL,
    `venue_option` ENUM('own', 'emarioh') NOT NULL DEFAULT 'own',
    `venue_name` VARCHAR(255) NOT NULL,
    `package_category_value` VARCHAR(100) DEFAULT NULL,
    `package_selection_value` VARCHAR(150) DEFAULT NULL,
    `package_label` VARCHAR(190) DEFAULT NULL,
    `package_id` BIGINT UNSIGNED DEFAULT NULL,
    `package_tier_label` VARCHAR(100) DEFAULT NULL,
    `package_tier_price` VARCHAR(100) DEFAULT NULL,
    `primary_contact` VARCHAR(150) NOT NULL,
    `primary_mobile` VARCHAR(20) NOT NULL,
    `primary_email` VARCHAR(190) NOT NULL,
    `alternate_contact` VARCHAR(190) DEFAULT NULL,
    `event_notes` TEXT DEFAULT NULL,
    `admin_notes` TEXT DEFAULT NULL,
    `status` ENUM('pending_review', 'approved', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending_review',
    `booking_source` VARCHAR(50) NOT NULL DEFAULT 'client_portal',
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `rejected_at` DATETIME DEFAULT NULL,
    `cancelled_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `reviewed_by_user_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_booking_requests_reference` (`reference`),
    KEY `idx_booking_requests_user` (`user_id`),
    KEY `idx_booking_requests_inquiry` (`inquiry_id`),
    KEY `idx_booking_requests_package` (`package_id`),
    KEY `idx_booking_requests_status` (`status`),
    KEY `idx_booking_requests_event_date` (`event_date`),
    KEY `idx_booking_requests_reviewer` (`reviewed_by_user_id`),
    CONSTRAINT `fk_booking_requests_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_booking_requests_inquiry`
        FOREIGN KEY (`inquiry_id`) REFERENCES `website_inquiries` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_booking_requests_package`
        FOREIGN KEY (`package_id`) REFERENCES `service_packages` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_booking_requests_reviewer`
        FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `booking_status_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `booking_id` BIGINT UNSIGNED NOT NULL,
    `changed_by_user_id` INT UNSIGNED DEFAULT NULL,
    `from_status` VARCHAR(50) DEFAULT NULL,
    `to_status` VARCHAR(50) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `summary` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking_status_logs_booking` (`booking_id`),
    KEY `idx_booking_status_logs_actor` (`changed_by_user_id`),
    CONSTRAINT `fk_booking_status_logs_booking`
        FOREIGN KEY (`booking_id`) REFERENCES `booking_requests` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_booking_status_logs_actor`
        FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `client_activity_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `activity_type` VARCHAR(100) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `related_reference` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_activity_logs_user` (`user_id`),
    KEY `idx_client_activity_logs_created` (`created_at`),
    CONSTRAINT `fk_client_activity_logs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_settings` (
    `id` TINYINT UNSIGNED NOT NULL,
    `payment_gateway` VARCHAR(100) NOT NULL DEFAULT 'PayMongo',
    `active_method` VARCHAR(50) NOT NULL DEFAULT 'QRPh',
    `accepted_wallets_label` VARCHAR(190) NOT NULL DEFAULT 'Any QRPh-supported e-wallet or banking app',
    `allow_full_payment` TINYINT(1) NOT NULL DEFAULT 1,
    `balance_due_rule` VARCHAR(100) NOT NULL DEFAULT '3 days before event',
    `receipt_requirement` ENUM('receipt_required', 'any_proof') NOT NULL DEFAULT 'receipt_required',
    `confirmation_rule` ENUM('verified_down_payment', 'manual_review') NOT NULL DEFAULT 'verified_down_payment',
    `support_mobile` VARCHAR(20) DEFAULT NULL,
    `instruction_text` TEXT DEFAULT NULL,
    `updated_by_user_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payment_settings_updated_by` (`updated_by_user_id`),
    CONSTRAINT `fk_payment_settings_updated_by`
        FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_invoices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL,
    `booking_id` BIGINT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `invoice_type` ENUM('down_payment', 'progress_payment', 'final_balance', 'full_payment', 'adjustment') NOT NULL DEFAULT 'down_payment',
    `title` VARCHAR(190) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'QRPh',
    `currency_code` CHAR(3) NOT NULL DEFAULT 'PHP',
    `amount_due` DECIMAL(12,2) NOT NULL,
    `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `balance_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `due_date` DATE DEFAULT NULL,
    `status` ENUM('pending', 'review', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `stage_label` VARCHAR(120) DEFAULT NULL,
    `note_text` TEXT DEFAULT NULL,
    `last_payment_at` DATETIME DEFAULT NULL,
    `created_by_user_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_invoices_number` (`invoice_number`),
    KEY `idx_payment_invoices_booking` (`booking_id`),
    KEY `idx_payment_invoices_user` (`user_id`),
    KEY `idx_payment_invoices_status` (`status`),
    KEY `idx_payment_invoices_due_date` (`due_date`),
    KEY `idx_payment_invoices_creator` (`created_by_user_id`),
    CONSTRAINT `fk_payment_invoices_booking`
        FOREIGN KEY (`booking_id`) REFERENCES `booking_requests` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_payment_invoices_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_payment_invoices_creator`
        FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_receipts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `booking_id` BIGINT UNSIGNED NOT NULL,
    `uploaded_by_user_id` INT UNSIGNED DEFAULT NULL,
    `original_file_name` VARCHAR(255) DEFAULT NULL,
    `stored_file_path` VARCHAR(255) DEFAULT NULL,
    `receipt_reference` VARCHAR(100) DEFAULT NULL,
    `sender_name` VARCHAR(150) DEFAULT NULL,
    `sender_mobile` VARCHAR(20) DEFAULT NULL,
    `amount_reported` DECIMAL(12,2) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('uploaded', 'review', 'verified', 'rejected') NOT NULL DEFAULT 'uploaded',
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME DEFAULT NULL,
    `reviewed_by_user_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payment_receipts_invoice` (`invoice_id`),
    KEY `idx_payment_receipts_booking` (`booking_id`),
    KEY `idx_payment_receipts_status` (`status`),
    KEY `idx_payment_receipts_uploaded_by` (`uploaded_by_user_id`),
    KEY `idx_payment_receipts_reviewed_by` (`reviewed_by_user_id`),
    CONSTRAINT `fk_payment_receipts_invoice`
        FOREIGN KEY (`invoice_id`) REFERENCES `payment_invoices` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_payment_receipts_booking`
        FOREIGN KEY (`booking_id`) REFERENCES `booking_requests` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_payment_receipts_uploaded_by`
        FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_payment_receipts_reviewed_by`
        FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `booking_id` BIGINT UNSIGNED NOT NULL,
    `actor_user_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `summary` VARCHAR(255) DEFAULT NULL,
    `meta_label` VARCHAR(255) DEFAULT NULL,
    `amount_label` VARCHAR(100) DEFAULT NULL,
    `status_class` VARCHAR(50) DEFAULT NULL,
    `status_label` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payment_logs_invoice` (`invoice_id`),
    KEY `idx_payment_logs_booking` (`booking_id`),
    KEY `idx_payment_logs_actor` (`actor_user_id`),
    CONSTRAINT `fk_payment_logs_invoice`
        FOREIGN KEY (`invoice_id`) REFERENCES `payment_invoices` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_payment_logs_booking`
        FOREIGN KEY (`booking_id`) REFERENCES `booking_requests` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_payment_logs_actor`
        FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_templates` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_key` VARCHAR(100) NOT NULL,
    `template_name` VARCHAR(150) NOT NULL,
    `trigger_label` VARCHAR(150) NOT NULL,
    `use_case` VARCHAR(190) DEFAULT NULL,
    `template_body` TEXT NOT NULL,
    `placeholders` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sms_templates_key` (`template_key`),
    KEY `idx_sms_templates_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_queue` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` BIGINT UNSIGNED DEFAULT NULL,
    `booking_id` BIGINT UNSIGNED DEFAULT NULL,
    `inquiry_id` BIGINT UNSIGNED DEFAULT NULL,
    `recipient_name` VARCHAR(150) NOT NULL,
    `recipient_mobile` VARCHAR(20) NOT NULL,
    `trigger_label` VARCHAR(150) NOT NULL,
    `message_body` TEXT NOT NULL,
    `source_label` VARCHAR(100) DEFAULT NULL,
    `provider_name` VARCHAR(100) DEFAULT NULL,
    `provider_message_id` VARCHAR(150) DEFAULT NULL,
    `scheduled_at` DATETIME DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `failed_at` DATETIME DEFAULT NULL,
    `status` ENUM('queued', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'queued',
    `failure_reason` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sms_queue_template` (`template_id`),
    KEY `idx_sms_queue_booking` (`booking_id`),
    KEY `idx_sms_queue_inquiry` (`inquiry_id`),
    KEY `idx_sms_queue_status` (`status`),
    KEY `idx_sms_queue_schedule` (`scheduled_at`),
    CONSTRAINT `fk_sms_queue_template`
        FOREIGN KEY (`template_id`) REFERENCES `sms_templates` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_sms_queue_booking`
        FOREIGN KEY (`booking_id`) REFERENCES `booking_requests` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_sms_queue_inquiry`
        FOREIGN KEY (`inquiry_id`) REFERENCES `website_inquiries` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
