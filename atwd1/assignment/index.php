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
//Defining get parameters 
$getPreDefinedParameters = ["from","to","amnt","format"];
//Base currency for the rates stored.
$baseCurrency = "GBP";
//File name for the rates stored.
$xmlFileName = 'rates.xml';
// API key for the currencies, allows me to get live information.
$currenciesAPIKey = "313f82e98f94595c11df26da43b9835f";
//Setting default timezone of the service
date_default_timezone_set("Europe/London");

//If no file has been created
if (!file_exists('./data/'. $xmlFileName))
{
    //Request data from APIs and create rates.xml file
    requestDataFromAPI($currenciesISOCodes, $baseCurrency, $xmlFileName, $currenciesAPIKey);
}
else
{
    //Getting rates information from the file stored locally.
    $rates = simplexml_load_file('./data/'. $xmlFileName) or die("Error: Cannot create object");
    //Getting the currency name from the XML data file
    $ratesTimeStamp = $rates->xpath("/currencies/@ts");
    //Formatting the timestamp to the date format
    $formatTimeStamp = date('d F Y H:i',  substr($ratesTimeStamp[0], 0, 10));

    if($ratesTimeStamp <= strtotime("-2 hours"))
    {
        //Update here 
        echo "Not within 2 Hours - " . $formatTimeStamp . "</br>";
        //Rename XML file to inlcude date
        rename("./data/rates.xml", "./data/rates" . $ratesTimeStamp[0] . ".xml");
         //Request data from APIs and create rates.xml file
        requestDataFromAPI($currenciesISOCodes, $baseCurrency, $xmlFileName, $currenciesAPIKey);
    }   
}
//Defining the amount on both arrays
$amountOfGetKeys = sizeof(array_keys($_REQUEST));
$amountOfGetParameters = sizeof($getPreDefinedParameters);
//checks that all of the parameters given through the HTTP request
checkRequestKeys($amountOfGetKeys, $amountOfGetParameters, $getPreDefinedParameters);

if ((isset($_REQUEST["from"])) && (isset($_REQUEST["to"]))  && (isset($_REQUEST["amnt"]))  && (isset($_REQUEST["format"])))
{
    $countryFrom = $_REQUEST["from"];
    $countryTo = $_REQUEST["to"];
    $amount = $_REQUEST["amnt"];
    $format = $_REQUEST["format"];

    //Checking if the to and from values are a currency type recognized
    checkCurrencyCode($countryFrom);
    checkCurrencyCode($countryTo);
    //Checking if the amnt value is a decimal point
    checkAmountIsFloat();
    //Checking if the format request is either xml or json
    checkFormatGetValue();

    //echo $countryFrom ." - " . $countryTo . " - " . $amount . " - " . $format . "</br>";  
    conductConvMessage($countryFrom, $countryTo, $amount, $format);
}
else
{
    //Output error 1200 - Currency type not recognized 
    conductErrorMessage(1200);  
    echo "Required parameter is missing </br>";  
    //Terminate the current script 
    exit();
} 
?>