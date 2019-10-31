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
include '../functions.php';
//Defining get parameters 
$getPreDefinedParameters = ["cur","action"];

$cur = $_REQUEST["cur"];
$action = $_REQUEST["action"];

//Checking if the to and from values are a currency type recognized
checkCurrencyCodeToXML($cur);

if ($action === "post")
{
    conductPostMessage($action, $cur);
}
else if ($action === "put")
{

}
else if ($action === "del")
{
    conductDeleteCurrency($action, $cur);
}
else
{
    //Output error 2000 - Action not recognized or is missing
    conductErrorMessage(2000); 
}

//Display the new currency rate to the user and do the calulation to get value.
function conductPostMessage($action, $cur){
    if ($cur == "GBP")
    {
        //Output error 2400 - Cannot update base currency
        outputErrorMessageResponse(2400); 
    }
    //Getting contries information from the file stored locally.
    $countries = getCountriesFromDataFile();
    //Getting contries information from the file stored locally.
    $rates = getRatesFromDataFile();
    //Update currency rate in the rates file.
    $currencyRate = getNewCurrencyRate($cur);
    //Get timestamp from rates file.
    $ts = $rates->xpath("/currencies/@ts");
    //Getting the currency rate from the XML data file
    $rate = $rates->xpath("/currencies/currency[@code='". $cur ."']/@rate");
    //Set old rate to varible to store.
    $oldRate = (float) $rate[0]->rate;
    //Make node attrube @rate equal to the new rate for that currency
    $rate[0]->rate = $currencyRate; 
    //If the values of the rates are not the same then show difference otherwise show error.
    if (!(float) $rate[0]->rate == $oldRate) {
        //Save the values in the rates.xml
        file_put_contents('../data/rates.xml', $rates->saveXML());
        //Contruct arrays with the data above
        $curArray = array("code"=> $cur, "name"=> (string) getCountryNameForCurrencyCode($countries, $cur), "loc"=> getCountryLocationForCurrencyCode($countries, $cur));
        $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "rate"=> (float) $currencyRate, "old_rate"=> $oldRate, "curr"=> $curArray);
        //Add the main node depending on function
        //$outputNode = constructOutputArray($dataArray, "action");
        $outputNode = array("action"=>$dataNode);

        //Convert array to the formatted out put, default xml.
        convertArrayToFormatForOutput("xml", $outputNode, "post");
    } else {
        //might need error code here
    }
}

//Display the deleted currency to the user.
function conductDeleteCurrency($action, $cur){
    //Getting contries information from the file stored locally.
    $rates = getRatesFromDataFile();
    //Get timestamp from rates file.
    $ts = $rates->xpath("/currencies/@ts");
    //Getting the currency from the XML data file
    $currency = $rates->xpath("/currencies/currency[@code='". $cur ."']");
    if(!$currency == false)
    {
        //Unset curreny specified from the xml
        unset($currency[0][0]);
        //Save the values in the rates.xml
        file_put_contents('../data/rates.xml', $rates->saveXML());
        //Contruct arrays with the data above
        $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "code"=> $cur);
        //Add the main node depending on function
        //$outputNode = constructOutputArray($dataArray, "action");
        $outputNode = array("action"=>$dataNode);
        //Convert array to the formatted out put, default xml.
        convertArrayToFormatForOutput("xml", $outputNode, "del");
    }
    else
    {
        //Output error 2300 - No rate listed for this currency
        outputErrorMessageResponse(2300); 
    }
}
?>