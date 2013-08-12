<?php
// Begin Standard Definitions
define("THINGS2DO", true);
$root=$_SERVER['DOCUMENT_ROOT'].'/';
include "$root/application/keystore.php";
@include "$root/config/apikeys.php";
header("Content-Type: application/json");
// End Standard Definitions

if (!defined("KEYSTORE_AUTHORIZED")){
    //apikeys not working, could have been tampered with
    @unlink("$root/config/apikeys.php"); // delete file to stop any further security issues
    die(json_encode(Array(
        "error"=>Array(
            "message" => "API Keys damaged",
            "id" => -1
        ))
    ));
}
try {
    include "$root/application/things2do.php";
    $t2d=new things2do($root);
    $t2d->suggestToUser();
}
catch (Exception $e) {
    die(json_encode(Array(
        "error"=>Array(
            "message" => $e->getMessage(),
            "id" => -2,
            "code" => $e->getCode(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "trace" => $e->getTrace()
        ))
    ));
}
//by this point, the code should have finished. If not, run below code
echo json_encode(Array(
    "error"=>Array(
        "message" => "Unsuccessfully finished fetching results",
        "id" => -3
    ))
);
?>