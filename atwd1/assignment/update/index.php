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
?>