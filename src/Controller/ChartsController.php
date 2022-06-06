<?php

namespace App\Controller;

use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChartsController extends AbstractController
{
    #[Route('/clinics/test', name: 'get_clinic_charts')]
    public function getChartsAction(): Response
    {
        $pieChart = new PieChart();
        $pieChart->getData()->setArrayToDataTable(
            [['Task', 'Hours per Day'],
                ['Work',11],
                ['Eat',2],
                ['Commute',2],
                ['Watch TV',2],
                ['Sleep',7],
                ['Playstation',10],
            ]
        );
        $pieChart->getOptions()->setTitle('My Daily Activities');
        $pieChart->getOptions()->setHeight(500);
        $pieChart->getOptions()->setWidth(900);
        $pieChart->getOptions()->getTitleTextStyle()->setBold(true);
        $pieChart->getOptions()->getTitleTextStyle()->setColor('#009900');
        $pieChart->getOptions()->getTitleTextStyle()->setItalic(true);
        $pieChart->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $pieChart->getOptions()->getTitleTextStyle()->setFontSize(20);

        return $this->render('frontend/clinics/dashboard.html.twig', [
            'pieChart' => $pieChart,
        ]);
        //return new JsonResponse($pieChart);
    }
}
