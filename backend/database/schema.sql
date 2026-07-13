DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `failed_jobs`;

DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `cache_locks`;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `cpe_assignments`;
DROP TABLE IF EXISTS `payment_methods`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `customer_addresses`;

DROP TABLE IF EXISTS `cpes`;
DROP TABLE IF EXISTS `isp_plans`;
DROP TABLE IF EXISTS `customers`;

CREATE TABLE `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL,
    `reserved_at` INT UNSIGNED NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL,
    INDEX `jobs_queue_index` (`queue`)
);

CREATE TABLE `job_batches` (
    `id` VARCHAR(255) NOT NULL PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT NULL,
    `cancelled_at` INT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT NULL
);

CREATE TABLE `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(255) NOT NULL UNIQUE,
    `connection` TEXT NOT NULL,
    `queue` TEXT NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `cache` (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` INT NOT NULL,
    INDEX `cache_expiration_index` (`expiration`)
);

CREATE TABLE `cache_locks` (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` INT NOT NULL,
    INDEX `cache_locks_expiration_index` (`expiration`)
);

CREATE TABLE `customers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `phone_num` VARCHAR(30) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `pending_email` VARCHAR(255) NULL,
    `status` TINYINT NOT NULL COMMENT '0=Inactive,1=Active',
    `role` TINYINT NOT NULL DEFAULT 1 COMMENT '1=customer, 2=admin',
    `password` VARCHAR(255) NOT NULL,
    `verification_token` VARCHAR(64) NULL,
    `verification_token_expires_at` timestamp NULL DEFAULT NULL,
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL
);

CREATE TABLE `customer_addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `township` VARCHAR(30) NOT NULL,
    `city` VARCHAR(30) NOT NULL,
    `region` VARCHAR(30) NOT NULL,
    `address_type` TINYINT NOT NULL COMMENT '1=Home, 2=Office, 3=Business',
    `is_primary` TINYINT NOT NULL DEFAULT 0 COMMENT '0=Secondary, 1=Primary',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    CONSTRAINT fk_addresses_customer_id
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE `isp_plans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(100) NOT NULL,
    `price` decimal(10,2) NOT NULL,
    `status` TINYINT NOT NULL COMMENT '0=Inactive,1=Active',
    `upload_speed` INT UNSIGNED NOT NULL,
    `download_speed` INT UNSIGNED NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL
);

CREATE TABLE `service_areas` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `region` VARCHAR(30) NOT NULL,
    `city` VARCHAR(30) NOT NULL,
    `township` VARCHAR(30) NOT NULL,
    `status` TINYINT DEFAULT 1 COMMENT '1 = active, 0 = inactive',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
);

CREATE TABLE `subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` INT UNSIGNED NOT NULL,
    `status` TINYINT NOT NULL COMMENT '0=pending,1=active,2=suspended,3=expired,4=cancelled',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `duration_months` INT NOT NULL DEFAULT 1,
    `auto_renew` TINYINT NOT NULL DEFAULT 0,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    CONSTRAINT fk_subscriptions_customer_id
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_subscriptions_plan_id
    FOREIGN KEY (plan_id) REFERENCES isp_plans(id)
);

CREATE TABLE `invoices` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `subscription_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `due_date` DATE NOT NULL,
    `status` TINYINT NOT NULL COMMENT '0=pending,1=paid,2=overdue,3=cancelled',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT fk_invoice_subscription
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);

CREATE TABLE `payment_methods` (
    `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(255) NULL COMMENT 'Icon file path or URL',
    `icon_type` TINYINT NOT NULL DEFAULT 1 COMMENT '1=emoji, 2=image_url, 3=uploaded_file',
    `description` VARCHAR(255) NULL,
    `payment_details` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=No extra details needed, 1=Requires payment details',
    `fields` JSON NULL COMMENT 'JSON schema for required payment fields',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=Inactive, 1=Active',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
);

CREATE TABLE `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `method` TINYINT UNSIGNED NOT NULL COMMENT 'References payment_methods.id',
    `payment_details` JSON NULL COMMENT 'Stores payment method specific details like card info, bank details, etc.',
    `transaction_ref` VARCHAR(100) NULL,
    `status` TINYINT NOT NULL COMMENT '0=pending 1=success 2=failed',
    `paid_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT fk_payment_customer
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_payment_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    CONSTRAINT `fk_payment_method`
    FOREIGN KEY (`method`) REFERENCES `payment_methods`(`id`) ON DELETE RESTRICT
);

CREATE TABLE `cpes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `serial_number` VARCHAR(100) NOT NULL,
    `mac_address` VARCHAR(100) NOT NULL,
    `status` TINYINT NOT NULL COMMENT '0=Available, 1=Assigned, 2=Faulty, 3=Maintenance, 4=Retired',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL
);

