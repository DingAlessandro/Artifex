<?php
function logError(Exception $e):void{
    echo "DB error. Try again later.";
    error_log($e->getMessage() .date("Y-m-d H:i:s")."\n", 3, "dberror.log");
}