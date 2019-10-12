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
|  Description:  Creating a functions used in the index.php file
|
*===========================================================================*/

function displayJSON($json) {
    header('Content-Type: application/json');
    echo $json;
}

function displayXML($dom) {
    header('Content-Type: text/xml');
    echo $dom->saveXML();
}

?>