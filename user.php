<?php
require_once __DIR__ . '/vendor/autoload.php';
include "config.php";

$botToken = "5823532001:AAFe5Y1I1JsmxC8XoUc2aPiRqtmuf83oek8";
// https://api.telegram.org/bot5823532001:AAFe5Y1I1JsmxC8XoUc2aPiRqtmuf83oek8/setWebhook?url=https://1b9e-84-54-90-105.eu.ngrok.io/projects/stadion_bot/user.php

$bot = new \TelegramBot\Api\Client($botToken);

/**
 * @var $bot \TelegramBot\Api\Client | \TelegramBot\Api\BotApi
 */

$bot->command('start', static function (\TelegramBot\Api\Types\Message $message) use ($removeButton, $bot, $connection) {
    try {
        $chat_id = $message->getChat()->getId();
        $firstname = $message->getChat()->getFirstName();
        $messageId = $message->getMessageId();
        $bot->sendPhoto($chat_id, "https://i.eurosport.com/2013/05/01/1000505-19249060-2560-1440.jpg", "Assalom Alaykum, Endi siz stadionlardan vaqt olish uchun ortiqcha vaqt sarflashingiz shart emas. Siz bu bot orqali yon atrofingizdagi stadionlardan vaqt band qilishingiz mumkin. Marhamat sinab ko'ring!\n\n@stadion_user_bot", null, $removeButton);


        $userN = $connection->query("select * from consumer where chat_id = '$chat_id'")->num_rows;
        $user = $connection->query("select * from consumer where chat_id = '$chat_id'")->fetch_assoc();
        if ($userN == 0 || $user['phone'] === null || $user['name'] === null || $user['manzil'] === null || $user['viloyat'] === null || $user['tuman'] === null) {
            if ($userN == 0) {
                $connection->query("insert into consumer(chat_id) values ('$chat_id')");
            }

            $button = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => "Kontaktni ulashish ⤴", "request_contact" => true]]], true, true);
            $bot->sendMessage($chat_id, "Siz bilan bog'lanishimiz uchun << Kontaktni ulashish >> tugmasi orqali yoki o'zingiz telefon raqamingizni yuboring", null, false, null, $button);
            $connection->query("update consumer set status = 'phone' where chat_id = '$chat_id'");
        } else {
            boshMenu($chat_id, $messageId, $connection, $bot,false);
        }

    } catch (Exception $exception) {
        //
    }
});
$bot->command('buyurtmalarim', static function (\TelegramBot\Api\Types\Message $message) use ($bot, $connection) {
    try {
        $chat_id = $message->getChat()->getId();
        $firstname = $message->getChat()->getFirstName();
        $messageId = $message->getMessageId();

        ///////////////////
        $bot->sendPhoto($chat_id, "https://i.eurosport.com/2013/05/01/1000505-19249060-2560-1440.jpg", "Assalom Alaykum, Endi siz stadionlardan vaqt olish uchun ortiqcha vaqt sarflashingiz shart emas. Siz bu bot orqali yon atrofingizdagi stadionlardan vaqt band qilishingiz mumkin. Marhamat sinab ko'ring!\n\n@stadion_user_bot", null, $removeButton);

    } catch (Exception $exception) {
        //
    }
});

