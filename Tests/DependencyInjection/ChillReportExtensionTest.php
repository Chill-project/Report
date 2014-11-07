<?php

namespace Chill\ReportBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ChillReportExtensionTest extends KernelTestCase
{
    /*
     * Check if class Chill\ReportBundle\Entity\Report is in chill_custom_fields.customizables_entities
     */
    public function testDeclareReportAsCustomizable()
    {
        self::bootKernel(array('environment' => 'test'));
        $customizablesEntities = static::$kernel->getContainer()
                ->getParameter('chill_custom_fields.customizables_entities');

        $reportFounded = false;
        foreach ($customizablesEntities as $customizablesEntity) {
            if($customizablesEntity['class'] === 'Chill\ReportBundle\Entity\Report') {
                $reportFounded = true;
            }
        }

        if(! $reportFounded) {
            throw new Exception("Class Chill\ReportBundle\Entity\Report not found in chill_custom_fields.customizables_entities", 1);
        }
    }
}
