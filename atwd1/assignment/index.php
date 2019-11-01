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
//Setting default timezone of the service
@date_default_timezone_set("Europe/London");
//Checking to see if the get methods are set
$countryFrom = isset($_REQUEST["from"]) ? $_REQUEST["from"] : null;
$countryTo = isset($_REQUEST["to"]) ? $_REQUEST["to"] : null;
$amount = isset($_REQUEST["amnt"]) ? $_REQUEST["amnt"] : null;
$format = isset($_REQUEST["format"]) ? $_REQUEST["format"] : null;
//If not then output error message.
if (!$countryFrom || !$countryTo || !$amount) {
    // maybe make a function "productParametersMissingError"
    outputErrorMessageResponse(1000);
}
// if $format is blank return a 1100 error code as XML?
if (!$format) {
    outputErrorMessageResponse(1100);
}

// This should check to see if the value is a decimal and not a float
checkAmountIsFloat($amount);
//Check format is XML or JSON
checkFormatIsXmlOrJson($format);

$rates = getRatesFromDataFile();

if ($rates == false) {
    initializeDataFromAPI($rates);
}
else
{
    //Getting the currency name from the XML data file
    $ratesTimeStamp = $rates->xpath("/currencies/@ts");
    //Formatting the timestamp to the date format
    $formatTimeStamp = date('d F Y H:i',  substr($ratesTimeStamp[0], 0, 10));

    if($ratesTimeStamp[0] <= strtotime("-2 hours")) {
        //Rename XML file to inlcude date
        archiveRatesFile($ratesTimeStamp[0]);
        //Request data from APIs and create rates.xml file
        updateDataFromAPI($rates);
    }
}

$rates = getRatesFromDataFile();
checkCurrencyCode($rates, $countryFrom);
checkCurrencyCode($rates, $countryTo);

$countries = getCountriesFromDataFile();

conductConvMessage($countries, $rates, $countryFrom, $countryTo, $amount, $format);

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