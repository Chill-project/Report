<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Chill\MainBundle\Entity\Scope;


/**
 * Add a scope to report
 */
class Version20150622233319 extends AbstractMigration 
    implements ContainerAwareInterface
{
    /**
     *
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        if ($container === NULL) {
            throw new \RuntimeException('Container is not provided. This migration '
                    . 'need container to set a default center');
        }
        
        $this->container = $container;
    }
    
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 
                'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE report ADD scope_id INT DEFAULT NULL');
        
        //add a default scope
        $scopes = $this->container->get('doctrine.orm.default_entity_manager')
              ->getRepository('ChillMainBundle:Scope')
              ->findAll();
        
        if (count($scopes) > 0) {
            $defaultScopeId = $scopes[0]->getId();
        } else {
            //check if there are data in report table
            $nbReports = $this->container->get('doctrine.orm.default_entity_manager')
                  ->createQuery('SELECT count(r.id) FROM ChillReportBundle:Report r')
                  ->getSingleScalarResult();
            
            if ($nbReports > 0) {
                //create a default scope
                $scope = new Scope();
                //create name according to installed languages
                $locales = $this->container
                      ->getParameter('chill_main.available_languages');
                $names = array();
                foreach($locales as $locale) {
                    $names[$locale] = 'default';
                }
                $scope->setName($names);
                //persist
                $this->container->get('doctrine.orm.default_entity_manager')
                      ->persist($scope);
                $this->container->get('doctrine.orm.default_entity_manager')
                      ->flush();
                //default scope is the newly-created one
                $defaultScopeId = $scope->getId();
            }
        }

        $this->addSql('ALTER TABLE report DROP scope'); //before this migration, scope was never used
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_report_scope '
                . 'FOREIGN KEY (scope_id) '
                . 'REFERENCES scopes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        
        if (isset($defaultScopeId)){
            $this->addSql('UPDATE report SET scope_id = :id', array(
               'id' => $defaultScopeId
            ));
        }
        $this->addSql('ALTER TABLE report ALTER COLUMN scope_id SET NOT NULL');
        $this->addSql('CREATE INDEX IDX_report_scope ON report (scope_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 
                'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE Report DROP CONSTRAINT FK_report_scope');
        $this->addSql('DROP INDEX IDX_report_scope');
        $this->addSql('ALTER TABLE Report ADD scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE Report DROP scope_id');
    }
}
