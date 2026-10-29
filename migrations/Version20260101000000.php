<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial LUXE CAR schema — vehicles, reservations, payment proofs, refunds, reviews, admins, settings, logs.';
    }

    public function up(Schema $schema): void
    {
        // vehicles
        $this->addSql("CREATE TABLE vehicles (
            id INT AUTO_INCREMENT NOT NULL,
            uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            brand VARCHAR(80) NOT NULL,
            model VARCHAR(80) NOT NULL,
            slug VARCHAR(120) NOT NULL,
            version VARCHAR(120) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            year INT NOT NULL,
            mileage INT NOT NULL,
            fuel VARCHAR(20) NOT NULL,
            price NUMERIC(12,2) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'AVAILABLE',
            published TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX UNIQ_vehicles_uuid (uuid),
            UNIQUE INDEX UNIQ_vehicles_slug (slug),
            INDEX idx_vehicle_slug (slug),
            INDEX idx_vehicle_published (published),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");

        // vehicle_images
        $this->addSql("CREATE TABLE vehicle_images (
            id INT AUTO_INCREMENT NOT NULL,
            vehicle_id INT NOT NULL,
            filename VARCHAR(190) NOT NULL,
            original_filename VARCHAR(190) NOT NULL,
            position INT NOT NULL,
            primary_image TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX IDX_vimg_vehicle (vehicle_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");
        $this->addSql("ALTER TABLE vehicle_images ADD CONSTRAINT FK_vimg_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE");

        // reservations
        $this->addSql("CREATE TABLE reservations (
            id INT AUTO_INCREMENT NOT NULL,
            vehicle_id INT NOT NULL,
            uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            reservation_number VARCHAR(30) NOT NULL,
            customer_name VARCHAR(160) NOT NULL,
            customer_email VARCHAR(190) NOT NULL,
            customer_phone VARCHAR(40) NOT NULL,
            reception_mode VARCHAR(20) NOT NULL,
            pickup_first_name VARCHAR(80) DEFAULT NULL,
            pickup_last_name VARCHAR(80) DEFAULT NULL,
            pickup_date DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
            pickup_time VARCHAR(10) DEFAULT NULL,
            delivery_address VARCHAR(200) DEFAULT NULL,
            delivery_city VARCHAR(100) DEFAULT NULL,
            delivery_postal_code VARCHAR(20) DEFAULT NULL,
            vehicle_price_snapshot NUMERIC(12,2) NOT NULL,
            reservation_fee_percentage NUMERIC(5,2) NOT NULL,
            reservation_fee_amount NUMERIC(12,2) NOT NULL,
            payment_method VARCHAR(30) NOT NULL,
            status VARCHAR(40) NOT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            confirmed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX UNIQ_res_uuid (uuid),
            UNIQUE INDEX UNIQ_res_number (reservation_number),
            INDEX idx_res_uuid (uuid),
            INDEX idx_res_number (reservation_number),
            INDEX idx_res_email (customer_email),
            INDEX idx_res_status (status),
            INDEX idx_res_created (created_at),
            INDEX IDX_res_vehicle (vehicle_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");
        $this->addSql("ALTER TABLE reservations ADD CONSTRAINT FK_res_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id)");

        // payment_proofs
        $this->addSql("CREATE TABLE payment_proofs (
            id INT AUTO_INCREMENT NOT NULL,
            reservation_id INT NOT NULL,
            original_filename VARCHAR(190) NOT NULL,
            stored_filename VARCHAR(190) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            uploaded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            reviewed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX IDX_proof_reservation (reservation_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");
        $this->addSql("ALTER TABLE payment_proofs ADD CONSTRAINT FK_proof_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE CASCADE");

        // refund_requests
        $this->addSql("CREATE TABLE refund_requests (
            id INT AUTO_INCREMENT NOT NULL,
            reservation_id INT NOT NULL,
            uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            reason VARCHAR(80) NOT NULL,
            customer_comment LONGTEXT DEFAULT NULL,
            encrypted_iban LONGTEXT NOT NULL,
            iban_last_four VARCHAR(4) NOT NULL,
            status VARCHAR(20) NOT NULL,
            requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            processed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            admin_note LONGTEXT DEFAULT NULL,
            UNIQUE INDEX UNIQ_refund_uuid (uuid),
            INDEX idx_refund_status (status),
            INDEX IDX_refund_reservation (reservation_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");
        $this->addSql("ALTER TABLE refund_requests ADD CONSTRAINT FK_refund_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id)");

        // reviews
        $this->addSql("CREATE TABLE reviews (
            id INT AUTO_INCREMENT NOT NULL,
            customer_name VARCHAR(120) NOT NULL,
            rating INT NOT NULL,
            comment LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            published_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX idx_review_status (status),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");

        // admin_users
        $this->addSql("CREATE TABLE admin_users (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(190) NOT NULL,
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(120) NOT NULL,
            roles JSON NOT NULL,
            active TINYINT(1) NOT NULL,
            last_login_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX UNIQ_admin_email (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");

        // settings
        $this->addSql("CREATE TABLE settings (
            name VARCHAR(80) NOT NULL,
            value LONGTEXT DEFAULT NULL,
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            PRIMARY KEY(name)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");

        // email_logs
        $this->addSql("CREATE TABLE email_logs (
            id INT AUTO_INCREMENT NOT NULL,
            reservation_id INT DEFAULT NULL,
            recipient VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NOT NULL,
            provider_message_id VARCHAR(190) DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            error_message LONGTEXT DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX IDX_email_reservation (reservation_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");
        $this->addSql("ALTER TABLE email_logs ADD CONSTRAINT FK_email_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE SET NULL");

        // admin_activity_logs
        $this->addSql("CREATE TABLE admin_activity_logs (
            id INT AUTO_INCREMENT NOT NULL,
            admin_user_id INT DEFAULT NULL,
            action VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) DEFAULT NULL,
            entity_id INT DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX IDX_log_admin (admin_user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");
        $this->addSql("ALTER TABLE admin_activity_logs ADD CONSTRAINT FK_log_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users (id) ON DELETE SET NULL");
    }

    public function down(Schema $schema): void
    {
        foreach ([
            'ALTER TABLE admin_activity_logs DROP FOREIGN KEY FK_log_admin',
            'ALTER TABLE email_logs DROP FOREIGN KEY FK_email_reservation',
            'ALTER TABLE refund_requests DROP FOREIGN KEY FK_refund_reservation',
            'ALTER TABLE payment_proofs DROP FOREIGN KEY FK_proof_reservation',
            'ALTER TABLE reservations DROP FOREIGN KEY FK_res_vehicle',
            'ALTER TABLE vehicle_images DROP FOREIGN KEY FK_vimg_vehicle',
        ] as $sql) {
            $this->addSql($sql);
        }
        foreach ([
            'admin_activity_logs', 'email_logs', 'settings', 'admin_users',
            'reviews', 'refund_requests', 'payment_proofs', 'reservations',
            'vehicle_images', 'vehicles',
        ] as $t) {
            $this->addSql("DROP TABLE $t");
        }
    }
}
