
<?php

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "skillswap"
);

if (!$conn) {
    die("DB ERROR: " . mysqli_connect_error());
}

?>
