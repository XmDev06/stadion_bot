<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'config.php';

$botToken = "5812515378:AAF8J9hvRbx5EULNJZ3I49jNg5slJIgIJT0";
// https://api.telegram.org/bot5812515378:AAF8J9hvRbx5EULNJZ3I49jNg5slJIgIJT0/setWebhook?url=https://59ca-213-230-102-9.eu.ngrok.io/projects/stadion_bot/index.php

/**
 * @var $bot \TelegramBot\Api\Client | \TelegramBot\Api\BotApi
 */

$bot = new \TelegramBot\Api\Client($botToken);


$bot->command('start', static function (\TelegramBot\Api\Types\Message $message) use ($removeButton, $connection, $bot) {
    try {
        $chatId = $message->getChat()->getId();
        $firstname = $message->getChat()->getFirstName();
        $is_verified = $connection->query("select * from users where chat_id = '$chatId'")->num_rows;
        if ($is_verified != 0) {
            $connection->query("update users set status = null where chat_id='$chatId'");
        }
        $bot->sendMessage($chatId, "👋 Assalomu alaykum botga xush kelibsiz!\nIltimos botga kirish uchun telefon raqamingizni kiriting.", null, false, false, $removeButton);


    } catch (Exception $exception) {
        //
    }
});


$bot->callbackQuery(static function (\TelegramBot\Api\Types\CallbackQuery $callbackquery) use ($connection, $bot) {
    try {

        $chatId = $callbackquery->getMessage()->getChat()->getId();
        $data = $callbackquery->getData();
        $firstname = $callbackquery->getMessage()->getChat()->getFirstName();
        $messageId = $callbackquery->getMessage()->getMessageId();

        if ($data == "createStd") {
            $bot->sendMessage($chatId, "Yangi stadion yaratish uchun Stadion nomini kiriting: ");
            $connection->query("update users set status = 'create_stadion' where chat_id='$chatId'");

        }

        if (strpos($data, "stadion") !== false) {
//            $bot->deleteMessage($chatId, $messageId);
            $stadion_id = explode("_", $data)[1];
            $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
            var_dump($stadion);
            $ega = $connection->query("select name from users where id = '$stadion[4]'")->fetch_assoc()["name"];
            $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[5]'")->fetch_assoc()['name'];
            $tuman = $connection->query("select name from tumanlars where id = '$stadion[6]'")->fetch_assoc()['name'];
            $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n📞 Bog'lanish uchun raqam:  $stadion[2]\n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n⏱ Soatlik narxi:  $stadion[3]\n";

            $button = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => '⏰ Stadion vaqtlari', 'callback_data' => 'std_vaqtlari']], [['text' => '⚙️ Tahrirlash', 'callback_data' => 'std_edit']]]);
//            $bot->sendLocation($chatId,'40.84894','72.069785');///////////////////////////////////////////////////bazadan opkelish kerag!!!
            $bot->sendMessage($chatId, $text, null, false, false, $button);
        }

    } catch (Exception $exception) {
    }
});


$bot->on(static function () {
},
    static function (\TelegramBot\Api\Types\Update $update) use ($connection, $bot) {

        try {
            $chat_id = $update->getMessage()->getChat()->getId();
            $text = $update->getMessage()->getText();
            $messageId = $update->getMessage()->getMessageId();

            $is_verified = $connection->query("select * from users where chat_id='$chat_id'")->num_rows;
            $status = $connection->query("select status from users where chat_id='$chat_id'")->fetch_assoc()['status'];
            if ($status == null) {
                $number = $connection->query("select * from users where is_admin='2' and phone='$text'")->num_rows;
                if ($number != 0) {
                    $connection->query("update users set chat_id='$chat_id', status='password' where is_admin='2' and phone='$text'");
                    $bot->sendMessage($chat_id, "🆔 Akkount parolini kiriting:");
                } else {
                    $bot->sendMessage($chat_id, "❗ Bunday raqam mavjud emas, Agarda siz hali ro'yxatdan o'tmagan bo'lsangiz example.com orqali ro'yxatdan o'ting yoki qaytadan urinib ko'ring!");
                }
            }

            if ($status == 'password') {
                $password_hash = $connection->query("select password from users where chat_id='$chat_id'")->fetch_assoc()['password'];
                $verify = password_verify($text, $password_hash);
                if ($verify) {
                    $name = $connection->query("select name from users where chat_id='$chat_id'")->fetch_assoc()['name'];
                    $user_id = $connection->query("select id from users where chat_id='$chat_id'")->fetch_assoc()['id'];
                    $stadions = $connection->query("select * from stadions where user_id = '$user_id'")->fetch_all();

                    $button = [[]];
                    foreach ($stadions as $stadion) {
                        $button[0][] = ["text" => "🏟 $stadion[1]", "callback_data" => "stadion_$stadion[0]"];
                    }
                    array_push($button[0], ["text" => '🆕 Stadion yaratish', "callback_data" => "createStd"]);
                    $button = array_chunk($button[0], 2);
                    var_dump($button);
                    $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);
                    $bot->sendMessage($chat_id, "Xush kelibsiz $name, bo'limlardan birini tanlang", null, false, false, $b);

//                    $connection->query("update users set status = 'stadion' where chat_id='$chat_id'");
                } else {
                    $bot->sendMessage($chat_id, "❗️Parolni noto'g'ri, qaytadan urinib ko'ring");
                }
            }


            if ($status == "create_stadion" && $text) {
                $filter = preg_match("/^[a-zA-Z '`‘]*$/", $text);
                if ($filter===1){
                    $connection->query("INSERT INTO `stadions`(`name`, `phone`, `narxi`, `user_id`, `viloyat`, `tuman`) values('$text',null)");
                }else{
                    $bot->sendMessage($chat_id,"❗Stadion nomida faqat harflar qatnashgan so'zlardan foydalaning");
                }
            }

        } catch (Exception $exception) {
        }
    });


$bot->run();