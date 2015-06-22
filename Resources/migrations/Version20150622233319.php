<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a scope to report
 */
class Version20150622233319 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 
                'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE report ADD scope_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE report DROP scope'); //before this migration, scope was never used
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_report_scope '
                . 'FOREIGN KEY (scope_id) '
                . 'REFERENCES scopes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_report_scope ON report (scope_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 
                'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE Report DROP CONSTRAINT FK_C38372B2682B5931');
        $this->addSql('DROP INDEX IDX_C38372B2682B5931');
        $this->addSql('ALTER TABLE Report ADD scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE Report DROP scope_id');
    }
}
