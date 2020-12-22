<?php

foreach ($_GET as $key => $value) 
{
    $_GET[$key] = htmlspecialchars($value);
}

foreach ($_POST as $key => $value) 
{
    $_POST[$key] = htmlspecialchars($value);
}

?>