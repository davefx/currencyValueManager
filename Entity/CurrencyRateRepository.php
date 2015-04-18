<?php

namespace Entity;


class CurrencyRateRepository {

    private $DATABASE_SERVER;
    private $DATABASE_NAME;
    private $DATABASE_USER;
    private $DATABASE_PASSWORD;

    /**
     * @var \mysqli|null $connection
     */
    private $connection = null;

    public function __construct($config_file)
    {
        $config = parse_ini_file($config_file);

        if (! $config ||
            ! isset ($config["DATABASE_SERVER"]) ||
            ! isset ($config["DATABASE_NAME"]) ||
            ! isset ($config["DATABASE_USER"]) ||
            ! isset ($config["DATABASE_PASSWORD"] )) {
            throw new \Exception ("Invalid configuration file");
        }

        $this->DATABASE_SERVER = $config["DATABASE_SERVER"];
        $this->DATABASE_NAME = $config["DATABASE_NAME"];
        $this->DATABASE_USER = $config["DATABASE_USER"];
        $this->DATABASE_PASSWORD = $config["DATABASE_PASSWORD"];

        $this->connection = mysqli_connect($this->DATABASE_SERVER,$this->DATABASE_USER,$this->DATABASE_PASSWORD, $this->DATABASE_NAME);
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }

    }

    public function __destruct()
    {
        $this->connection->close();
    }


    /**
     * @param $currency string
     * @param $date \DateTime
     */
    public function findRate($currency, $date)
    {
        $result = null;

        if ($stmt = $this->connection->prepare("SELECT rate FROM currency_rate WHERE currency=? and day=?")) {
            $stmt->bind_param("ss", $currency, $date->format("Y-m-d"));

            $stmt->execute();
            $stmt->bind_result($rate);
            while ($stmt->fetch()) {
                $result = $rate;
            }

            $stmt->close();
        } else {
            throw new \Exception("Error while preparing query:". mysqli_error($this->connection));
        }

        return $result;

    }

    /**
     * @param $currency string
     * @param $date \DateTime
     * @param $rate string
     */
    public function setRate($currency, $date, $rate)
    {
        $result = false;

        if ($stmt = $this->connection->prepare("REPLACE INTO currency_rate (currency, day, rate) VALUES (?,?,?)")) {
            $stmt->bind_param("sss", $currency, $date->format("Y-m-d"), $rate);

            $result = $stmt->execute();

            $stmt->close();
        }

        return $result;

    }



}