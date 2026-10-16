<?php
namespace DoctrineMigrationsMySQL;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261016000100 extends AbstractMigration
{
    public function getDescription(): string { return 'Roles de administrador y árbitro; conserva las cuentas existentes'; }
    public function isTransactional(): bool { return false; }
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE administrador ADD rol VARCHAR(20) DEFAULT 'ROLE_ADMIN' NOT NULL");
    }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE administrador DROP rol'); }
}