$bot->callbackQuery(static function (\TelegramBot\Api\Types\CallbackQuery $callbackquery) use ($vaqtlar_massiv, $bot, $connection) {
    try {
        $chat_id = $callbackquery->getMessage()->getChat()->getId();
        $data = $callbackquery->getData();
        $firstname = $callbackquery->getMessage()->getChat()->getFirstName();
        $messageId = $callbackquery->getMessage()->getMessageId();
        $status = $connection->query("select status from consumer where chat_id='$chat_id'")->fetch_assoc()['status'];

        if (strpos($data, 'boshMenu') !== false) {
            boshMenu($chat_id, $messageId, $connection, $bot);
        }

        /////////////// Login Start ///////////////////////

        if (strpos($data, "viloyat_") !== false) {
            $viloyat_id = explode("_", $data)[1];
            $tumanlar = $connection->query("select * from tumanlars where viloyat_id ='$viloyat_id'")->fetch_all();

            $button = [];
            foreach ($tumanlar as $tuman) {
                $button[] = ["text" => "$tuman[1]", "callback_data" => "tuman_$tuman[0]"];
            }
            $tbutton = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(array_chunk($button, 2));

            $test = $connection->query("update consumer set viloyat = '$viloyat_id' where chat_id = '$chat_id'");

            $bot->sendMessage($chat_id, "Tuman yoki shahringizni tanlang", null, false, null, $tbutton);
            $bot->deleteMessage($chat_id, $messageId);
        }
        if (strpos($data, "tuman_") !== false) {
            $tuman_id = explode("_", $data)[1];
            $connection->query("update consumer set tuman = '$tuman_id', status = 'manzil' where chat_id = '$chat_id'");
            $bot->sendMessage($chat_id, "Iltimos aniq manzilingizni kiriting");
            $bot->deleteMessage($chat_id, $messageId);
        }

        /////////////// Login End //////////////////////

        if (strpos($data, "stadionInfo_") !== false) {
            $stadion_id = explode("_", $data)[1];
            $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
            $ega = $connection->query("select name from users where id = '$stadion[6]'")->fetch_assoc()["name"];
            $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[7]'")->fetch_assoc()['name'];
            $tuman = $connection->query("select name from tumanlars where id = '$stadion[8]'")->fetch_assoc()['name'];

            $phone_2 = '';
            if ($stadion[3] !== null) {
                $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
            }
            $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $stadion[4]\n\n⏱ Soatlik narxi:  $stadion[5]\n ";
            $button = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => '⏰ Vaqt olish', 'callback_data' => "buyTime_$stadion_id"], ['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]]);
            $bot->sendMessage($chat_id, $text, null, false, false, $button);
            $bot->deleteMessage($chat_id, $messageId);
        }

        /////////////// Buy Time //////////////////

        if (strpos($data, "buyTime_") !== false) {
            $stadion_id = explode("_", $data)[1];

            $now_date = date('Y/m/d');
            $days = [];
            for ($i = 0; $i < 15; $i++) {
                $kun = date('d.m.y', strtotime("+$i day", strtotime($now_date)));
                $kun_call = date('d-m-Y', strtotime("+$i day", strtotime($now_date)));
                $days[] = ['text' => "$kun", 'callback_data' => $stadion_id . "_Stdday_$kun_call"];
            }
            $days[] = ['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu'];
            $days_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(array_chunk($days, 3));

            $bot->sendMessage($chat_id, "Band qilingan vaqtlarni ko'rish uchun kunlardan birini tanlang 👇", false, false, null, $days_btn);
            $bot->deleteMessage($chat_id, $messageId);
        }
        if (strpos($data, "Stdday_") !== false) {
            $stadion_id = explode("_", $data)[0];
            $kun = explode("_", $data)[2];
            $vaqtMassiv = [];
            foreach ($vaqtlar_massiv as $item) {
                $vaqtNow = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$item'")->num_rows;
                if ($vaqtNow == 0) {
                    $vaqtMassiv[] = ['text' => "$item", "callback_data" => "NewVaqt" . "_$stadion_id" . "_$item" . "_$kun"];
                }
            }
            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(array_chunk($vaqtMassiv, '4'));
            $bot->sendMessage($chat_id, 'Qaysi vaqtni buyurtma qilasiz ?', false, null, false, $btn);
            $bot->deleteMessage($chat_id, $messageId);

        }
        if (strpos($data, "NewVaqt_") !== false) {
            var_dump(explode("_", $data));
            $stadion_id = explode("_", $data)[1];
            $vaqt = explode("_", $data)[2];
            $kun = explode("_", $data)[3];
            $myfile = fopen("vaqt/$chat_id.txt", "a") or die("Unable to open file!");


            $vaqtlar_text = []; // fayldagi vaqtlar array
            $text_file_massiv = explode("###", file_get_contents("vaqt/$chat_id.txt"));
            foreach ($text_file_massiv as $value) {
                $vaqtlar_text[] = explode(",", $value)[1];
            }
            var_dump($vaqtlar_text);

            if (!in_array($vaqt, $vaqtlar_text)) {
                fwrite($myfile, "$stadion_id,$vaqt,$kun###");
                fclose($myfile);
            }

            $vaqtMassiv = []; // for button
            foreach ($vaqtlar_massiv as $key => $item) {
                $vaqtBandmi = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$item'")->num_rows;
                if ($vaqtBandmi == 0) {
                    if (in_array($item, $vaqtlar_text) || $vaqt == $item) {
                        $vaqtMassiv[] = ['text' => "$item ✅", "callback_data" => "NewVaqt" . "_$stadion_id" . "_$item" . "_$kun"];
                    } else {
                        $vaqtMassiv[] = ['text' => "$item", "callback_data" => "NewVaqt" . "_$stadion_id" . "_$item" . "_$kun"];
                    }
                }
            }
            $vaqt_chunk = array_chunk($vaqtMassiv, '4');
            $vaqt_chunk[] = [['text' => "Tasdiqlash ✅", "callback_data" => "vaqtConfirmPhone"], ['text' => "Bekor qilish ❌", "callback_data" => "vaqtCancle"]];
            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($vaqt_chunk);

            $bot->sendMessage($chat_id, "Agar boshqa buyurtmangiz bo'lmasa tasdiqlash tugmasini bosing. Agar bo'lsa yana vaqt tanlang", false, null, false, $btn);
            $bot->deleteMessage($chat_id, $messageId);

        }
        if ($data == "vaqtConfirmPhone") {
            $bot->sendMessage($chat_id, "Siz bilan bog'lanish uchun telefon raqam yuboring");
            $bot->deleteMessage($chat_id,$messageId);
            $connection->query("update consumer set status = 'vaqtConfirmPhone' where chat_id = '$chat_id'");
        }
        if ($data == "vaqtConfirm") {
            $file_arr = explode("###", file_get_contents("vaqt/$chat_id.txt"));
            $stadion_id = explode(",", file_get_contents("vaqt/$chat_id.txt"))[0];
            array_pop($file_arr);
            $oldbuyurtmachiId = $connection->query("select id from consumer where chat_id = '$chat_id'")->fetch_assoc()['id'];
            foreach ($file_arr as $value) {
                $valuArr = explode(",", $value);
                $stadion_id = $valuArr[0];
                $vaqt = $valuArr[1];
                $kun = $valuArr[2];
                $vaqtNow = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$vaqt'")->num_rows;
                if ($vaqtNow == 0) {
                    $connection->query("insert into vaqtlar (stadion_id, consumer, vaqt, kun) values ('$stadion_id', '$oldbuyurtmachiId', '$vaqt', '$kun')");
                }
            }
            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Orqaga 🔙", "callback_data" => "buyTime_$stadion_id"], ['text' => "Bosh Menu 🏘", "callback_data" => "boshMenu"]]]);
            $bot->sendMessage($chat_id, "Siz tanlagan vaqtlar band qilindi", null, false, null, $btn);
            unlink("vaqt/$chat_id.txt");
            $bot->deleteMessage($chat_id, $messageId);

        }
        if ($data == "vaqtCancle") {
            unlink("vaqt/$chat_id.txt");
            $bot->sendMessage($chat_id, "Tanlangan vaqtlar bekor qilindi!", null, false, null, new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Bosh Menu 🏘", "callback_data" => "boshMenu"]]]));
            $bot->deleteMessage($chat_id, $messageId);
        }

        /////////////// Buy Time End //////////////////

        ///////////// Settings start ///////////

        if ($data == "settings"){
            $user = $connection->query("select * from consumer where chat_id = '$chat_id'")->fetch_all()[0];
            $viloyat = $connection->query("select name from viloyatlars where id = '$user[5]'")->fetch_assoc()['name'];
            $tuman = $connection->query("select name from tumanlars where id = '$user[6]'")->fetch_assoc()['name'];

            var_dump($user);
            $text = "Sizning ma'lumotlaringiz:\n\n🆔 Ismingiz: $user[2]\n📲 Telefon raqamingiz: $user[3]\n🗺 Yashash joyingiz: $viloyat viloyati, $tuman tumani\n📍 Manzilingiz: $user[4]\n";

            $bot->sendMessage($chat_id,$text);
//            $bot->deleteMessage($chat_id,$messageId);
        }

        ///////////// Settings start ///////////
    } catch (Exception $exception) {
        //
    }
});

$bot->on(static function () {
},
    static function (\TelegramBot\Api\Types\Update $update) use ($bot, $connection) {
        try {
            $chat_id = $update->getMessage()->getChat()->getId();
            $text = $update->getMessage()->getText();
            $messageId = $update->getMessage()->getMessageId();
            $status = $connection->query("select status from consumer where chat_id='$chat_id'")->fetch_assoc()['status'];

            /////////////// Login Start ///////////////////////

            if ($status == 'phone') {
                if ($text) {
                    $filter_number = preg_match("/^[0-9]{12,12}/", $text);
                    if ($filter_number === 1) {
                        $phone_number = $text;
                        $bot->sendMessage($chat_id, "Ismingizni kiriting ");
                    } else {
                        $bot->sendMessage($chat_id, "❗️ Iltimos, telefon raqamni namunadagidek kiriting(Na'muna: 998338885544)");
                    }
                } else {
                    $phone_number = $update->getMessage()->getContact()->getPhoneNumber();
                    $bot->sendMessage($chat_id, "Ismingizni kiriting ");
                }
                $test = $connection->query("update consumer set phone = '$phone_number', status = 'name' where chat_id = '$chat_id'");
            }
            if ($status == 'name') {
                $filter = preg_match("/^[a-zA-Z '`‘]*$/", $text);
                if ($filter === 1) {
                    $rep_text = str_replace("'", "\'", $text);
                    $connection->query("update consumer set name = '$rep_text', status = '0' where chat_id ='$chat_id'");
                    $viloyatlar = $connection->query("select * from viloyatlars")->fetch_all();
                    $button = [];
                    foreach ($viloyatlar as $viloyat) {
                        $button[] = ["text" => "$viloyat[1]", "callback_data" => "viloyat_$viloyat[0]"];
                    }
                    $vbutton = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(array_chunk($button, 2));
                    $bot->sendMessage($chat_id, "Yon atrofingizdagi stadionlarni topish uchun joylashuvingizni tanlang", null, false, null, $vbutton);

                } else {
                    $bot->sendMessage($chat_id, "❗Ismda faqat harflar qatnashgan so'zlardan foydalaning");
                }
            }
            if ($status == "manzil") {
                $rep_text = str_replace("'", "\'", $text);
                $connection->query("update consumer set manzil = '$rep_text', status = '0' where chat_id ='$chat_id'");
                boshMenu($chat_id, $messageId, $connection, $bot);
            }

            /////////////// Login End //////////////////////

            if ($status == "vaqtConfirmPhone") {
                $filter_number = preg_match("/^[0-9]{12,12}/", $text);
                if ($filter_number === 1) {
                    $connection->query("update consumer set phone = '$text', status = '0' where chat_id = '$chat_id'");

                    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                    $file_arr = explode("###", file_get_contents("vaqt/$chat_id.txt"));
                    array_pop($file_arr);
                    $stadion_id = explode(",", file_get_contents("vaqt/$chat_id.txt"))[0];
                    $stdName = $connection->query("select name from stadions where id = '$stadion_id'")->fetch_assoc()['name'];
                    $day = explode(",", $file_arr[0])[2];

                    $textconf = "Siz $stdName stadionidan $day kuni uchun quyidagi vaqtlarni band qilmoqchimisiz?\n\n";
                    foreach ($file_arr as $value) {
                        $valuArr = explode(",", $value);
                        $vaqt = $valuArr[1];
                        $kun = $valuArr[2];
                        $vaqtNow = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$vaqt'")->num_rows;
                        if ($vaqtNow == 0) {
                            $textconf .= "⏱ $vaqt\n";
                        }
                    }
                    $textconf .= "\nAgar ma'lumotlar to'g'ri bo'lsa << Tasdiqlash >> tugmasini bosing.";

                    $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Tasdiqlash ✅", "callback_data" => "vaqtConfirm"], ['text' => "Bekor qilish ❌", "callback_data" => "vaqtCancle"]]]);
                    $bot->sendMessage($chat_id, $textconf, null, false, null, $btn);
                    unlink("vaqt/$chat_id.txt");
                    $bot->deleteMessage($chat_id, $messageId);
                } else {
                    $bot->sendMessage($chat_id, "❗️ Iltimos, telefon raqamni namunadagidek kiriting(Na'muna: 998338885544)");
                }
            }

        } catch (Exception $exception) {
            //
        }
    });

$bot->run();