<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250202130711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(30) NOT NULL, wins_count INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_match (id INT AUTO_INCREMENT NOT NULL, team_a_id INT NOT NULL, team_b_id INT NOT NULL, winner_id INT DEFAULT NULL, INDEX IDX_BB0D551CEA3FA723 (team_a_id), INDEX IDX_BB0D551CF88A08CD (team_b_id), INDEX IDX_BB0D551C5DFCD4B8 (winner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CEA3FA723 FOREIGN KEY (team_a_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CF88A08CD FOREIGN KEY (team_b_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551C5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CEA3FA723');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CF88A08CD');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551C5DFCD4B8');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE tournament_match');
    }
}
