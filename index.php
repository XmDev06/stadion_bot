<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'config.php';

$botToken = "5812515378:AAF8J9hvRbx5EULNJZ3I49jNg5slJIgIJT0";
// https://api.telegram.org/bot5812515378:AAF8J9hvRbx5EULNJZ3I49jNg5slJIgIJT0/setWebhook?url=https://4f44-188-113-236-77.in.ngrok.io/stadion_bot/index.php

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
            $stadion_id = explode("_", $data)[1];
            $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
            var_dump($stadion);
            $ega = $connection->query("select name from users where id = '$stadion[6]'")->fetch_assoc()["name"];
            $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[7]'")->fetch_assoc()['name'];
            $tuman = $connection->query("select name from tumanlars where id = '$stadion[8]'")->fetch_assoc()['name'];
            var_dump($stadion);
            $phone_2 = '';
            if ($stadion[3]!== null){
                $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
            }
            $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $stadion[4]\n\n⏱ Soatlik narxi:  $stadion[5]\n ";

            $button = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => '⏰ Stadion vaqtlari', 'callback_data' => 'std_vaqtlari']], [['text' => '⚙️ Tahrirlash', 'callback_data' => 'std_edit']]]);
//            $bot->sendLocation($chatId,'40.84894','72.069785');///////////////////////////////////////////////////bazadan opkelish kerag!!!
            $bot->sendMessage($chatId, $text, null, false, false, $button);
        }

        if ($data == "phone_number_2"){
            $bot->sendMessage($chatId, "Tahminiy mo'ljal kiriting 📍");
            $connection->query("update users set status = 'moljal' where chat_id='$chatId'");
            $myfile = fopen("session/$chatId.txt", "a") or die("Unable to open file!");
            fwrite($myfile, "phone_2=null;");
            fclose($myfile);
            $bot->deleteMessage($chatId, $messageId);
        }

        if (strpos($data, "viloyat_") !== false){
            $viloyat_id = explode("_", $data)[1];
            $tumanlar = $connection->query("select * from tumanlars where viloyat_id = $viloyat_id")->fetch_all();

            $button = [[]];
            foreach ($tumanlar as $tuman) {
                $button[0][] = ["text" => "$tuman[1]", "callback_data" => "tuman_$tuman[0]"];
            }
            $button = array_chunk($button[0], 2);
            $tuman_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

            $bot->sendMessage($chatId, "Tumanni tanlang 👇👇👇 ", null, false, null, $tuman_btn);
            $connection->query("update users set status = 'tuman' where chat_id='$chatId'");

            $myfile = fopen("session/$chatId.txt", "a");
            fwrite($myfile, "viloyat=".$viloyat_id.";");
            fclose($myfile);
            $bot->deleteMessage($chatId, $messageId);
        }

        if (strpos($data, "tuman_") !== false){
            $tuman_id = explode("_", $data)[1];

            $myfile = fopen("session/$chatId.txt", "a");
            fwrite($myfile, "tuman=".$tuman_id.";");
            fclose($myfile);

            $tuman =  $connection->query("select name from tumanlars where id = $tuman_id")->fetch_assoc()['name'];
            $moljal = '';
            $name = '';
            $phone = '';
            $phone_2 = '';
            $viloyat = '';
            $narx = '';

            $data = file_get_contents("session/$chatId.txt");
            $data_massiv = explode(';', $data);

            foreach ($data_massiv as $item) {
                $keylar = explode('=', $item);
                if ($keylar[0] == 'name'){
                    $name .= $keylar[1];
                }
                if($keylar[0]== 'moljal'){
                    $moljal .= $keylar[1];
                }
                if($keylar[0]== 'phone'){
                    $phone .= $keylar[1];
                }
                if($keylar[0]== 'phone_2' && $keylar[1] !== "null"){
                    $phone_2 .= $keylar[1];
                }
                if($keylar[0]== 'viloyat'){
                    $viloyat = $connection->query("select name from viloyatlars where id = $keylar[1]")->fetch_assoc()['name'];
                }
                if ($keylar[0] == 'narxi'){
                    $narx .= $keylar[1];
                }
            }

            $phone_number_2 = '';
            if ($phone_2 !== ''){
                $phone_number_2 .= "📞 Bog'lanish uchun raqam 2: +$phone_2\n";
            }
            $text = "🏟 Stadion nomi:  $name\n‍📞 Bog'lanish uchun raqam: +$phone\n$phone_number_2\n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $moljal\n\n⏱ Soatlik narxi:  $narx\n ";


            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"Qayta to'ldirish", "callback_data"=>"reset"],['text'=>"Tasdiqlash 👍", "callback_data"=>"tasdiqlash"]]]);
            $bot->sendMessage($chatId, $text, null, false, null, $btn);
            $connection->query("update users set status = 'tasdiqlash' where chat_id='$chatId'");

            $bot->deleteMessage($chatId, $messageId);
        }

        if ($data == 'tasdiqlash'){
            $data = file_get_contents("session/$chatId.txt");
            $data_massiv = explode(';', $data);
            $user_id ='';
            $key = '';
            $value = '';
            foreach ($data_massiv as $item) {
                $keylar = explode('=', $item);
                $key .= $keylar[0].",";
                if ($keylar[1]== "null"){
                    $value .= $keylar[1].",";
                }else{
                    $value .= '"'.$keylar[1].'",';
                }
            }
            $key = substr($key, 0,-2);
            $value = substr($value, 0,-4);

            $test = $connection->query("insert into stadions ($key) values ($value)");
            var_dump("insert into stadions ($key) values ($value)");
            if ($test){
                $bot->sendMessage($chatId, 'zooor');
            }else{
                $bot->sendMessage($chatId, 'noooo');
            }
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
            $user_id = $connection->query("select id from users where chat_id='$chat_id'")->fetch_assoc()['id'];
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

                    $stadions = $connection->query("select * from stadions where user_id = '$user_id'")->fetch_all();

                    $button = [[]];
                    foreach ($stadions as $stadion) {
                        $button[0][] = ["text" => "🏟 $stadion[1]", "callback_data" => "stadion_$stadion[0]"];
                    }
                    array_push($button[0], ["text" => '🆕 Stadion yaratish', "callback_data" => "createStd"]);
                    $button = array_chunk($button[0], 2);

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
                    $myfile = fopen("session/$chat_id.txt", "w") or die("Unable to open file!");
                    fwrite($myfile, "name=".$text.";");
                    fclose($myfile);
                    $connection->query("update users set status = 'phone_number' where chat_id='$chat_id'");
                    $bot->sendMessage($chat_id, "Bog'lanish uchun telefon raqam kiriting ☎️\n(Na'muna: 998991112233)");

                }else{
                    $bot->sendMessage($chat_id,"❗Stadion nomida faqat harflar qatnashgan so'zlardan foydalaning");
                }
            }


            if ($status == 'phone_number' && $text){
                $filter_number = preg_match("/^[0-9]{12,12}/", $text);
                if ($filter_number === 1){
                    $next_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"O'tkazib yuborish ⏭", "callback_data"=>'phone_number_2']]]);
                    $bot->sendMessage($chat_id, "Qo'shimcha telefon raqam mavjud bo'lsa kiriting☎️\n(Na'muna: 998991112233)", null, false, null, $next_btn);
                    $connection->query("update users set status = 'phone_number_2' where chat_id='$chat_id'");

                    $myfile = fopen("session/$chat_id.txt", "a");
                    fwrite($myfile, "phone=".$text.";");
                    fclose($myfile);
                }else{
                    $bot->sendMessage($chat_id, "❗️ Iltimos, telefon raqamni namunadagidek kiriting");
                }
            }

            if ($status == 'phone_number_2' && $text){
                $filter_number = preg_match("/^[0-9]{12,12}/", $text);
                if ($filter_number === 1){
                    $bot->sendMessage($chat_id, "Tahminiy mo'ljal kiriting 📍");
                    $connection->query("update users set status = 'moljal' where chat_id='$chat_id'");
                    $myfile = fopen("session/$chat_id.txt", "a");
                    fwrite($myfile, "phone_2=".$text.";");
                    fclose($myfile);
                }else{
                    $bot->sendMessage($chat_id, "❗️ Iltimos, telefon raqamni namunadagidek kiriting");
                }
            }

            if ($status == 'moljal'){
                $myfile = fopen("session/$chat_id.txt", "a");
                fwrite($myfile, "moljal=".$text.";");
                fclose($myfile);
                $bot->sendMessage($chat_id, "Stadion lacatsiyasini tashlang 📍");
                $connection->query("update users set status = 'location' where chat_id='$chat_id'");
            }

            if ($status == 'location'){
                $latitude = $update->getMessage()->getLocation()->getLatitude();
                $longitude = $update->getMessage()->getLocation()->getLongitude();
                $myfile = fopen("session/$chat_id.txt", "a");
                fwrite($myfile, "latitude=".$latitude.";");
                fwrite($myfile, "longitude=".$longitude.";");
                fclose($myfile);

                $bot->sendMessage($chat_id, "Stadion narxini so'mda kiriting 💰 (Na'muna: 50000)");
                $connection->query("update users set status = 'narx' where chat_id='$chat_id'");
            }

            if ($status == "narx"){
                $filter_narx = preg_match("/^[0-9]/", $text);
                if ($filter_narx === 1){
                    $myfile = fopen("session/$chat_id.txt", "a");
                    fwrite($myfile, "narxi=".$text.";");
                    fwrite($myfile, "user_id=".$user_id.";");
                    fclose($myfile);


                    $viloyatlar = $connection->query("select * from viloyatlars")->fetch_all();
                    $button = [[]];
                    foreach ($viloyatlar as $viloyat) {
                        $button[0][] = ["text" => "$viloyat[1]", "callback_data" => "viloyat_$viloyat[0]"];
                    }
                    $button = array_chunk($button[0], 2);

                    $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

                    $bot->sendMessage($chat_id, "Viloyatni tanlang 🏙", null, false, null, $b);
                    $connection->query("update users set status = 'viloyat' where chat_id='$chat_id'");
                }else{
                    $bot->sendMessage($chat_id, "❗️ Iltimos, stadion summasini namunadagidek kiriting");
                }
            }


        } catch (Exception $exception) {
        }
    });


$bot->run();