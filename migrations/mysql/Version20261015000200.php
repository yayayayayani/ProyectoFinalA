<?php
namespace DoctrineMigrationsMySQL;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261015000200 extends AbstractMigration
{
    public function getDescription(): string { return 'Añade cuentas administradoras sin modificar los registros del torneo'; }
    public function isTransactional(): bool { return false; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE administrador (id INT AUTO_INCREMENT NOT NULL, usuario VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_ADMIN_USUARIO (usuario), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE administrador'); }
}
