<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141129012050 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("CREATE SEQUENCE Report_id_seq INCREMENT BY 1 MINVALUE 1 START 1;");
        $this->addSql("CREATE TABLE Report (id INT NOT NULL, user_id INT DEFAULT NULL, person_id INT DEFAULT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, scope VARCHAR(255) DEFAULT NULL, cFData JSON NOT NULL, cFGroup_id INT DEFAULT NULL, PRIMARY KEY(id));");
        $this->addSql("CREATE INDEX IDX_C38372B2A76ED395 ON Report (user_id);");
        $this->addSql("CREATE INDEX IDX_C38372B2217BBB47 ON Report (person_id);");
        $this->addSql("CREATE INDEX IDX_C38372B216D2C9F0 ON Report (cFGroup_id);");
        $this->addSql("ALTER TABLE Report ADD CONSTRAINT FK_C38372B2A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE;");
        $this->addSql("ALTER TABLE Report ADD CONSTRAINT FK_C38372B2217BBB47 FOREIGN KEY (person_id) REFERENCES Person (id) NOT DEFERRABLE INITIALLY IMMEDIATE;");
        $this->addSql("ALTER TABLE Report ADD CONSTRAINT FK_C38372B216D2C9F0 FOREIGN KEY (cFGroup_id) REFERENCES CustomFieldsGroup (id) NOT DEFERRABLE INITIALLY IMMEDIATE;");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
