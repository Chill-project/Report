<?php

namespace Chill\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('ChillReportBundle:Default:index.html.twig', array('name' => $name));
    }
}
