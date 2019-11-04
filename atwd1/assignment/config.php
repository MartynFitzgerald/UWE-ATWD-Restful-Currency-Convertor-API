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
|  Description:  This file has all the consent values within this project.
|
*===========================================================================*/
//Setting default timezone of the service
@date_default_timezone_set("Europe/London");

//Defining expected GET parameters 
define('PRE_DEFINED_GET_PARAMETERS', array('from', 'to', 'amnt', 'format'));
//This array holds the predefined rates for the application. 
define('PRE_DEFINED_ISO_CODES', array('AUD','BRL','CAD','CHF','CNY','DKK','EUR','GBP','HKD','HUF','INR','JPY','MXN','MYR','NOK','NZD','PHP','RUB','SEK','SGD','THB','TRY','USD','ZAR'));

//Rates file name stored on the server.
define('RATES_FILENAME', 'rates.xml');
//Errors file name stored on the server.
define('ERRORS_FILENAME', 'errors.xml');
//Countries file name stored on the server.
define('COUNTRIES_FILENAME', 'countries.xml');

//Base currency for the rates stored.
define('BASE', 'GBP');
//Formats that this service will be allowed.
define('FORMATS', array('xml', 'json'));

//Base currency for the rates stored.
define('URL_RATES', 'http://data.fixer.io/api/latest?access_key=313f82e98f94595c11df26da43b9835f');

//phpinfo();
?>