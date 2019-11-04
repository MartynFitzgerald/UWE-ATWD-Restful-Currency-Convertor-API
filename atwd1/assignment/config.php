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

define('RATES_LOCATION', 'rates.xml');

//Defining expected GET parameters 
define('PRE_DEFINED_GET_PARAMETERS', serialize (array('from', 'to', 'amnt', 'format')));

define('BASE', 'GBP');

define('FORMATS', serialize (array('xml', 'json')));

phpinfo();
?>