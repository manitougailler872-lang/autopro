<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260202000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vehicle enrichment fields: power, color, doors, transmission, rating, category.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE vehicles
            ADD power INT DEFAULT NULL,
            ADD color VARCHAR(40) DEFAULT NULL,
            ADD doors INT DEFAULT NULL,
            ADD transmission VARCHAR(30) DEFAULT NULL,
            ADD rating INT DEFAULT NULL,
            ADD category VARCHAR(30) DEFAULT NULL");
        $this->addSql("CREATE INDEX idx_vehicle_brand ON vehicles (brand)");
        $this->addSql("CREATE INDEX idx_vehicle_year  ON vehicles (year)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX idx_vehicle_brand ON vehicles");
        $this->addSql("DROP INDEX idx_vehicle_year  ON vehicles");
        $this->addSql("ALTER TABLE vehicles
            DROP power, DROP color, DROP doors,
            DROP transmission, DROP rating, DROP category");
    }
}
