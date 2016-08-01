<?php


class StockSim {
    private $numShares = 0;

    private $journalEntries = [];

    /** @var double */
    private $yearlyInvestment;

    /** @var DataProvider */
    private $dataProvider;

    /** @var int */
    private $startYearMonth;

    /** @var int */
    private $endYearMonth;

    /** @var int[] */
    private $yearlyBuyingMonthNumbers = [];

    private $dividendData;

    private $monthlyAverageStockData;

    private $stockData;

    /** @var DateTime */
    private $currentSimDate;

    private $stockPriceTime = 'open';


    /**
     * StockSim constructor.
     *
     * @param float        $yearlyInvestment
     * @param DataProvider $dataProvider
     * @param int          $startYearMonth
     * @param int          $endYearMonth
     * @param int[]        $yearlyBuyingMonthNumbers
     * @param              $stockPriceTime
     */
    public function __construct($yearlyInvestment, DataProvider $dataProvider, $startYearMonth, $endYearMonth, array $yearlyBuyingMonthNumbers, $stockPriceTime)
    {
        $this->yearlyInvestment = $yearlyInvestment;
        $this->dataProvider = $dataProvider;
        $this->startYearMonth = $startYearMonth;
        $this->endYearMonth = $endYearMonth;
        $this->yearlyBuyingMonthNumbers = $yearlyBuyingMonthNumbers;
        $this->stockPriceTime = $stockPriceTime;

        $this->stockData = $dataProvider->getStockData();
        $this->monthlyAverageStockData = $dataProvider->getMonthlyAverageStockData();
        $this->dividendData = $dataProvider->getDividendData();
    }


    public function runSim()
    {
        $start = $this->startYearMonth . '-01 12:00:00';
        $this->addEntry("starting sim at $start", 'start');
        $this->addEntry("will buy stock in months " . join(',', $this->yearlyBuyingMonthNumbers), 'months');
        $this->addEntry("will contribute {$this->yearlyInvestment} bucks per year", 'yearly-investment');
        $dt = $this->currentSimDate = new DateTime($start);

        while ($dt->format('Y-m') <= $this->endYearMonth) {

            if ($this->hasDividendExDateToday()) {
                $this->handleDividend();
            }

            if ($this->shouldBuyStockToday()) {
                $this->buyStock();
            }

            $dt->modify("+1 day");
        }
        $endDate = $this->currentSimDate->format('Y-m-d');
        $this->addEntry("ending sim at $endDate", 'end');
        $this->addEntry(sprintf("total shares %s\n", $this->numShares), 'end-shares');

        foreach ($this->journalEntries as $entry) {
            printf("%20s %s\n", $entry['type'], $entry['message']);
        }
    }

    private function hasDividendExDateToday()
    {
        return isset($this->dividendData[ $this->getCurrentDateString() ]);
    }

    private function shouldBuyStockToday()
    {
        return $this->currentSimDate->format('d') === '15'
            && in_array($this->currentSimDate->format('m'), $this->yearlyBuyingMonthNumbers)
        ;
    }

    private function buyStock()
    {
        $moneyToSpend = $this->yearlyInvestment / count($this->yearlyBuyingMonthNumbers);
        $date = $this->getCurrentDateString();
        $price = $this->getCurrentMonthAvgStockPrice();
        $numShares = $moneyToSpend / $price;
        $this->numShares += $numShares;
        $this->addEntry(sprintf("Buying %.2f of stock on $date using month's average price of %.2f", $numShares, $price), 'buy-stock');
    }


    private function handleDividend()
    {
        $dateStr = $this->getCurrentDateString();
        $dividendInfo = $this->dividendData[$dateStr];

        $dividendPayout = $this->numShares * $dividendInfo['amount'];
        $this->addEntry(sprintf("Collecting dividend of %.2f on %.2f shares of stock on $dateStr", $dividendPayout, $this->numShares), 'collect-div');

        $dividendPurchaseDate = $this->findSuitableDividendPurchaseDate();
        $price = $this->stockData[$dividendPurchaseDate][$this->stockPriceTime];
        $numShares = $dividendPayout / $price;
        $this->numShares += $numShares;
        $this->addEntry(sprintf("Buying %.2f of stock on $dividendPurchaseDate using that day's price of %.2f", $numShares, $price), 'buy-stock-drip');
    }

    private function findSuitableDividendPurchaseDate()
    {
        $dateStr = $this->getCurrentDateString();
        $dividendInfo = $this->dividendData[$dateStr];

        // We try to use the payment date if we know what it is, but otherwise we use the EX date + 2 weeks.
        if ($dividendInfo['paymentDate']) {
            $date = $dividendInfo['paymentDate'];
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $dividendInfo['exDate'])->modify("+14 days")->format('Y-m-d');
        }

        // Check if we have data for that day(eg, the market was open).
        if (isset($this->stockData[$date])) {
            return $date;
        }

        // Try to find a nearby date.
        return $this->findClosestDateWithStockData($date);
    }

    private function getCurrentMonthAvgStockPrice()
    {
        if (!isset($this->monthlyAverageStockData[ $this->getCurrentDateString(false) ])) {
            throw new Exception("no avg price data for date: " . $this->getCurrentDateString(false));
        }
        return $this->monthlyAverageStockData[ $this->getCurrentDateString(false) ][$this->stockPriceTime];
    }


    private function findClosestDateWithStockData(DateTime $date)
    {
        $maxDate = max(array_keys($this->stockData));
        $dt = clone $date;
        $dateStr = $dt->format('Y-m-d');
        while (!isset($this->stockData[$dateStr]) && $dateStr <= $maxDate) {
            $dt->modify("+1 day");
            $dateStr = $dt->format('Y-m-d');
        }

        if (!isset($this->stockData[$dateStr])) {
            throw new \Exception("couldnt find date with stock data after $date");
        }

        return $dateStr;
    }

    private function addEntry($message, $type='message')
    {
        $this->journalEntries[] = compact('message', 'type');
    }

    private function getCurrentDateString($includeDay = true)
    {
        return $this->currentSimDate->format($includeDay ? 'Y-m-d' : 'Y-m');
    }
}