CREATE TABLE `cpe_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `cpe_id` BIGINT UNSIGNED NOT NULL,
    `subscription_id` BIGINT UNSIGNED NOT NULL,
    `assigned_at` timestamp NOT NULL,
    `unassigned_at` timestamp NULL DEFAULT NULL,
    `status` TINYINT NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    CONSTRAINT fk_assignments_cpe_id
    FOREIGN KEY (cpe_id) REFERENCES cpes(id),
    CONSTRAINT fk_assignments_subscription_id
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);

CREATE TABLE `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `event_type` TINYINT NOT NULL COMMENT '1=invoice_created, 2=payment_success, 3=subscription_approved, 4=service_activated, 5=subscription_cancelled',
    `channel` TINYINT NOT NULL DEFAULT 1 COMMENT '1=email, 2=sms, 3=in_app',
    `title` VARCHAR(100) NOT NULL,
    `message` TEXT,
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1=active',
    `is_read` TINYINT DEFAULT 0,
    `read_at` TIMESTAMP NULL,
    `scheduled_at` TIMESTAMP NULL,
    `sent_status` TINYINT DEFAULT 0 COMMENT '0=pending 1=sent 2=failed',
    `sent_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT fk_notifications_customer_id
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

INSERT INTO customers
(name, phone_num, email, status, role, password, created_at, updated_at)
VALUES
('Elizabeth', '09123456789', 'elizabeth@gmail.com', 1, 1, '\$2y\$12\$6gJ8bZyujtrGBc536ZQZiulY1JNBppWPjTZ0gaCoUM2kwCW8HIhfq', NOW(), NOW()),
('Leona Louisa', '0980888088', 'leonalouisa@gmail.com', 1, 0, '\$2y\$12\$XhDgFdTNnU4I2psyI7JTzeWc/tmbykl7eo1Wke/4uU.jmXTfN596K', NOW(), NOW()),
('Emily', '09676767677', 'emily@gmail.com', 1, 0, '\$2y\$12\$2RCBemfIWVKa9a9NT7ECNOGd7OW9VsZiL2mFK7J5eyXhfbRl8ykXK', NOW(), NOW()),
('Zoey', '09000000000', 'zoey@gmail.com', 1, 1, '\$2y\$12\$2RCBemfIWVKa9a9NT7ECNOGd7OW9VsZiL2mFK7J5eyXhfbRl8ykXK', NOW(), NOW());

INSERT INTO isp_plans
(name, description, price, status, upload_speed, download_speed, created_at, updated_at)
VALUES
('Home Starter', 'Basic internet for browsing, messaging and social media. Best for 1–2 users.', 25000.00, 1, 10, 20, NOW(), NOW()),
('Home Basic', 'Stable internet for streaming, online classes and daily home usage.', 35000.00, 1, 15, 30, NOW(), NOW()),
('Home Plus', 'Fast HD streaming, video calls and light gaming for small families.', 55000.00, 1, 25, 50, NOW(), NOW()),
('Fiber Family', 'High-speed fiber for multiple devices, streaming and online learning.', 75000.00, 1, 35, 70, NOW(), NOW()),
('Premium Ultra', 'Ultra-fast internet for 4K streaming, gaming and smart home use.', 95000.00, 1, 50, 100, NOW(), NOW()),
('Business Pro', 'Enterprise-grade stable connection for offices and heavy usage.', 120000.00, 1, 75, 150, NOW(), NOW());

INSERT INTO service_areas
(region, city, township, status, created_at, updated_at)
VALUES
('Yangon', 'Yangon', 'Dala', 1, NOW(), NOW()),
('Yangon', 'Yangon', 'Hlaing', 1, NOW(), NOW()),
('Yangon', 'Yangon', 'Bahan', 1, NOW(), NOW()),
('Yangon', 'Yangon', 'Kamayut', 1, NOW(), NOW()),
('Mandalay', 'Mandalay', 'Chanayethazan', 1, NOW(), NOW()),
('Mandalay', 'Mandalay', 'Aungmyethazan', 1, NOW(), NOW()),
('Mandalay', 'Mandalay', 'Pyigyitagon', 1, NOW(), NOW()),
('Naypyidaw', 'Naypyidaw', 'Zabuthiri', 1, NOW(), NOW()),
('Naypyidaw', 'Naypyidaw', 'Pyinmana', 1, NOW(), NOW()),
('Shan', 'Shan', 'Taunggyi', 1, NOW(), NOW());

INSERT INTO `payment_methods`
(`name`, `icon`, `icon_type`, `description`, `payment_details`, `fields`, `is_active`, `created_at`, `updated_at`)
VALUES
('Credit / Debit Card', '💳', 1, 'Pay with credit or debit card', 1, '{"card_number":"required","expiry":"required","cvv":"required","card_holder":"required"}', 1, NOW(), NOW()),
('Bank Transfer', '🏦', 1, 'Direct bank transfer to our account', 1, '{"bank_name":"required","account_number":"required","account_holder":"required"}', 1, NOW(), NOW()),
('Cash Payment', '💰', 1, 'Pay with cash at our office', 0, NULL, 1, NOW(), NOW()),
('KBZ Pay', '📱', 1, 'Pay with KBZ Pay mobile wallet', 1, '{"phone_number":"required"}', 1, NOW(), NOW()),
('Wave Money', '📱', 1, 'Pay with Wave Money mobile wallet', 1, '{"phone_number":"required"}', 1, NOW(), NOW()),
('CB Pay', '📱', 1, 'Pay with CB Pay mobile wallet', 1, '{"phone_number":"required"}', 1, NOW(), NOW());

INSERT INTO cpes
(serial_number, mac_address, status, created_at, updated_at)
VALUES
('CPE-390941069', '39:51:7A:0E:6F:DC', 0, NOW(), NOW()),
('CPE-211885115', '8F:22:B1:F7:E4:29', 0, NOW(), NOW()),
('CPE-582399202', 'F5:78:DC:D1:74:AB', 0, NOW(), NOW()),
('CPE-530302393', 'AE:15:D7:2E:1D:24', 0, NOW(), NOW()),
('CPE-983293382', 'AC:01:7B:96:A5:82', 0, NOW(), NOW()),
('CPE-674839201', '12:45:AB:7C:9D:EF', 0, NOW(), NOW()),
('CPE-829104756', 'A1:B2:C3:D4:E5:F6', 0, NOW(), NOW()),
('CPE-458920317', '22:34:56:78:9A:BC', 0, NOW(), NOW()),
('CPE-739201845', 'DE:AD:BE:EF:10:20', 0, NOW(), NOW()),
('CPE-581934620', '98:76:54:32:10:FE', 0, NOW(), NOW()),
('CPE-902817364', '11:22:33:44:55:66', 0, NOW(), NOW()),
('CPE-315790482', '77:88:99:AA:BB:CC', 0, NOW(), NOW()),
('CPE-746291830', '01:23:45:67:89:AB', 0, NOW(), NOW()),
('CPE-864209531', 'CD:EF:01:23:45:67', 0, NOW(), NOW()),
('CPE-193847562', '10:20:30:40:50:60', 0, NOW(), NOW()),
('CPE-528374910', '6A:7B:8C:9D:AE:BF', 0, NOW(), NOW()),
('CPE-690125738', 'B1:C2:D3:E4:F5:A6', 0, NOW(), NOW()),
('CPE-417839205', '34:56:78:9A:BC:DE', 0, NOW(), NOW()),
('CPE-783920146', 'FE:DC:BA:98:76:54', 0, NOW(), NOW()),
('CPE-256814907', 'AA:BB:CC:DD:EE:FF', 0, NOW(), NOW()),
('CPE-901273645', '13:57:9B:DF:24:68', 0, NOW(), NOW()),
('CPE-368492750', '24:68:AC:E0:12:46', 0, NOW(), NOW()),
('CPE-574839102', '35:79:BD:F1:13:57', 0, NOW(), NOW()),
('CPE-819263540', '46:8A:CE:02:24:68', 0, NOW(), NOW()),
('CPE-637490281', '57:9B:DF:13:35:79', 0, NOW(), NOW()),
('CPE-482719603', '68:AC:E0:24:46:8A', 0, NOW(), NOW()),
('CPE-795031864', '79:BD:F1:35:57:9B', 0, NOW(), NOW()),
('CPE-104928573', '8A:CE:02:46:68:AC', 0, NOW(), NOW()),
('CPE-236790415', '9B:DF:13:57:79:BD', 0, NOW(), NOW()),
('CPE-348105729', 'BC:E0:24:68:8A:CE', 0, NOW(), NOW());
