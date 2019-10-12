<?php
/*=============================================================================
|      Editors:  Martyn Fitzgerald - 16025948
|
|  Module Code:  UFCFV4-30-2
| Module Title:  Data, Schemas & Applications
|                
|   Instructor:  Prakash Chatterjee / Glyn Watkins
|     Due Date:  14/03/2019
|
|  Description:  Creating a JSON object with the data inside our MYSQL Database
|
*===========================================================================*/

//This array holds the predefined rates for the application. 
$currenciesISOCodes = ["AUD","BRL","CAD","CHF","CNY","DKK","EUR","GBP","HKD","HUF","INR","JPY","MXN","MYR","NOK","NZD","PHP","RUB","SEK","SGD","THB","TRY","USD","ZAR"];

// API key for the currencies, allows me to get live information.
$currenciesAPIKey = "313f82e98f94595c11df26da43b9835f";

//Getting current currencies information from the API
$currentCurrencies = json_decode(file_get_contents('http://data.fixer.io/api/latest?access_key='. $currenciesAPIKey),true);

//Getting contries information from the file stored locally.
$countries = simplexml_load_file('./data/countries.xml') or die("Error: Cannot create object");

//Setting the rates of all currency rates to the a varible.
$currencies = $currentCurrencies["rates"];

//This loop cycles through the predefined rates.
for ($i = 0; $i < sizeof($currenciesISOCodes); $i++)
{

    $timeStamp =  time();
    $currencyRate = $currencies[$currenciesISOCodes[$i]] / $currencies["GBP"];
    $currencyCode = $currenciesISOCodes[$i];
    $currencyName = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currenciesISOCodes[$i] . "']/CcyNm");
    $currencyLocations = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currenciesISOCodes[$i] . "']/CtryNm");
    $currencyLocationsFormatted = ucwords(strtolower(implode(", ",$currencyLocations)));

    echo $timeStamp ." - " . $currencyRate . " - " . $currencyCode . " - " . $currencyName[0] . " - " . $currencyLocationsFormatted . "</br>";  
}
?>