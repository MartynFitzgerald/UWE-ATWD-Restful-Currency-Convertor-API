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
//Config used to hold predefined values
require_once('config.php');
//Functions used throught this service is located.
require_once('functions.php');
//Checking to see if the get methods are set
$countryFrom = isset($_REQUEST["from"]) ? strtoupper($_REQUEST["from"]) : null;
$countryTo = isset($_REQUEST["to"]) ? strtoupper($_REQUEST["to"]) : null;
$amount = isset($_REQUEST["amnt"]) ? $_REQUEST["amnt"] : null;
$format = isset($_REQUEST["format"]) ? strtolower($_REQUEST["format"]) : null;
//If the values are null then output error message.
if (!$countryFrom || !$countryTo || !$amount) {
    // maybe make a function "productParametersMissingError"
    outputErrorMessageResponse(1000);
}
//Check format is XML, JSON or null
checkFormatIsXmlOrJson($format);
//This should check to see if the value is a decimal and not a float
checkAmountIsFloat($amount);
//Check the parameters are the ones that are expected
checkParametersAreRecognized(PRE_DEFINED_GET_PARAMETERS_CONVERSION);
//Setting value outside the ifstatement to allow us to access rates below the if statement.
$rates = null;
//Check if file doesn't exists and then create file or read that file.
if (!file_exists(RATES_PATH_DIRECTORY)) {
    //Create rates file
    initializeDataFromAPI();
    //Request the rates information form file
    $rates = getRatesFromDataFile();
} else {
    //Request the rates information form file
    $rates = getRatesFromDataFile();
    //Getting the currency name from the XML data file
    $timeStamp = $rates->xpath("/currencies/@ts")[0];
    //Formatting the timestamp to the date format
    $formatTimeStamp = date('d F Y H:i',  substr($timeStamp, 0, 10));
    //Checking to if the current time is above 2 hours from the time stamp stored.
    if ($timeStamp <= strtotime("-2 hours")) {
        //Request data from APIs and create rates.xml file.
        updateDataFromAPI($rates, $timeStamp);
    }
}
//Checking currency code to the rates.xml file.
checkCurrencyCode($rates, $countryFrom);
checkCurrencyCode($rates, $countryTo);
//Request the countries information form file.
$countries = getCountriesFromDataFile();
//Produce conversion message to user.
conductConvMessage($countries, $rates, $countryFrom, $countryTo, $amount, $format);
//Display the currency conversion to the user and do the calulation to get value.
function conductConvMessage($countries, $rates, $countryFrom, $countryTo, $amount, $format){
    //Getting the currency rate from the XML data file
    $base = $rates->xpath("/currencies/@base")[0];
    //If conversion is to GBP then get the rate of GBP 
    if($countryTo == $base) {
        //Get the base rate.
        $baseRate = $rates->xpath("/currencies/currency[@code='". $base ."']/@rate")[0];
        //Getting the currency rate from the XML data file
        $rateTo = $rates->xpath("/currencies/currency[@code='". $countryFrom ."']/@rate")[0];
        $rate = (float) ($baseRate / $rateTo);
        //Calculating the conversion.
        $amountCalculation = round($rate * $amount, 2);
    } else {
        //Getting the currency rate from the XML data file
        $rateTo = $rates->xpath("/currencies/currency[@code='". $countryTo ."']/@rate")[0];
        //Calculating the conversion.
        $amountCalculation = round($rateTo * $amount, 2);
        $rate = (float) $rateTo[0];
    }
    //Getting timestamp from document
    $ts = $rates->xpath("/currencies/@ts");
    //Build the PHP array so we can convert it too xml or json.
    $fromArray = array("code"=> $countryFrom, "curr"=> (string) getCountryNameForCurrencyCode($countries, $countryFrom), "loc"=> getCountryLocationForCurrencyCode($countries, $countryFrom), "amnt"=> (float) $amount);
    $toArray = array("code"=> $countryTo, "curr"=> (string) getCountryNameForCurrencyCode($countries, $countryTo), "loc"=> getCountryLocationForCurrencyCode($countries, $countryTo), "amnt"=> $amountCalculation);
    $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "rate"=> $rate, "from"=> $fromArray, "to"=> $toArray);
    $outputNode = array("conv"=>$dataArray);
    //Convert array to format and output
    convertArrayToFormatForOutput($outputNode, $format);
}
?>