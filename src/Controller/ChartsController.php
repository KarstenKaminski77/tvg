<?php

namespace App\Controller;

use App\Entity\ClinicUsers;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\AreaChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\BarChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\CandlestickChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\ColumnChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\ComboChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\GeoChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\Histogram;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\LineChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Options\ComboChart\Series;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChartsController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/clinics/analytics', name: 'get_clinic_charts')]
    public function getChartsAction(): Response
    {
        // Permissions
        $users = $this->em->getRepository(ClinicUsers::class)->find($this->getUser()->getId());

        $permissions = [];

        foreach($users->getClinicUserPermissions() as $user){

            $permissions[] = $user->getPermission()->getId();
        }

        if(!in_array(7, $permissions)){

            return $this->render('frontend/clinics/dashboard.html.twig', [
                'access_granted' => false,
                'pieChart' => '',
                'histogram' => '',
                'areaChart' => '',
                'barChart' => '',
                'columnChart' => '',
                'lineChart' => '',
                'geoChart' => '',
                'comboChart' => '',
                'candleChart' => '',
            ]);

            return new JsonResponse($response);
        }

        $pieChart = new PieChart();
        $pieChart->getData()->setArrayToDataTable(
            [['Task', 'Hours per Day'],
                ['Work',11],
                ['Eat',2],
                ['Commute',2],
                ['Watch TV',2],
                ['Sleep',7],
            ]
        );
        $pieChart->getOptions()->setTitle('Pie Chart');
        $pieChart->getOptions()->getTitleTextStyle()->setBold(true);
        $pieChart->getOptions()->getTitleTextStyle()->setColor('#54565a');
        $pieChart->getOptions()->getTitleTextStyle()->setItalic(true);
        $pieChart->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $pieChart->getOptions()->getTitleTextStyle()->setFontSize(20);
        $pieChart->getOptions()->setColors(['#54565a','#90969b','#aab0b5','#c5ccd2','#e3e9ef']);
        $pieChart->getOptions()->setIs3D(true);

        $histogram = new Histogram();
        $histogram->getData()->setArrayToDataTable([
            ['Population'],
            [12000000],
            [13000000],
            [100000000],
            [1000000000],
            [150000000],
            [175000000],
            [25000000],
            [22000000],
            [600000],
            [6000000],
            [65000000],
            [210000000],
            [240000000],
            [80000000],
            [300000000]
        ]);
        $histogram->getOptions()->setTitle('Histogram');
        $histogram->getOptions()->getTitleTextStyle()->setBold(true);
        $histogram->getOptions()->getTitleTextStyle()->setColor('#54565a');
        $histogram->getOptions()->getTitleTextStyle()->setItalic(true);
        $histogram->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $histogram->getOptions()->getTitleTextStyle()->setFontSize(20);
        $histogram->getOptions()->getLegend()->setPosition('none');
        $histogram->getOptions()->setColors(['#e7711c']);
        $histogram->getOptions()->getHistogram()->setLastBucketPercentile(10);
        $histogram->getOptions()->getHistogram()->setBucketSize(100000000);
        $histogram->getOptions()->setColors(['#54565a','#90969b','#aab0b5']);

        $area = new AreaChart();
        $area->getData()->setArrayToDataTable([
            ['Year', 'Sales', 'Expenses'],
            ['2013',  1000,      400],
            ['2014',  1170,      460],
            ['2015',  660,       1120],
            ['2016',  1030,      540]
        ]);
        $area->getOptions()->setTitle('Area Chart');
        $area->getOptions()->getTitleTextStyle()->setBold(true);
        $area->getOptions()->getTitleTextStyle()->setColor('#54565a');
        $area->getOptions()->getTitleTextStyle()->setItalic(true);
        $area->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $area->getOptions()->getTitleTextStyle()->setFontSize(20);
        $area->getOptions()->getHAxis()->setTitle('Year');
        $area->getOptions()->getHAxis()->getTitleTextStyle()->setColor('#333');
        $area->getOptions()->getVAxis()->setMinValue(0);
        $area->getOptions()->setColors(['#90969b','#aab0b5']);

        $bar = new BarChart();
        $bar->getData()->setArrayToDataTable([
            ['City', '2010 Population', '2000 Population'],
            ['New York City, NY', 8175000, 8008000],
            ['Los Angeles, CA', 3792000, 3694000],
            ['Chicago, IL', 2695000, 2896000],
            ['Houston, TX', 2099000, 1953000],
            ['Philadelphia, PA', 1526000, 1517000]
        ]);
        $bar->getOptions()->setTitle('Bar Chart');
        $bar->getOptions()->getTitleTextStyle()->setBold(true);
        $bar->getOptions()->getTitleTextStyle()->setColor('#54565a');
        $bar->getOptions()->getTitleTextStyle()->setItalic(true);
        $bar->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $bar->getOptions()->getTitleTextStyle()->setFontSize(20);
        $bar->getOptions()->getHAxis()->setTitle('Population of Largest U.S. Cities');
        $bar->getOptions()->getHAxis()->setMinValue(0);
        $bar->getOptions()->getVAxis()->setTitle('City');
        $bar->getOptions()->setColors(['#54565a','#aab0b5']);

        $col = new ColumnChart();
        $col->getData()->setArrayToDataTable(
            [
                ['Time of Day', 'Motivation Level', ['role' => 'annotation'], 'Energy Level', ['role' => 'annotation']],
                [['v' => [8, 0, 0], 'f' => '8 am'],  1, '1', 0.25, '0.2'],
                [['v' => [9, 0, 0], 'f' => '9 am'],  2, '2',  0.5, '0.5'],
                [['v' => [10, 0, 0], 'f' => '10 am'], 3, '3',    1,  '1'],
                [['v' => [11, 0, 0], 'f' => '11 am'], 4, '4', 2.25,  '2'],
                [['v' => [12, 0, 0], 'f' => '12 am'], 5, '5', 2.25,  '2'],
                [['v' => [13, 0, 0], 'f' => '1 pm'],  6, '6',    3,  '3'],
                [['v' => [14, 0, 0], 'f' => '2 pm'],  7, '7', 3.25,  '3'],
                [['v' => [15, 0, 0], 'f' => '3 pm'],  8, '8',    5,  '5'],
                [['v' => [16, 0, 0], 'f' => '4 pm'],  9, '9',  6.5,  '6'],
                [['v' => [17, 0, 0], 'f' => '5 pm'], 10, '10',  10, '10']
            ]
        );
        $col->getOptions()->setTitle('Column Chart');
        $col->getOptions()->getTitleTextStyle()->setBold(true);
        $col->getOptions()->getTitleTextStyle()->setColor('#54565a');
        $col->getOptions()->getTitleTextStyle()->setItalic(true);
        $col->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $col->getOptions()->getTitleTextStyle()->setFontSize(20);
        $col->getOptions()->getAnnotations()->setAlwaysOutside(true);
        $col->getOptions()->getAnnotations()->getTextStyle()->setFontSize(14);
        $col->getOptions()->getAnnotations()->getTextStyle()->setColor('#000');
        $col->getOptions()->getAnnotations()->getTextStyle()->setAuraColor('none');
        $col->getOptions()->getHAxis()->setTitle('Time of Day');
        $col->getOptions()->getHAxis()->setFormat('h:mm a');
        $col->getOptions()->getHAxis()->getViewWindow()->setMin([7, 30, 0]);
        $col->getOptions()->getHAxis()->getViewWindow()->setMax([17, 30, 0]);
        $col->getOptions()->getVAxis()->setTitle('Rating (scale of 1-10)');
        $col->getOptions()->setColors(['#54565a','#aab0b5']);

        $line = new LineChart();
        $line->getData()->setArrayToDataTable(
            [
                [['label' => 'x', 'type' => 'number'], ['label' => 'values', 'type' => 'number'],
                    ['id' =>'i0', 'type' => 'number', 'role' =>'interval'],
                    ['id' =>'i1', 'type' => 'number', 'role' =>'interval'],
                    ['id' =>'i2', 'type' => 'number', 'role' =>'interval'],
                    ['id' =>'i2', 'type' => 'number', 'role' =>'interval'],
                    ['id' =>'i2', 'type' => 'number', 'role' =>'interval'],
                    ['id' =>'i2', 'type' => 'number', 'role' =>'interval']],
                [1, 100, 90, 110, 85, 96, 104, 120],
                [2, 120, 95, 130, 90, 113, 124, 140],
                [3, 130, 105, 140, 100, 117, 133, 139],
                [4, 90, 85, 95, 85, 88, 92, 95],
                [5, 70, 74, 63, 67, 69, 70, 72],
                [6, 30, 39, 22, 21, 28, 34, 40],
                [7, 80, 77, 83, 70, 77, 85, 90],
                [8, 100, 90, 110, 85, 95, 102, 110]
            ]
        );
        $line->getOptions()->setTitle('Line Chart');
        $line->getOptions()->getTitleTextStyle()->setBold(true);
        $line->getOptions()->getTitleTextStyle()->setColor('#54565a');
        $line->getOptions()->getTitleTextStyle()->setItalic(true);
        $line->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $line->getOptions()->getTitleTextStyle()->setFontSize(20);
        $line->getOptions()->setCurveType('function');
        $line->getOptions()->setLineWidth(4);
        $line->getOptions()->getLegend()->setPosition('none');
        $line->getOptions()->setColors(['#54565a']);

        $geo = new GeoChart();
        $geo->getData()->setArrayToDataTable(
            [
                ['City',   'Popularity'],
                ['Country', 'Popularity'],
                ['Germany', 200],
                ['United States', 300],
                ['Brazil', 400],
                ['Canada', 500],
                ['France', 600],
                ['RU', 700],
                ['AE', 950],
                ['QA', 500],
                ['SAU', 600],
                ['ZA', 400],
                ['AU', 500]
            ]
        );
        $geo->getOptions()->setDisplayMode('region');
        $geo->getOptions()->getColorAxis()->setColors(['green', 'blue']);
        $geo->getOptions()->setDefaultColor('#54565a');

        $combo = new ComboChart();
        $combo->getData()->setArrayToDataTable([
            ['Month', 'Bolivia', 'Ecuador', 'Madagascar', 'Papua New Guinea', 'Rwanda', 'Average'],
            ['2004/05',  165,      938,         522,             998,           450,      614.6],
            ['2005/06',  135,      1120,        599,             1268,          288,      682],
            ['2006/07',  157,      1167,        587,             807,           397,      623],
            ['2007/08',  139,      1110,        615,             968,           215,      609.4],
            ['2008/09',  136,      691,         629,             1026,          366,      569.6]
        ]);
        $combo->getOptions()->setTitle('Combo Chart');
        $combo->getOptions()->getTitleTextStyle()->setBold(true);
        $combo->getOptions()->getTitleTextStyle()->setColor('#54565a');
        $combo->getOptions()->getTitleTextStyle()->setItalic(true);
        $combo->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $combo->getOptions()->getTitleTextStyle()->setFontSize(20);
        $combo->getOptions()->getVAxis()->setTitle('Cups');
        $combo->getOptions()->getHAxis()->setTitle('Month');
        $combo->getOptions()->setSeriesType('bars');
        $combo->getOptions()->setColors(['#54565a','#90969b','#aab0b5','#c5ccd2','#e3e9ef']);

        $series5 = new Series();
        $series5->setType('line');
        $combo->getOptions()->setSeries([5 => $series5]);

        $candle = new CandlestickChart();
        $candle->getData()->setArrayToDataTable([
            ['Mon', 20, 28, 38, 45],
            ['Tue', 31, 38, 55, 66],
            ['Wed', 50, 55, 77, 80],
            ['Thu', 77, 77, 66, 50],
            ['Fri', 68, 66, 22, 15]
            // Treat the first row as data.
        ], true);
        $candle->getOptions()->getLegend()->setPosition('none');
        $candle->getOptions()->setTitle('Candlestick Chart');
        $candle->getOptions()->getTitleTextStyle()->setBold(true);
        $candle->getOptions()->getTitleTextStyle()->setColor('#54565a');
        $candle->getOptions()->getTitleTextStyle()->setItalic(true);
        $candle->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $candle->getOptions()->getTitleTextStyle()->setFontSize(20);
        $candle->getOptions()->getBar()->setGroupWidth('100%');
        $candle->getOptions()->getCandlestick()->getFallingColor()->setStroke('#90969b');
        $candle->getOptions()->getCandlestick()->getFallingColor()->setStrokeWidth(2);
        $candle->getOptions()->setColors(['#54565a']);
        $candle->getOptions()->getCandlestick()->getRisingColor()->setStrokeWidth(0);
        $candle->getOptions()->getCandlestick()->getRisingColor()->setFill('#54565a');
        $candle->getOptions()->setTheme('Gray');

        return $this->render('frontend/clinics/dashboard.html.twig', [
            'access_granted' => true,
            'pieChart' => $pieChart,
            'histogram' => $histogram,
            'areaChart' => $area,
            'barChart' => $bar,
            'columnChart' => $col,
            'lineChart' => $line,
            'geoChart' => $geo,
            'comboChart' => $combo,
            'candleChart' => $candle,
        ]);
    }
}
