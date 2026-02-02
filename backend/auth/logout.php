<?php
session_start();
session_destroy();

$base_url = "http://localhost/event%20booking/frontend/";
header("Location: " . $base_url . "login.html");
exit;
