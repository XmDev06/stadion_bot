<?php
date_default_timezone_set("Asia/Tashkent");

$connection = mysqli_connect('localhost', 'root', '', 'stadion');

$removeButton = new \TelegramBot\Api\Types\ReplyKeyboardRemove(true);

$vaqtlar_massiv = ['7:00 - 7:30', '7:30 - 8:00', '8:00 - 8:30', '8:30 - 9:00', '9:00 - 9:30', '9:30 - 10:00', '10:00 - 10:30', '10:30 - 11:00', '11:00 - 11:30', '11:30 - 12:00', '12:00 - 12:30', '12:30 - 13:00', '13:00 - 13:30', '13:30 - 14:00', '14:00 - 14:30', '14:30 - 15:00', '15:00 - 15:30', '15:30 - 16:00', '16:00 - 16:30', '16:30 - 17:00', '17:00 - 17:30', '17:30 - 18:00', '18:00 - 18:30', '18:30 - 19:00', '19:00 - 19:30', '19:30 - 20:00', '20:00 - 20:30', '20:30 - 21:00', '21:00 - 21:30', '21:30 - 22:00', '22:00 - 22:30', '22:30 - 23:00', '23:00 - 23:30', '23:30 - 00:00', '00:00 - 00:30', '00:30 - 1:00', '1:00 - 1:30', '1:30 - 2:00'];


// oragaga
//  ['text'=>"Orqaga 🔙", 'callback_data'=>"orqaga"]
//  new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text'=>"Orqaga 🔙", 'callback_data'=>"orqaga"]]],false,true)
//  , null, false,null, new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]],false,true)


function boshMenu($chat_id, $messageId, $connection, $bot, $deleteMessage = true)
{
//    $connection->query("update consumer set status = '0' where chat_id = '$chat_id'");
    $buttons = new  \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => "🏟 Stadionlar"],['text' => "🛒 Buyurtmalarim"]],[['text' => "⚙ Sozlamalar"]]],false,true);
    $bot->sendMessage($chat_id, "Buyurtmalaringiz ro'yxatini ko'rish uchun  🛒 Buyurtmalarim, yangi buyurtma berish uchun  🏟 Stadionlar  tugmasini bosing.", null, false, null, $buttons);
    if ($deleteMessage) {
        $bot->deleteMessage($chat_id, $messageId);
    }
}

function adminMenu($chat_id, $bot, $connection)
{
    $connection->query("update users set status = 'stadion' where chat_id='$chat_id'");
    $kun = date("d-m-Y");
    $vaqt = date("H");

    $connection->query("DELETE FROM `vaqtlar` WHERE kun < '$kun'");
    $vaqtlar_massiv = $GLOBALS['vaqtlar_massiv'];
    foreach ($vaqtlar_massiv as $item) {
        $e = explode(":", explode(" - ", $item)[1])[0];
        if ($e < ($vaqt - 1) && $e != 00 && $e != 1&& $e != 2) {
            $connection->query("DELETE FROM `vaqtlar` WHERE kun = '$kun' and vaqt = '$item'");
        }
    }

    $user_id = $connection->query("select id from users where chat_id='$chat_id'")->fetch_assoc()['id'];
    $name = $connection->query("select name from users where chat_id='$chat_id'")->fetch_assoc()['name'];
    $stadions = $connection->query("select * from stadions where user_id = '$user_id'")->fetch_all();

    $button = [[]];
    foreach ($stadions as $stadion) {
        $button[0][] = ["text" => "🏟 $stadion[1]", "callback_data" => "stadion_$stadion[0]"];
    }
    array_push($button[0], ["text" => '🆕 Stadion yaratish', "callback_data" => "createStd"]);
    $button = array_chunk($button[0], 2);

    $removeButton = new \TelegramBot\Api\Types\ReplyKeyboardRemove(true);
    $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);
    $bot->sendMessage($chat_id, "$name, bo'limlardan birini tanlang", null, false, false, $b);

}
