<?php
/*=============================================================================
|      Editors:  Martyn Fitzgerald - 16025948
|
|  Module Code:  UFCFX3-15-3
| Module Title:  ADVANCED TOPICS IN WEB DEVELOPMENT 1
|                
|   Instructor:  Prakash Chatterjee
|     Due Date:  21/11/2019
|
|  Description:  This file gets the sets all the predfined arrays and creates
|                the rates file if none exist and gets the infromation to
|                display for the conversion.
|
*===========================================================================*/
include 'functions.php';

//Defining get parameters 
$getPreDefinedParameters = ["from","to","amnt","format"];

//File name for the rates stored.
//$xmlFileName = 'rates.xml';
//Setting default timezone of the service
date_default_timezone_set("Europe/London");

//If no file has been created
/*if (!file_exists('./data/'. $xmlFileName))
{
    //Request data from APIs and create rates.xml file
    initializeDataFromAPI($currenciesISOCodes, $baseCurrency, $xmlFileName);
}
else
{
    //Getting rates information from the file stored locally.
    //$rates = simplexml_load_file('./data/'. $xmlFileName) or die("Error: Cannot create object");
    $rates = getRatesFromDataFile();

    //Getting the currency name from the XML data file
    $ratesTimeStamp = $rates->xpath("/currencies/@ts");
    //Formatting the timestamp to the date format
    $formatTimeStamp = date('d F Y H:i',  substr($ratesTimeStamp[0], 0, 10));

    if($ratesTimeStamp[0] <= strtotime("-2 hours"))
    {
        //Rename XML file to inlcude date
        rename("./data/rates.xml", "./data/rates" . $ratesTimeStamp[0] . ".xml");
        //Request data from APIs and create rates.xml file
        updateDataFromAPI($rates, $xmlFileName);
    }   
}*/

$countryFrom = isset($_REQUEST["from"]) ? $_REQUEST["from"] : null;
$countryTo = isset($_REQUEST["to"]) ? $_REQUEST["to"] : null;
$amount = isset($_REQUEST["amnt"]) ? $_REQUEST["amnt"] : null;
$format = isset($_REQUEST["format"]) ? $_REQUEST["format"] : null;

if (!$countryFrom || !$countryTo || !$amount) {

    // maybe make a function "productParametersMissingError"
    outputErrorMessageResponse(1000);
}

// if $format is blank return a 1100 error code as XML?
if (!$format) {
    outputErrorMessageResponse(1100);
}

// maybe check for parameter not recognised here

$rates = null;

try {
    $rates = getRatesFromDataFile();
} catch (Exception $e) {
    //rates does not exist
    //outputErrorMessageResponse(1500, "Error in service");
}

if ($rates == null) {
    initializeDataFromAPI();
}

try {
    $rates = getRatesFromDataFile();
} catch (Exception $e) {
    //rates does not exist - even after trying to create the file
    outputErrorMessageResponse(1500, "Error in service");
}

//$rates exists
//Getting the currency name from the XML data file
$ratesTimeStamp = $rates->xpath("/currencies/@ts");
//Formatting the timestamp to the date format
$formatTimeStamp = date('d F Y H:i',  substr($ratesTimeStamp[0], 0, 10));

if($ratesTimeStamp[0] <= strtotime("-2 hours"))
{
    //Rename XML file to inlcude date
    //rename("./data/rates.xml", "./data/rates" . $ratesTimeStamp[0] . ".xml");
    archiveRatesFile($ratesTimeStamp[0]);
    //Request data from APIs and create rates.xml file
    updateDataFromAPI($rates);
}


checkCurrencyCode($rates, $countryFrom);
checkCurrencyCode($rates, $countryTo);


//check if from is provided
//if (isset($_REQUEST["from"] == false)) {
    //return 400 status code with message
//}

// This should check to see if the value is a decimal and not a float
checkAmountIsFloat($amount);

checkFormatIsXmlOrJson($format);

$countries = getCountriesFromDataFile();

conductConvMessage($countries, $rates, $countryFrom, $countryTo, $amount, $format);

// check if to is provided

// check if amnt (amount) is provided

// check if format is provided

//Defining the amount on both arrays
/*$amountOfGetKeys = sizeof(array_keys($_REQUEST));
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
    checkAmountIsFloat($amount);
    //Checking if the format request is either xml or json
    checkFormatGetValue();

    conductConvMessage($countryFrom, $countryTo, $amount, $format);
}
else
{
    //Output error 1200 - Currency type not recognized 
    outputErrorMessageResponse(1200);  
}*/

//Display the currency conversion to the user and do the calulation to get value.
function conductConvMessage($countries, $rates, $countryFrom, $countryTo, $amount, $format){
    //Getting the currency rate from the XML data file
    $rateTo = $rates->xpath("/currencies/currency[@code='". $countryTo ."']/@rate");
    $ts = $rates->xpath("/currencies/@ts");

    $amountCalculation = round($rateTo[0] * $amount, 2);
    
    $fromArray = array("code"=> $countryFrom, "curr"=> (string) getCountryNameForCurrencyCode($countries, $countryFrom), "loc"=> getCountryLocationForCurrencyCode($countries, $countryFrom), "amnt"=> (float) $amount);
    $toArray = array("code"=> $countryTo, "curr"=> (string) getCountryNameForCurrencyCode($countries, $countryTo), "loc"=> getCountryLocationForCurrencyCode($countries, $countryTo), "amnt"=> $amountCalculation);
    $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "rate"=> (float) $rateTo[0], "from"=> $fromArray, "to"=> $toArray);

    //$outputNode = constructOutputArray($dataArray, "conv");
    $outputNode = array("conv"=>$dataArray);

    convertArrayToFormatForOutput($format, $outputNode);
}

?>