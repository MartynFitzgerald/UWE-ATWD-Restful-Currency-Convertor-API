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

$baseCurrency = "GBP";

$xmlFileName = 'rates.xml';

//If no file has been created
if (!file_exists($xmlFileName))
{
    // API key for the currencies, allows me to get live information.
    $currenciesAPIKey = "313f82e98f94595c11df26da43b9835f";

    //Getting current currencies information from the API
    $currentCurrencies = json_decode(file_get_contents('http://data.fixer.io/api/latest?access_key='. $currenciesAPIKey),true);

    //Getting contries information from the file stored locally.
    $countries = simplexml_load_file('./data/countries.xml') or die("Error: Cannot create object");

    //Setting the rates of all currency rates to the a varible.
    $currencies = $currentCurrencies["rates"];

    //Create XML Document
    $dom = new DOMDocument("1.0");

    //Creating "currencies" Node
    $root = $dom->createElement("currencies");

    //Adding attribute "base" to "currencies" Node
    $root->setAttributeNode(new DOMAttr("base", $baseCurrency));

    //Setting root to the XML document
    $dom->appendChild($root);

    //This loop cycles through the predefined rates.
    for ($i = 0; $i < sizeof($currenciesISOCodes); $i++)
    {
        //Creating timestamp for when created in XML document
        $timeStamp =  time();
        //Calculating the rate, since the API base currency isnt GBP
        $currencyRate = $currencies[$currenciesISOCodes[$i]] / $currencies[$baseCurrency];
        //Setting the ISO code for this currency
        $currencyCode = $currenciesISOCodes[$i];
        //Getting the currency name from the XML data file
        $currencyName = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currenciesISOCodes[$i] . "']/CcyNm");
        //Getting the currency locations from the XML data file
        $currencyLocations = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currenciesISOCodes[$i] . "']/CtryNm");
        //Formatted the locations to put them into a string and also capitalization the first letter within a word
        $currencyLocationsFormatted = ucwords(strtolower(implode(", ",$currencyLocations)));
        
        //Print this out to the screen now
        //echo $timeStamp ." - " . $currencyRate . " - " . $currencyCode . " - " . $currencyName[0] . " - " . $currencyLocationsFormatted . "</br>";  

        $itemNode = $dom->createElement("currency");
        $itemNode->appendChild($dom->createElement("at", $timeStamp));
        $itemNode->appendChild($dom->createElement("rate", $currencyRate));
        $currNode = $itemNode->appendChild($dom->createElement("curr"));
            $currNode->appendChild($dom->createElement("code", $currencyCode));
            $currNode->appendChild($dom->createElement("name", $currencyName[0]));
            $currNode->appendChild($dom->createElement("loc", $currencyLocationsFormatted));
        $itemNode->appendChild($currNode);
        $root->appendChild($itemNode);
    }

    //Saving XML document to the filename defined above
    $dom->save($xmlFileName);

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