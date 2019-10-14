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
include 'functions.php';

//This array holds the predefined rates for the application. 
$currenciesISOCodes = ["AUD","BRL","CAD","CHF","CNY","DKK","EUR","GBP","HKD","HUF","INR","JPY","MXN","MYR","NOK","NZD","PHP","RUB","SEK","SGD","THB","TRY","USD","ZAR"];

$baseCurrency = "GBP";

$xmlFileName = 'rates.xml';

// API key for the currencies, allows me to get live information.
$currenciesAPIKey = "313f82e98f94595c11df26da43b9835f";

date_default_timezone_set("Europe/London");

//If no file has been created
if (!file_exists('./data/'. $xmlFileName))
{
    //Getting current currencies information from the API
    $currentCurrencies = json_decode(file_get_contents('http://data.fixer.io/api/latest?access_key='. $currenciesAPIKey),true);

    //Getting contries information from the file stored locally.
    $countries = simplexml_load_file('./data/countries.xml') or die("Error: Cannot create object");

    //Setting the rates of all currency rates to the a varible.
    $currencies = $currentCurrencies["rates"];

    initializeRatesXML($currenciesISOCodes, $baseCurrency, $xmlFileName, $countries, $currencies);
}
else
{
    //Getting rates information from the file stored locally.
    $rates = simplexml_load_file('./data/'. $xmlFileName) or die("Error: Cannot create object");
    //Getting the currency name from the XML data file
    $ratesTimeStamp = $rates->xpath("/currencies/currency/at");
    $formatTimeStamp = date('d F Y H:i',  substr($ratesTimeStamp[0], 0, 10));

    if($ratesTimeStamp <= strtotime("-2 hours"))
    {
        //Update here 
        echo "Not within 2 Hours - " . $formatTimeStamp . "</br>";
        //Rename XML file to inlcude date
        rename("./data/rates.xml", "./data/rates" . $ratesTimeStamp[0] . ".xml");
        
        //Getting current currencies information from the API
        $currentCurrencies = json_decode(file_get_contents('http://data.fixer.io/api/latest?access_key='. $currenciesAPIKey),true);

        //Getting contries information from the file stored locally.
        $countries = simplexml_load_file('./data/countries.xml') or die("Error: Cannot create object");
        
        initializeRatesXML($currenciesISOCodes, $baseCurrency, $xmlFileName, $countries, $currencies);
    }   
}


if ((isset ($_REQUEST["from"])) && (isset ($_REQUEST["to"]))  && (!isset ($_REQUEST["amnt"]))  && (!isset ($_REQUEST["format"])))
{
    $countryFrom = $_REQUEST["from"];
    $countryTo = $_REQUEST["to"];
    $amount = $_REQUEST["amnt"];
    $format = $_REQUEST["format"];

    echo $countryFrom ." - " . $countryTo . " - " . $amount . " - " . $format . "</br>";  
}
else if ((isset ($_REQUEST["cur"])) && (isset ($_REQUEST["action"])))
{
    $cur = $_REQUEST["cur"];
    $action = $_REQUEST["action"];

    echo $cur ." - " . $action . "</br>";  

    if ($action === "post")
    {

    }
    else if ($action === "put")
    {

    }
    else if ($action === "del")
    {

    }
    else
    {
        //wrong action given...
    }
}
else
{
    echo "Parameter not recognized </br>";  
    
} 

?>