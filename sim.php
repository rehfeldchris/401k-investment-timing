<?php

require_once 'DataProvider.php';
require_once 'StockSim.php';

$dp = new DataProvider('data/spy/stock.csv', 'data/spy/dividend.csv');

$sim = new StockSim(5500, $dp, '2007-01', '2015-12', [1,2,3], 'close');
$sim->runSim();
echo "\n\n\n\n";

$sim = new StockSim(5500, $dp, '2007-01', '2015-12', [4,5,6], 'close');
$sim->runSim();
echo "\n\n\n\n";

$sim = new StockSim(5500, $dp, '2007-03', '2016-06', [7,8,9], 'close');
$sim->runSim();
echo "\n\n\n\n";

$sim = new StockSim(5500, $dp, '2007-06', '2016-05', [10,11,12], 'close');
$sim->runSim();
echo "\n\n\n\n";

$sim = new StockSim(5500, $dp, '2007-01', '2015-12', [1], 'close');
$sim->runSim();
echo "\n\n\n\n";
