<?php
/*
    HostGuard Service Suspension API function
    POST Variables:
        - vserverid: containers.vserverid
        - reason:  Suspension reason
*/
if (!defined("WHMCS")) die("This file cannot be accessed directly");
if (!isset($_POST['reason']) || !isset($_POST['vserverid'])) {
        die('Invalid Request');
}
$vserverid = intval($_POST['vserverid']);
$reason = mysql_real_escape_string($_POST['reason']);

// This query SHOULD only get 1 server product
$hosting_query = mysql_query('
SELECT *, h.id AS hostingID
FROM tblcustomfieldsvalues v 
        LEFT JOIN tblcustomfields f ON f.id=v.fieldid
        LEFT JOIN tblhosting h ON h.id=v.relid 
WHERE 
        f.fieldname = "vserverid" AND v.value = '.$vserverid.'
LIMIT 1');

if (mysql_num_rows($hosting_query) == 0) {
        die('Invalid vserverid');
}

$account = mysql_fetch_assoc($hosting_query);
$values['accountid'] = $account['hostingID'];
$values['suspendreason'] = $reason;
$results = localAPI("modulesuspend", $values);
$results['service_id'] = $account['hostingID'];
if ($results['result'] != "success") {
        echo json_encode($results);
} else {
        echo json_encode($results);
}
?>