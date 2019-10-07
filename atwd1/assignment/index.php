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
$currenciesAccepted = ["AUD", 
         "BRL", 
         "CAD", 
         "CHF",
         "CNY",
         "DKK",
         "EUR",
         "GBP",
         "HKD",
         "HUF",
         "INR",
         "JPY",
         "MXN",
         "MYR",
         "NOK",
         "NZD",
         "PHP",
         "RUB",
         "SEK",
         "SGD",
         "THB",
         "TRY",
         "USD",
         "ZAR"
];

$currenciesAPIKey = "313f82e98f94595c11df26da43b9835f";

//Getting current currencies information from the API
$currentCurrencies = json_decode(file_get_contents('http://data.fixer.io/api/latest?access_key='. $currenciesAPIKey),true);

//Getting contries information from the file stored locally.
$countries = simplexml_load_file('./data/countries.xml') or die("Error: Cannot create object");

$currencies = $currentCurrencies["rates"];


//header('Content-Type: text/xml');
//echo $countries;

//echo $countries->CcyTbl->CcyNtry[0]->CtryNm;

$xpath = new DOMXPath($countries);

foreach ($xpath->evaluate("//@CtryNm") as $idAttribute) {
    echo $idAttribute->value;
  }

for ($i = 0; $i < sizeof($currenciesAccepted); $i++)
{
    $timeStamp =  time();
    $currencyRate = $currencies[$currenciesAccepted[$i]] / $currencies["GBP"];
    $currencyCode = $currenciesAccepted[$i];
    $currencyName = "";
    $currencyLocations = "";

    //echo $currencyCode ." - " . $currencyRate . " - ". $timeStamp. "</br>";   

}
?>