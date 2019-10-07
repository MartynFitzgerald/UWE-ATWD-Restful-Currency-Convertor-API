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

$currenciesAPIKey = "313f82e98f94595c11df26da43b9835f";

//Getting current currencies information from the API
$currentCurrencies = json_decode(file_get_contents('http://data.fixer.io/api/latest?access_key='. $currenciesAPIKey),true);

//Getting contries information from the file stored locally.
$countries = file_get_contents('./data/countries.xml'); 

$currencies = $currentCurrencies["rates"];


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

for ($i = 0; $i < sizeof($currenciesAccepted); $i++)
{
    $convertCurrency = $currencies[$currenciesAccepted[$i]] / $currencies["GBP"];

    echo $currenciesAccepted[$i] ." - " . $convertCurrency . "</br>";   

}
?>