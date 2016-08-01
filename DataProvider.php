<?php

require_once 'CsvReader.php';

class DataProvider
{
    private $stockDataFile;
    private $dividendDataFile;

    public function __construct($stockDataFile, $dividendDataFile)
    {
        $this->stockDataFile = $stockDataFile;
        $this->dividendDataFile = $dividendDataFile;
    }

    public function getDividendData()
    {
        $reader = new CsvReader();
        $iter = $reader->createIterator($this->dividendDataFile);
        $rows = [];
        foreach ($iter as $row) {
            $exDate = DateTime::createFromFormat('m/d/Y', $row['Ex/Eff Date']);
            $paymentDate = $row['Payment Date'] ? DateTime::createFromFormat('m/d/Y', $row['Payment Date']) : null;
            if (!preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $row['Ex/Eff Date'])) {
                throw new \Exception("Bad date: '" . $row['Ex/Eff Date'] . "'");
            }

            $this->assertNumeric($row, 'Cash Amount');
            $dateStr = $exDate->format('Y-m-d');
            $rows[$dateStr] = [
                'exDate' => $exDate->format('Y-m-d'),
                'paymentDate' => $paymentDate ? $paymentDate->format('Y-m-d') : null,
                'amount' => $row['Cash Amount']
            ];
        }
        ksort($rows);
        return $rows;
    }

    public function getStockData()
    {
        $reader = new CsvReader();
        $iter = $reader->createIterator($this->stockDataFile);
        $rows = [];
        foreach ($iter as $row) {
            if (!preg_match('#^\d{4}/\d{1,2}/\d{1,2}$#', $row['date'])) {
                throw new \Exception("Bad date: '" . $row['date'] . "'");
            }
            foreach (['close', 'open', 'high', 'low'] as $field) {
                $this->assertNumeric($row, $field);
            }
            $dt = DateTime::createFromFormat('Y/m/d', $row['date']);
            if (!$dt) {
                echo $row['date'];
            }
            $date = DateTime::createFromFormat('Y/m/d', $row['date'])->format('Y-m-d');
            $rows[$date] = $row;
        }
        ksort($rows);
        return $rows;
    }

    public function getMonthlyAverageStockData()
    {
        $stockData = $this->getStockData();
        $fields = ['close', 'open', 'high', 'low'];
        $rows = [];
        foreach ($stockData as $date => $row) {
            $columnWiseGroups = [];
            foreach ($fields as $field) {
                $columnWiseGroups[$field][] = $row[$field];
            }

            $monthAverages = [];
            foreach ($columnWiseGroups as $field => $values) {
                $monthAverages[$field] = array_sum($values) / count($values);
            }

            $yearMonth = DateTime::createFromFormat('Y-m-d', $date)->format('Y-m');
            $rows[$yearMonth] = $monthAverages;
        }
        ksort($rows);
        return $rows;
    }

    private function assertNumeric($row, $key)
    {
        if (!preg_match('#^\d+(.\d+)?$#', $row[$key])) {
            throw new \Exception("Bad number in field $key: '$row[$key]'");
        }
    }
}