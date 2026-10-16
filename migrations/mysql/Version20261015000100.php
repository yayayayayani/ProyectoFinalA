<?php

declare(strict_types=1);

namespace DoctrineMigrationsMySQL;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Esquema inicial del torneo para MySQL: InnoDB, utf8mb4, claves foráneas e índices.';
    }

    public function isTransactional(): bool
    {
        // MySQL confirma automáticamente las instrucciones DDL.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Esta migración requiere MySQL o MariaDB.');
        $this->addSql('CREATE TABLE equipo (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE gol (id INT AUTO_INCREMENT NOT NULL, cantidad INT NOT NULL, jugador_id INT NOT NULL, jornada_id INT NOT NULL, UNIQUE INDEX UNIQ_GOL_JUGADOR_JORNADA (jugador_id, jornada_id), INDEX IDX_14B85EACB8A54D43 (jugador_id), INDEX IDX_14B85EAC26E992D9 (jornada_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE incidencia (id INT AUTO_INCREMENT NOT NULL, descripcion LONGTEXT NOT NULL, tipo_tarjeta VARCHAR(10) NOT NULL, fecha_incidencia DATE NOT NULL, fecha_suspension DATE DEFAULT NULL, jugador_id INT NOT NULL, INDEX IDX_C7C6728CB8A54D43 (jugador_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE jornada (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, fecha DATE NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE jugador (id INT AUTO_INCREMENT NOT NULL, nombres VARCHAR(100) NOT NULL, apellidos VARCHAR(100) NOT NULL, fecha_nacimiento DATE NOT NULL, fotografia VARCHAR(255) DEFAULT NULL, equipo_id INT NOT NULL, INDEX IDX_527D6F1823BFBED (equipo_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE gol ADD CONSTRAINT FK_14B85EACB8A54D43 FOREIGN KEY (jugador_id) REFERENCES jugador (id)');
        $this->addSql('ALTER TABLE gol ADD CONSTRAINT FK_14B85EAC26E992D9 FOREIGN KEY (jornada_id) REFERENCES jornada (id)');
        $this->addSql('ALTER TABLE incidencia ADD CONSTRAINT FK_C7C6728CB8A54D43 FOREIGN KEY (jugador_id) REFERENCES jugador (id)');
        $this->addSql('ALTER TABLE jugador ADD CONSTRAINT FK_527D6F1823BFBED FOREIGN KEY (equipo_id) REFERENCES equipo (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE gol');
        $this->addSql('DROP TABLE incidencia');
        $this->addSql('DROP TABLE jugador');
        $this->addSql('DROP TABLE jornada');
        $this->addSql('DROP TABLE equipo');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
