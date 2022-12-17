<?php

$connection = mysqli_connect('localhost','root','','stadion');

$removeButton = new \TelegramBot\Api\Types\ReplyKeyboardRemove(true);

$vaqtlar_massiv = ['7:00','7:30','8:00','8:30','9:00','9:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30','20:00','20:30','21:00','21:30','22:00','22:30','23:00','23:30','00:00','00:30','1:00','1:30'];



function boshMenu($chat_id, $messageId, $connection, $bot, $deleteMessage = true)
{
    $tuman = $connection->query("select tuman from consumer where  chat_id ='$chat_id'")->fetch_assoc()['tuman'];
    $stadions = $connection->query("select * from stadions where tuman = '$tuman'")->fetch_all();

    $buttons = [];
    foreach ($stadions as $stadion) {
        $buttons[] = ['text' => "🏟 $stadion[1]", "callback_data" => "stadionInfo_$stadion[0]"];
    }
    $chunkB = array_chunk($buttons, 2);
    array_unshift($chunkB,[['text' => "⚙ Sozlamalar", "callback_data" => "settings"]]);
    $sButtons = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($chunkB);
    $bot->sendMessage($chat_id, "Buyurtmalaringiz ro'yxatini ko'rish uchun /buyurtmalarim buyrug'ini bering. Yangi buyurtma berish uchun berish uchun stadionlardan birini tanlang:", null, false, null, $sButtons);
    if ($deleteMessage){
        $bot->deleteMessage($chat_id, $messageId);
    }
}