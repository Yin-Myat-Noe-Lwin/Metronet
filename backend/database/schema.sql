DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `cpe_assignments`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `customer_addresses`;

DROP TABLE IF EXISTS `cpes`;
DROP TABLE IF EXISTS `isp_plans`;
DROP TABLE IF EXISTS `customers`;

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

CREATE TABLE `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `method` TINYINT NOT NULL COMMENT '1=card, 2=bank, 3=cash 4=mock',
    `transaction_ref` VARCHAR(100) NULL,
    `status` TINYINT NOT NULL COMMENT '0=pending 1=success 2=failed',
    `paid_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT fk_payment_customer
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_payment_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
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
    `event_type` TINYINT NOT NULL COMMENT '1=invoice_created, 2=payment_success, 3=subscription_approved, 4=service_activated',
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
