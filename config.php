<?php

$connection = mysqli_connect('localhost','root','','stadion');

$removeButton = new \TelegramBot\Api\Types\ReplyKeyboardRemove(true);
$mainbutton = [[['text'=>'']]];



