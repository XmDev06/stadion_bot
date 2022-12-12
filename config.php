<?php

$connection = mysqli_connect('localhost','newuser','password','stadion');

$removeButton = new \TelegramBot\Api\Types\ReplyKeyboardRemove(true);
$mainbutton = [[['text'=>'']]];


