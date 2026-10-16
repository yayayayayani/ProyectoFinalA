<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261014033837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade jornadas, goles e incidencias sin modificar equipos ni jugadores existentes.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE gol (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, cantidad INTEGER NOT NULL, jugador_id INTEGER NOT NULL, jornada_id INTEGER NOT NULL, CONSTRAINT FK_14B85EACB8A54D43 FOREIGN KEY (jugador_id) REFERENCES jugador (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_14B85EAC26E992D9 FOREIGN KEY (jornada_id) REFERENCES jornada (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_GOL_JUGADOR_JORNADA ON gol (jugador_id, jornada_id)');
        $this->addSql('CREATE INDEX IDX_14B85EACB8A54D43 ON gol (jugador_id)');
        $this->addSql('CREATE INDEX IDX_14B85EAC26E992D9 ON gol (jornada_id)');
        $this->addSql('CREATE TABLE incidencia (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, descripcion CLOB NOT NULL, tipo_tarjeta VARCHAR(10) NOT NULL, fecha_incidencia DATE NOT NULL, fecha_suspension DATE DEFAULT NULL, jugador_id INTEGER NOT NULL, CONSTRAINT FK_C7C6728CB8A54D43 FOREIGN KEY (jugador_id) REFERENCES jugador (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C7C6728CB8A54D43 ON incidencia (jugador_id)');
        $this->addSql('CREATE TABLE jornada (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, fecha DATE NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE gol');
        $this->addSql('DROP TABLE incidencia');
        $this->addSql('DROP TABLE jornada');
    }
}
