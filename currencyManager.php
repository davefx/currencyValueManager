#!/usr/bin/php
<?php

require_once ("Entity/CurrencyRateRepository.php");

use Entity\CurrencyRateRepository;

function simple_get_url($url)
{
    $curl_handle=curl_init();

    curl_setopt($curl_handle,CURLOPT_URL, $url);
    curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,15);
    curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($curl_handle,CURLOPT_FOLLOWLOCATION,1);


    $buffer = curl_exec($curl_handle);

    if ($error = curl_error($curl_handle)) {
        return false;
    }

    curl_close($curl_handle);

    return $buffer;
}

function refresh_rates()
{
    $repository = new CurrencyRateRepository($GLOBALS["CONFIG_FILE"]);
    $date = new \DateTime();

    $xml = simple_get_url ($GLOBALS["CURRENCIES_URL"]);
    if ($xml === false) {
        throw new Exception("Cannot get currency values");
    }

    $currencies_xml = new SimpleXMLElement($xml);
    foreach ($currencies_xml->conversion as $conversion) {
        $repository->setRate ($conversion->currency, $date, $conversion->rate );
    }

    return true;
}

function get_single_rate($input, $date = null)
{
    if (! $date) {
        $date = date_create();
    }
    $repository = new CurrencyRateRepository($GLOBALS["CONFIG_FILE"]);

    list($currency, $amount) = explode(" ", $input);

    $rate = $repository->findRate($currency, $date);

    if ($rate)
        return "USD ".$amount*$rate;
    else
        return null;

}

function get_multiple_rates($input, $date = null)
{
    $result = array();
    foreach ($input as $currencyAmount) {
        $result[] = get_single_rate($currencyAmount, $date);
    }

    return $result;
}

// ================================================================================
// Main program
// ================================================================================

$GLOBALS["CONFIG_FILE"]="database_config.ini";
$GLOBALS["CURRENCIES_URL"]="https://wikitech.wikimedia.org/wiki/Fundraising/tech/Currency_conversion_sample?ctype=text/xml&action=raw";

$shortOptions  = "";
$longOptions   = array();

$longOptions[] = "config-file:";
$longOptions[] = "url:";

$shortOptions .= "h"; // Help
$longOptions[] = "help";

$shortOptions .= "m"; // Multiple
$longOptions[] = "multiple";

$shortOptions .= "r";
$longOptions[] = "refresh";

$options = getopt($shortOptions, $longOptions);

if ($argc==1 || isset ($options["h"]) || isset($options["help"])) {
    echo "Currency rate managing tool. By David Marín\n";
    echo "Syntax: ".$argv[0]." [options] (-r | [-m | --multiple] <<currency amounts>>)\n\n";
    echo "Options:\n";
    echo "\t -h,--help:                         Show this help message\n";
    echo "\t --config-file=<<config-file.ini>>: Sets the configuration file to use\n";
    echo "\t --url=<<URL>>:                     Sets the URL for getting conversion rates (only http supported)\n\n";
    echo "\t -r:            Refresh conversion rates\n";
    echo "\t -m,--multiple: Allows the entering of multiple currency amounts to convert, with format array( 'JPY 5000', 'CZK 62.5' )\"\n";
    echo "\n";
    echo "Examples:\n";
    echo "\t$argv[0] -r\n";
    echo "\t$argv[0] JPY 1234\n";
    echo "\t$argv[0] -m \"array('JPY 1234','AUD 4567')\"\n\n";
    exit();
}

if (isset($options["config-file"])) {
    $GLOBALS["CONFIG_FILE"] = $options["config-file"];
}
if (isset($options["url"])) {
    $GLOBALS["CURRENCIES_URL"] = $options["url"];
}

if (! file_exists($GLOBALS["CONFIG_FILE"])) {
    echo "Error: cannot access config file ",$GLOBALS["CONFIG_FILE"],"\n";
    exit (1);
}

if (isset($options["r"]) || isset($options["refresh"])) {
    echo "Refreshing...";
    $result = refresh_rates();
    if ($result) {
        echo "Currency rates refreshed.\n";
        exit (0);
    } else {
        echo "There was an error while refreshing currency rates.\n";
        exit (1);
    }
}

$arguments = $argv;
array_shift($arguments); // Remove command name

for ($i=0; $i<$argc-1; $i++)
{
    if (substr($arguments[$i], 0, 1) == "-") {
        unset ($arguments[$i]);
    }
}

$arguments = implode(" ", $arguments);

if (! isset($options["m"]) && ! isset($options["multiple"])) {
    echo get_single_rate($arguments)."\n";
} else {
    // Totally insecure!!! Not enough time for security...
    eval('$amount = '.$arguments.";");
    echo "array('".implode(get_multiple_rates($amount),"', '")."')\n";
}









