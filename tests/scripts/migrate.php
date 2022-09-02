<?php

try {
    $dbh = new PDO('mysql:host=mysql;dbname=test', 'root');
    echo "Connection success";
} catch(PDOException $e) {
    echo "Cannot connect";
}
