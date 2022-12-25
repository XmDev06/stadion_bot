<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'config.php';

$botToken = "5812515378:AAF8J9hvRbx5EULNJZ3I49jNg5slJIgIJT0";
// https://api.telegram.org/bot5812515378:AAF8J9hvRbx5EULNJZ3I49jNg5slJIgIJT0/setWebhook?url=https://0935-213-230-100-150.eu.ngrok.io/projects/stadion_bot/index.php

/**
 * @var $bot \TelegramBot\Api\Client | \TelegramBot\Api\BotApi
 */

$bot = new \TelegramBot\Api\Client($botToken);

$bot->command('start', static function (\TelegramBot\Api\Types\Message $message) use ($removeButton, $connection, $bot) {
    try {
        $chat_id = $message->getChat()->getId();
        $firstname = $message->getChat()->getFirstName();

        $is_verified = $connection->query("select * from users where chat_id = '$chat_id'")->num_rows;
        if ($is_verified == 0) {
            $bot->sendMessage($chat_id, "👋 Assalomu alaykum botga xush kelibsiz!\nIltimos botga kirish uchun telefon raqamingizni kiriting.", null, false, false, $removeButton);
        } else {
            adminMenu($chat_id, $bot, $connection);
        }
    } catch (Exception $exception) {
        //
    }
});


$bot->callbackQuery(static function (\TelegramBot\Api\Types\CallbackQuery $callbackquery) use ($vaqtlar_massiv, $connection, $bot) {
    try {

        $chatId = $callbackquery->getMessage()->getChat()->getId();
        $data = $callbackquery->getData();
        $messageId = $callbackquery->getMessage()->getMessageId();
        $userId = $connection->query("select id from users where chat_id='$chatId'")->fetch_assoc()['id'];

        if ($data == "createStd") {
            $bot->sendMessage($chatId, "Yangi stadion yaratish uchun Stadion nomini kiriting: ", null, false, null, new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]], false, true));
            $connection->query("update users set status = 'create_stadion' where chat_id='$chatId'");
        }
        if (strpos($data, "stadion") !== false) {
            $stadion_id = explode("_", $data)[1];
            $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
            $ega = $connection->query("select name from users where id = '$stadion[5]'")->fetch_assoc()["name"];
            $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[6]'")->fetch_assoc()['name'];
            $tuman = $connection->query("select name from tumanlars where id = '$stadion[7]'")->fetch_assoc()['name'];
            $qfy = $connection->query("select name from qfy where id = '$stadion[8]'")->fetch_assoc()['name'];


            $phone_2 = '';
            if ($stadion[3] !== null) {
                $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
            }
            $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani, $qfy shahri/qishlog'i\n\n⏱ Soatlik narxi:  $stadion[4]\n ";

            $button = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => '⏰ Stadion vaqtlari', 'callback_data' => "stdVaqtlari_$stadion_id"]], [['text' => '⚙️ Tahrirlash', 'callback_data' => "stdEdit_$stadion_id"], ['text' => "⛔️ Stadionni o'chirish", 'callback_data' => "deleteStd_boshMenu_$stadion_id"]], [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]]);
            $bot->sendMessage($chatId, $text, null, false, false, $button);
            $bot->deleteMessage($chatId, $messageId);
        }
        if ($data == "phone_number_2") {
            $tumanlar = $connection->query("select * from tumanlars where viloyat_id = 1")->fetch_all();

            $button = [[]];
            foreach ($tumanlar as $tuman) {
                $button[0][] = ["text" => "$tuman[1]", "callback_data" => "tuman_$tuman[0]"];
            }
            $button = array_chunk($button[0], 2);
            $tuman_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

            $bot->sendMessage($chatId, "Tumanni tanlang 👇👇👇 ", null, false, null, $tuman_btn);
            $connection->query("update users set status = 'tuman' where chat_id='$chatId'");

            $myfile = fopen("session/$chatId.txt", "a") or die("Unable to open file!");
            fwrite($myfile, "phone_2=null;");
            fclose($myfile);
            $bot->deleteMessage($chatId, $messageId);
        }
        if (strpos($data, "tuman_") !== false) {
            $tuman_id = explode("_", $data)[1];
            $qfy = $connection->query("select * from qfy where tuman_id = '$tuman_id'")->fetch_all();

            $button = [[]];
            foreach ($qfy as $item) {
                $button[0][] = ["text" => "$item[1]", "callback_data" => "qfy_$item[0]"];
            }
            $button = array_chunk($button[0], 2);
            $qfy_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);
            $bot->sendMessage($chatId, 'QFYni tanlang !!!', null, false, null, $qfy_btn);
            $bot->deleteMessage($chatId, $messageId);

            $myfile = fopen("session/$chatId.txt", "a");
            fwrite($myfile, "tuman_id=" . $tuman_id . ";");
            fwrite($myfile, "viloyat_id=1;");
            fclose($myfile);

        }
        if (strpos($data, 'qfy_') !== false) {
            $qfy_id = explode('_', $data)[1];
            $myfile = fopen("session/$chatId.txt", "a");
            fwrite($myfile, "qfy_id=" . $qfy_id . ";");
            fclose($myfile);
            $bot->sendMessage($chatId, "Stadion lacatsiyasini tashlang 📍", null, false, null, new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => "Locatsiyani yuborish 📍", "request_location" => true]]], true, true));
            $bot->deleteMessage($chatId, $messageId);
            $connection->query("update users set status = 'location' where chat_id='$chatId'");
        }

        if ($data == 'tasdiqlash') {
            $data = file_get_contents("session/$chatId.txt");
            $data_massiv = explode(';', $data);
            $user_id = '';
            $key = '';
            $value = '';
            foreach ($data_massiv as $item) {
                $keylar = explode('=', $item);
                $key .= $keylar[0] . ",";
                if ($keylar[1] == "null") {
                    $value .= $keylar[1] . ",";
                } else {
                    $value .= '"' . $keylar[1] . '",';
                }
            }
            $key = substr($key, 0, -2);
            $value = substr($value, 0, -4);

            $test = $connection->query("insert into stadions ($key) values ($value)");

            if ($test) {

                $stadions = $connection->query("select * from stadions where user_id = '$userId'")->fetch_all();

                $button = [[]];
                foreach ($stadions as $stadion) {
                    $button[0][] = ["text" => "🏟 $stadion[1]", "callback_data" => "stadion_$stadion[0]"];
                }
                array_push($button[0], ["text" => '🆕 Stadion yaratish', "callback_data" => "createStd"]);
                $button = array_chunk($button[0], 2);

                $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);
                $bot->sendMessage($chatId, "Bo'limlardan birini tanlang", null, false, false, $b);

                unlink("session/$chatId.txt");
            } else {
                $bot->sendMessage($chatId, "Bog'lanishdagi xatolik");
            }
        }
        if (strpos($data, 'deleteStd_') !== false) {
            $stadion_id = explode('_', $data)[2];
            $delete_test = $connection->query("delete from stadions where id = '$stadion_id'");
            if ($delete_test) {
                $bot->sendMessage($chatId, "Stadion o'chirib tashlandi ✅");
                $connection->query("update users set status = 'stadion' where chat_id='$chatId'");
            } else {
                $bot->sendMessage($chatId, "Stadionni o'chirib bo'lmadi dasturchi bilan bog'laning ✅");
            }
        }
        if (strpos($data, 'boshMenu') !== false) {
            $stadions = $connection->query("select * from stadions where user_id = '$userId'")->fetch_all();

            $button = [[]];
            foreach ($stadions as $stadion) {
                $button[0][] = ["text" => "🏟 $stadion[1]", "callback_data" => "stadion_$stadion[0]"];
            }
            array_push($button[0], ["text" => '🆕 Stadion yaratish', "callback_data" => "createStd"]);
            $button = array_chunk($button[0], 2);

            $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);
            $bot->sendMessage($chatId, "Bo'limlardan birini tanlang", null, false, false, $b);
            $bot->deleteMessage($chatId, $messageId);
        }


        ////////// Stadion EDIT  /////////
        if (strpos($data, 'stdEdit') !== false) {
            $stadion_id = explode("_", $data)[1];
            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                [['text' => 'Nomi 🔤', 'callback_data' => "name_$stadion_id"], ['text' => 'Narxi 💵', 'callback_data' => "narx_$stadion_id"]],
                [['text' => 'Tel raqam 1 📲', 'callback_data' => "phone1_$stadion_id"], ['text' => 'Tel raqam 2 📲', 'callback_data' => "phone2_$stadion_id"]],
                [['text' => 'Mo\'ljal 📍', 'callback_data' => "manzil_$stadion_id"], ['text' => 'Locatsiya 🗺', 'callback_data' => "location_$stadion_id"]],
                [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]
            ]);

            $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
            var_dump($stadion);
            $ega = $connection->query("select name from users where id = '$stadion[6]'")->fetch_assoc()["name"];
            $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[7]'")->fetch_assoc()['name'];
            $tuman = $connection->query("select name from tumanlars where id = '$stadion[8]'")->fetch_assoc()['name'];
            var_dump($stadion);
            $phone_2 = '';
            if ($stadion[3] !== null) {
                $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
            }
            $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $stadion[4]\n\n⏱ Soatlik narxi:  $stadion[5]\n\nTahrirlash uchun quyidagi bo'limlardan birini tanlang 👇👇👇 ";


            $bot->sendMessage($chatId, $text, null, false, null, $btn);
            $bot->deleteMessage($chatId, $messageId);
        }
        if (strpos($data, 'name_') !== false) {
            $id = explode('_', $data)[1];
            $bot->sendMessage($chatId, 'Yangi nom kiriting');
            $nameEdit = "nameEdit_$id";
            $connection->query("update users set status = '$nameEdit' where chat_id='$chatId'");
        }
        if (strpos($data, 'narx_') !== false) {
            $id = explode('_', $data)[1];
            $bot->sendMessage($chatId, 'Yangi summani kiriting');
            $nameEdit = "narxEdit_$id";
            $connection->query("update users set status = '$nameEdit' where chat_id='$chatId'");
        }
        if (strpos($data, 'phone1_') !== false) {
            $id = explode('_', $data)[1];
            $bot->sendMessage($chatId, 'Yangi telefon raqamini kiriting. (Namuna: 998991112233)');
            $nameEdit = "phone1Edit_$id";
            $connection->query("update users set status = '$nameEdit' where chat_id='$chatId'");
        }
        if (strpos($data, 'phone2_') !== false) {
            $id = explode('_', $data)[1];
            $bot->sendMessage($chatId, 'Yangi ikkinchi telefon raqamini kiriting. (Namuna: 998991112233)');
            $nameEdit = "phone2Edit_$id";
            $connection->query("update users set status = '$nameEdit' where chat_id='$chatId'");
        }
        if (strpos($data, 'manzil_') !== false) {
            $id = explode('_', $data)[1];
            $bot->sendMessage($chatId, "Yangi mo'ljalni kiriting");
            $nameEdit = "manzilEdit_$id";
            $connection->query("update users set status = '$nameEdit' where chat_id='$chatId'");
        }
        if (strpos($data, 'location_') !== false) {
            $id = explode('_', $data)[1];
            $bot->sendMessage($chatId, "Yangi locatsiyani jo'nating");
            $nameEdit = "locationEdit_$id";
            $connection->query("update users set status = '$nameEdit' where chat_id='$chatId'");
        }
        ////////// Stadion EDIT end  /////////

        ///////////   STADION vaqtlari  START //////////////////

        if (strpos($data, "stdVaqtlari_") !== false) {
            $stadion_id = explode("_", $data)[1];
            $baza = $connection->query("select * from vaqtlar where stadion_id = '$stadion_id'");

            $now_date = date('Y/m/d');
            $days = [];
            for ($i = 0; $i < 15; $i++) {
                $kun = date('d.m.y', strtotime("+$i day", strtotime($now_date)));
                $kun_call = date('d-m-Y', strtotime("+$i day", strtotime($now_date)));
                $days[] = ['text' => "$kun", 'callback_data' => $stadion_id . "_Stdday_$kun_call"];
            }
            $days[] = ['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu'];
            $days_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(array_chunk($days, 3));

            $bot->sendMessage($chatId, "Band qilingan vaqtlarni ko'rish uchun kunlardan birini tanlang 👇", false, false, null, $days_btn);
            $bot->deleteMessage($chatId, $messageId);
        }
        if (strpos($data, "Stdday_") !== false) {
            $stadion_id = explode("_", $data)[0];
            $kun = explode("_", $data)[2];
            $vaqtlar = $connection->query("select * from vaqtlar where stadion_id = '$stadion_id' and kun = '$kun' order by vaqt")->fetch_all();
            $gtime = date("H");

            if (count($vaqtlar) != 0) {
                $no = 0;
                foreach ($vaqtlar as $key => $vaqt) {
                    $buyurtmachi = $connection->query("select * from consumer where id='$vaqt[2]'")->fetch_assoc();
                    $bname = $buyurtmachi['name'];
                    $bphone = $buyurtmachi['phone'];
                    $soat = $vaqt[3];

                    $text = "⏰ Vaqt: $soat\n👨‍💼 Buyurtmachining ismi: $bname\n📲 Telefon raqami: $bphone";
                    if ($kun == date('d-m-Y')) {
                        $soat = explode(":", explode(" - ", $soat)[0])[0];

                        if ($gtime - 1 <= $soat) {
                            if (($key + 1) == count($vaqtlar)) {
                                $buttonEdit = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                                    [
                                        ['text' => "Vaqt band qilish 🖇", 'callback_data' => "buyTime_$stadion_id" . "_$kun"]
                                    ],
                                    [
                                        ['text' => "vaqtni bekor qilish ❌", 'callback_data' => "deleteVaqt_$vaqt[0]"],
                                        ['text' => "Orqaga 🔙", 'callback_data' => "stdVaqtlari_$stadion_id"]]
                                ]);
                                $bot->sendMessage($chatId, $text, false, false, null, $buttonEdit);
                            } else {
                                $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "vaqtni bekor qilish ❌", 'callback_data' => "deleteVaqt_$vaqt[0]"]]]);
                                $bot->sendMessage($chatId, $text, null, false, null, $b);
                            }
                            $no = 0;
                        } else {
                            $no += 1;
                        }

                    } else {
                        if (($key + 1) == count($vaqtlar)) {
                            $bot->sendMessage($chatId, $text, false, false, null, $buttonEdit);
                        } else {
                            $bot->sendMessage($chatId, $text);
                        }
                    }
                }
                if ($no != 0) {
                    $home = new  \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Orqaga 🔙", 'callback_data' => "stdVaqtlari_$stadion_id"]]]);
                    $bot->sendMessage($chatId, "Band qilingan vaqtlar yo'q", null, false, null, $home);
                }

            } else {
                $home = new  \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Orqaga 🔙", 'callback_data' => "stdVaqtlari_$stadion_id"]]]);
                $bot->sendMessage($chatId, "Bu kun uchun <b>hech qanday vaqt band qilinmagan!</b>", "HTML", false, null, $home);
            }
            $bot->deleteMessage($chatId, $messageId);
        }
        if (strpos($data, "buyTime_") !== false) {
            $stadion_id = explode("_", $data)[1];
            $kun = explode("_", $data)[2];
            $consumer = "consumerPhone_$stadion_id" . "_$kun";
            $connection->query("update users set status = '$consumer' where chat_id='$chatId'");
            $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Orqaga 🔙", 'callback_data' => "stdVaqtlari_$stadion_id"]]]);
            $bot->sendMessage($chatId, "Vaqt band qilish uchun buyurtmachining telefon raqamini kiriting (Na'muna: 998993337744):", null, false, null, $b);
            $bot->deleteMessage($chatId, $messageId);
        }
        if (strpos($data, "deleteVaqt_") !== false){
            $vaqt_id = explode("_", $data)[1];
            $test = $connection->query("DELETE FROM `vaqtlar` WHERE id = '$vaqt_id'");
            if ($test){
                $bot->sendMessage($chatId,"Vaqt bekor qilindi ✅");
                $bot->deleteMessage($chatId,$messageId);
                adminMenu($chatId,$bot,$connection);
            }
        }

        ///////////   STADION vaqtlari  END //////////////////


        ////////// Qayta zakaz qilish /////////////
        if (strpos($data, 'qaytaZakaz_') !== false) {
            $phone = explode('_', $data)[1];
            $kun = explode('_', $data)[2];
            $stadion_id = explode('_', $data)[3];
            $oldbuyurtmachiId = $connection->query("select * from consumer where phone = '$phone'")->fetch_assoc()['id'];

            $vaqtMassiv = [];
            if (date('d-m-Y') == $kun) {
                foreach ($vaqtlar_massiv as $key => $item) {
                    $vaqtNow = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$item' and stadion_id = '$stadion_id'")->num_rows;
                    $e = explode(":", explode(" - ", $item)[0])[0];
                    $v = date("H") <= $e || $e == 1 || $e == 2;

                    if ($vaqtNow == 0 && $v) {
                        $vaqtMassiv[] = ['text' => "$item", "callback_data" => "NewVaqt" . "_$kun" . "_$stadion_id" . "_$oldbuyurtmachiId" . "_$item"];
                    }
                }
            } else {
                foreach ($vaqtlar_massiv as $key => $item) {
                    $vaqtNow = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$item' and stadion_id = '$stadion_id'")->num_rows;
                    if ($vaqtNow == 0) {
                        $vaqtMassiv[] = ['text' => "$item", "callback_data" => "NewVaqt" . "_$kun" . "_$stadion_id" . "_$oldbuyurtmachiId" . "_$item"];
                    }
                }
            }


            $vaqtMassiv = array_chunk($vaqtMassiv, '3');
            $vaqtMassiv[] = [['text' => "Orqaga 🔙", 'callback_data' => "stdVaqtlari_$stadion_id"]];

            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($vaqtMassiv);
            $bot->sendMessage($chatId, 'Qaysi vaqtni buyurtma qildi ?', false, null, false, $btn);
            $bot->deleteMessage($chatId, $messageId);
        }
        if (strpos($data, 'NewVaqt_') !== false) {
            $kun = explode('_', $data)[1];
            $stadion_id = explode('_', $data)[2];
            $oldbuyurtmachiId = explode('_', $data)[3];;
            $vaqt = explode('_', $data)[4];
            $myfile = fopen("vaqt/$chatId.txt", "a") or die("Unable to open file!");

            $vaqtlar_text = [];
            $text_file_massiv = explode("###", file_get_contents("vaqt/$chatId.txt"));
            foreach ($text_file_massiv as $value) {
                $vaqtlar_text[] = explode(",", $value)[2];
            }
            if (!in_array($vaqt, $vaqtlar_text)) {
                fwrite($myfile, "$stadion_id,$oldbuyurtmachiId,$vaqt,$kun###");
                fclose($myfile);
            }


            $vaqtMassiv = [];
            foreach ($vaqtlar_massiv as $key => $item) {
                $vaqtNow = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$item'")->num_rows;
                if (date('d-m-Y') == $kun) {
                    $e = explode(":", explode(" - ", $item)[0])[0];
                    $v = date("H") <= $e || $e == 1 || $e == 2;

                    if ($vaqtNow == 0 && $v) {
                        if (in_array($item, $vaqtlar_text) || $vaqt == $item) {
                            $vaqtMassiv[] = ['text' => "$item ✅", "callback_data" => "NewVaqt" . "_$kun" . "_$stadion_id" . "_$oldbuyurtmachiId" . "_$item"];
                        } else {
                            $vaqtMassiv[] = ['text' => "$item", "callback_data" => "NewVaqt" . "_$kun" . "_$stadion_id" . "_$oldbuyurtmachiId" . "_$item"];
                        }
                    }
                } else {
                    if ($vaqtNow == 0) {
                        if (in_array($item, $vaqtlar_text) || $vaqt == $item) {
                            $vaqtMassiv[] = ['text' => "$item ✅", "callback_data" => "NewVaqt" . "_$kun" . "_$stadion_id" . "_$oldbuyurtmachiId" . "_$item"];
                        } else {
                            $vaqtMassiv[] = ['text' => "$item", "callback_data" => "NewVaqt" . "_$kun" . "_$stadion_id" . "_$oldbuyurtmachiId" . "_$item"];
                        }
                    }
                }
            }

            $vaqt_chunk = array_chunk($vaqtMassiv, '3');
            $vaqt_chunk[] = [['text' => "Tasdiqlash ✅", "callback_data" => "vaqtConfirm"], ['text' => "Bekor qilish ❌", "callback_data" => "vaqtCancle_$stadion_id"]];
            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($vaqt_chunk);

            $bot->sendMessage($chatId, "Agar boshqa buyurtmangiz bo'lmasa tasdiqlash tugmasini bosing. Agar bo'lsa yana vaqt tanlang", false, null, false, $btn);
            $bot->deleteMessage($chatId, $messageId);
        }
        if ($data == "vaqtConfirm") {
            $file_arr = explode("###", file_get_contents("vaqt/$chatId.txt"));
            $stadion_id = explode(",", file_get_contents("vaqt/$chatId.txt"))[0];
            array_pop($file_arr);
            foreach ($file_arr as $value) {
                $valuArr = explode(",", $value);
                $stadion_id = $valuArr[0];
                $oldbuyurtmachiId = $valuArr[1];
                $vaqt = $valuArr[2];
                $kun = $valuArr[3];
                $vaqtNow = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$vaqt'")->num_rows;
                if ($vaqtNow == 0) {
                    $test = $connection->query("insert into vaqtlar (stadion_id, consumer, vaqt, kun) values ('$stadion_id', '$oldbuyurtmachiId', '$vaqt', '$kun')");
                }
            }
            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Orqaga 🔙", "callback_data" => "stdVaqtlari_$stadion_id"], ['text' => "Bosh Menu 🏘", "callback_data" => "boshMenu"]]]);
            $bot->sendMessage($chatId, "Siz tanlagan vaqtlar band qilindi", null, false, null, $btn);
            unlink("vaqt/$chatId.txt");
            $bot->deleteMessage($chatId, $messageId);
        }
        if (strpos($data, "vaqtCancle_") !== false) {
            $stadion_id = explode("_", $data)[1];
            unlink("vaqt/$chatId.txt");
            $bot->sendMessage($chatId, "Tanlangan vaqtlar bekor qilindi!", null, false, null, new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Bosh Menu 🏘", "callback_data" => "boshMenu"], ['text' => "Orqaga 🔙", 'callback_data' => "stadion_$stadion_id"]]]));
            $bot->deleteMessage($chatId, $messageId);
        }

    } catch (Exception $exception) {
    }
});


$bot->on(static function () {
},
    static function (\TelegramBot\Api\Types\Update $update) use ($removeButton, $vaqtlar_massiv, $connection, $bot) {

        try {
            $chat_id = $update->getMessage()->getChat()->getId();
            $text = $update->getMessage()->getText();
            $messageId = $update->getMessage()->getMessageId();
            $user_id = $connection->query("select id from users where chat_id='$chat_id'")->fetch_assoc()['id'];
            $is_verified = $connection->query("select * from users where chat_id='$chat_id'")->num_rows;
            $status = $connection->query("select status from users where chat_id='$chat_id'")->fetch_assoc()['status'];


            if ($text == "Bosh menyu 🏘") {
                adminMenu($chat_id, $bot, $connection);
            }

            ///////////////// LOGIN //////////////
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
                    adminMenu($chat_id, $bot, $connection);
                } else {
                    $bot->sendMessage($chat_id, "❗️Parolni noto'g'ri, qaytadan urinib ko'ring");
                }
            }
            ///////////////// LOGIN END //////////////

            /////////////////  CREAT STADION START //////////////////
            if ($status == "create_stadion" && $text) {
                $filter = preg_match("/^[a-zA-Z '`‘]*$/", $text);
                if ($filter === 1) {
                    $std_unique = $connection->query("select name from stadions where name = '$text' and user_id = '$user_id'")->num_rows;

                    if ($std_unique == 0) {

                        $myfile = fopen("session/$chat_id.txt", "w") or die("Unable to open file!");
                        fwrite($myfile, "name=" . $text . ";");
                        fclose($myfile);
                        $connection->query("update users set status = 'phone_number' where chat_id='$chat_id'");
                        $bot->sendMessage($chat_id, "Bog'lanish uchun telefon raqam kiriting ☎️\n(Na'muna: 998991112233)", null, false, null, new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]], false, true));
                    } else {
                        $bot->sendMessage($chat_id, "❗Sizda ushbu nomdagi stadion mavjud");
                    }
                } else {
                    if ($text !== "Bosh menyu 🏘") {
                        $bot->sendMessage($chat_id, "❗Stadion nomida faqat harflar qatnashgan so'zlardan foydalaning");
                    }
                }
            }
            if ($status == 'phone_number' && $text) {
                $filter_number = preg_match("/^[0-9]{12,12}/", $text);
                if ($filter_number === 1) {
                    $next_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "O'tkazib yuborish ⏭", "callback_data" => 'phone_number_2']]]);
                    $bot->sendMessage($chat_id, "Qo'shimcha telefon raqam mavjud bo'lsa kiriting☎️\n(Na'muna: 998991112233)", null, false, null, $next_btn);
                    $connection->query("update users set status = 'phone_number_2' where chat_id='$chat_id'");

                    $myfile = fopen("session/$chat_id.txt", "a");
                    fwrite($myfile, "phone=" . $text . ";");
                    fclose($myfile);
                } else {
                    if ($text !== "Bosh menyu 🏘") {
                        $bot->sendMessage($chat_id, "❗️ Iltimos, telefon raqamni namunadagidek kiriting");
                    }
                }
            }
            if ($status == 'phone_number_2' && $text) {
                $filter_number = preg_match("/^[0-9]{12,12}/", $text);
                if ($filter_number === 1) {
                    $tumanlar = $connection->query("select * from tumanlars where viloyat_id = 1")->fetch_all();

                    $button = [[]];
                    foreach ($tumanlar as $tuman) {
                        $button[0][] = ["text" => "$tuman[1]", "callback_data" => "tuman_$tuman[0]"];
                    }
                    $button = array_chunk($button[0], 2);
                    $button[0] = [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']];
                    $tuman_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

                    $bot->sendMessage($chat_id, "Tumanni tanlang 👇👇👇 ", null, false, null, $tuman_btn);
                    $connection->query("update users set status = 'tuman' where chat_id='$chat_id'");
                    $myfile = fopen("session/$chat_id.txt", "a");
                    fwrite($myfile, "phone_2=" . $text . ";");
                    fclose($myfile);
                } else {
                    if ($text !== "Bosh menyu 🏘") {
                        $bot->sendMessage($chat_id, "❗️ Iltimos, telefon raqamni namunadagidek kiriting");
                    }
                }
            }
            if ($status == 'location') {
                $latitude = $update->getMessage()->getLocation()->getLatitude();
                $longitude = $update->getMessage()->getLocation()->getLongitude();
                $myfile = fopen("session/$chat_id.txt", "a");
                fwrite($myfile, "latitude=" . $latitude . ";");
                fwrite($myfile, "longitude=" . $longitude . ";");
                fclose($myfile);

                $bot->sendMessage($chat_id, "Stadion narxini so'mda kiriting 💰 (Na'muna: 50000)", null, false, null, $removeButton);
                $connection->query("update users set status = 'narx' where chat_id='$chat_id'");
            }
            if ($status == "narx") {
                $filter_narx = preg_match("/^[0-9]/", $text);
                if ($filter_narx === 1) {
                    $myfile = fopen("session/$chat_id.txt", "a");
                    fwrite($myfile, "narxi=" . $text . ";");
                    fwrite($myfile, "user_id=" . $user_id . ";");
                    fclose($myfile);
                    $tuman_id = '';
                    $qfy_id = '';
                    $name = '';
                    $phone = '';
                    $phone_2 = '';
                    $viloyat = '';
                    $narx = '';

                    $data = file_get_contents("session/$chat_id.txt");
                    $data_massiv = explode(';', $data);

                    foreach ($data_massiv as $item) {
                        $keylar = explode('=', $item);
                        if ($keylar[0] == 'name') {
                            $name .= $keylar[1];
                        }
                        if ($keylar[0] == 'qfy_id') {
                            $qfy_id .= $keylar[1];
                        }
                        if ($keylar[0] == 'phone') {
                            $phone .= $keylar[1];
                        }
                        if ($keylar[0] == 'phone_2' && $keylar[1] !== "null") {
                            $phone_2 .= $keylar[1];
                        }
                        if ($keylar[0] == 'viloyat_id') {
                            $viloyat = $connection->query("select name from viloyatlars where id = $keylar[1]")->fetch_assoc()['name'];
                        }
                        if ($keylar[0] == 'narxi') {
                            $narx .= $keylar[1];
                        }
                        if ($keylar[0] == 'tuman_id') {
                            $tuman_id .= $keylar[1];
                        }
                    }
                    $tuman = $connection->query("select name from tumanlars where id = $tuman_id")->fetch_assoc()['name'];
                    $qfy = $connection->query("select name from qfy where id = $qfy_id")->fetch_assoc()['name'];

                    $phone_number_2 = '';
                    if ($phone_2 !== '') {
                        $phone_number_2 .= "📞 Bog'lanish uchun raqam 2: +$phone_2\n";
                    }
                    $text = "🏟 Stadion nomi:  $name\n‍📞 Bog'lanish uchun raqam: +$phone\n$phone_number_2\n📍 Stadion joylashgan joy: $tuman tumani\n📍 Mo'ljal: $qfy QFY\n\n⏱ Soatlik narxi:  $narx\n ";


                    $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Qayta to'ldirish ♻️", "callback_data" => "createStd"], ['text' => "Tasdiqlash 👍", "callback_data" => "tasdiqlash"]], [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]]);
                    $bot->sendMessage($chat_id, $text, null, false, null, $btn);
                    $connection->query("update users set status = 'tasdiqlash' where chat_id='$chat_id'");

                } else {
                    if ($text !== "Bosh menyu 🏘") {
                        $bot->sendMessage($chat_id, "❗️ Iltimos, stadion summasini namunadagidek kiriting");
                    }
                }
            }
            /////////////////  CREAT STADION END //////////////////

            /////////////////  Edit STADION START //////////////////
            if (strpos($status, 'nameEdit') !== false) {
                $stadion_id = explode('_', $status)[1];
                $filter = preg_match("/^[a-zA-Z '`‘]*$/", $text);
                $std_unique = $connection->query("select name from stadions where name = '$text' and user_id = $user_id")->num_rows;
                if ($filter === 1 && $std_unique == 0) {
                    $connection->query("update stadions set name = '$text' where id = $stadion_id");
                    $connection->query("update users set status = '0' where chat_id = $chat_id");
                    $bot->sendMessage($chat_id, "Sizning stadioningiz nomi muaffaqiyatli o'zgartirildi");

                    $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                        [['text' => 'Nomi 🔤', 'callback_data' => "name_$stadion_id"], ['text' => 'Narxi 💵', 'callback_data' => 'narx_edit']],
                        [['text' => 'Tel raqam 1 📲', 'callback_data' => 'phone_edit1'], ['text' => 'Tel raqam 2 📲', 'callback_data' => 'phone_edit2']],
                        [['text' => 'Mo\'ljal 📍', 'callback_data' => 'manzil_edit'], ['text' => 'Locatsiya 🗺', 'callback_data' => 'location_edit']],
                        [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]
                    ]);

                    $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
                    var_dump($stadion);
                    $ega = $connection->query("select name from users where id = '$stadion[6]'")->fetch_assoc()["name"];
                    $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[7]'")->fetch_assoc()['name'];
                    $tuman = $connection->query("select name from tumanlars where id = '$stadion[8]'")->fetch_assoc()['name'];

                    $phone_2 = '';
                    if ($stadion[3] !== null) {
                        $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
                    }
                    $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $stadion[4]\n\n⏱ Soatlik narxi:  $stadion[5]\n\nTahrirlash uchun quyidagi bo'limlardan birini tanlang 👇👇👇 ";
                    $bot->sendMessage($chat_id, $text, null, false, null, $btn);
                }
            }
            if (strpos($status, 'narxEdit_') !== false) {
                $stadion_id = explode('_', $status)[1];
                $filter = preg_match("/^[a-zA-Z]/", $text);
                if ($filter === 0) {
                    $connection->query("update stadions set narxi = '$text' where id = $stadion_id");
                    $connection->query("update users set status = '0' where chat_id = $chat_id");
                    $bot->sendMessage($chat_id, "Sizning stadioningiz narxi muaffaqiyatli o'zgartirildi");

                    $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                        [['text' => 'Nomi 🔤', 'callback_data' => "name_$stadion_id"], ['text' => 'Narxi 💵', 'callback_data' => "narx_$stadion_id"]],
                        [['text' => 'Tel raqam 1 📲', 'callback_data' => "phone1_$stadion_id"], ['text' => 'Tel raqam 2 📲', 'callback_data' => "phone2_$stadion_id"]],
                        [['text' => 'Mo\'ljal 📍', 'callback_data' => "manzil_$stadion_id"], ['text' => 'Locatsiya 🗺', 'callback_data' => "location_$stadion_id"]],
                        [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]
                    ]);

                    $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
                    var_dump($stadion);
                    $ega = $connection->query("select name from users where id = '$stadion[6]'")->fetch_assoc()["name"];
                    $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[7]'")->fetch_assoc()['name'];
                    $tuman = $connection->query("select name from tumanlars where id = '$stadion[8]'")->fetch_assoc()['name'];

                    $phone_2 = '';
                    if ($stadion[3] !== null) {
                        $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
                    }
                    $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $stadion[4]\n\n⏱ Soatlik narxi:  $stadion[5]\n\nTahrirlash uchun quyidagi bo'limlardan birini tanlang 👇👇👇 ";
                    $bot->sendMessage($chat_id, $text, null, false, null, $btn);
                }
            }
            if (strpos($status, 'phone1Edit_') !== false) {
                $stadion_id = explode('_', $status)[1];
                $filter = preg_match("/^[0-9]{12,12}/", $text);
                if ($filter === 1) {
                    $connection->query("update stadions set phone = '$text' where id = $stadion_id");
                    $connection->query("update users set status = '0' where chat_id = $chat_id");
                    $bot->sendMessage($chat_id, "Sizning stadioningiz telefon raqami muaffaqiyatli o'zgartirildi");

                    $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                        [['text' => 'Nomi 🔤', 'callback_data' => "name_$stadion_id"], ['text' => 'Narxi 💵', 'callback_data' => "narx_$stadion_id"]],
                        [['text' => 'Tel raqam 1 📲', 'callback_data' => "phone1_$stadion_id"], ['text' => 'Tel raqam 2 📲', 'callback_data' => "phone2_$stadion_id"]],
                        [['text' => 'Mo\'ljal 📍', 'callback_data' => "manzil_$stadion_id"], ['text' => 'Locatsiya 🗺', 'callback_data' => "location_$stadion_id"]],
                        [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]
                    ]);

                    $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
                    $ega = $connection->query("select name from users where id = '$stadion[6]'")->fetch_assoc()["name"];
                    $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[7]'")->fetch_assoc()['name'];
                    $tuman = $connection->query("select name from tumanlars where id = '$stadion[8]'")->fetch_assoc()['name'];

                    $phone_2 = '';
                    if ($stadion[3] !== null) {
                        $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
                    }
                    $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $stadion[4]\n\n⏱ Soatlik narxi:  $stadion[5]\n\nTahrirlash uchun quyidagi bo'limlardan birini tanlang 👇👇👇 ";
                    $bot->sendMessage($chat_id, $text, null, false, null, $btn);
                }
            }
            if (strpos($status, 'phone2Edit_') !== false) {
                $stadion_id = explode('_', $status)[1];
                $filter = preg_match("/^[0-9]{12,12}/", $text);
                if ($filter === 1) {
                    $connection->query("update stadions set phone_2 = '$text' where id = $stadion_id");
                    $connection->query("update users set status = '0' where chat_id = $chat_id");
                    $bot->sendMessage($chat_id, "Sizning stadioningiz ikkinchi telefon raqami muaffaqiyatli o'zgartirildi");

                    $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                        [['text' => 'Nomi 🔤', 'callback_data' => "name_$stadion_id"], ['text' => 'Narxi 💵', 'callback_data' => "narx_$stadion_id"]],
                        [['text' => 'Tel raqam 1 📲', 'callback_data' => "phone1_$stadion_id"], ['text' => 'Tel raqam 2 📲', 'callback_data' => "phone2_$stadion_id"]],
                        [['text' => 'Mo\'ljal 📍', 'callback_data' => "manzil_$stadion_id"], ['text' => 'Locatsiya 🗺', 'callback_data' => "location_$stadion_id"]],
                        [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]
                    ]);

                    $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
                    $ega = $connection->query("select name from users where id = '$stadion[6]'")->fetch_assoc()["name"];
                    $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[7]'")->fetch_assoc()['name'];
                    $tuman = $connection->query("select name from tumanlars where id = '$stadion[8]'")->fetch_assoc()['name'];

                    $phone_2 = '';
                    if ($stadion[3] !== null) {
                        $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
                    }
                    $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $stadion[4]\n\n⏱ Soatlik narxi:  $stadion[5]\n\nTahrirlash uchun quyidagi bo'limlardan birini tanlang 👇👇👇 ";
                    $bot->sendMessage($chat_id, $text, null, false, null, $btn);
                }
            }
            if (strpos($status, 'manzilEdit_') !== false) {
                $stadion_id = explode('_', $status)[1];

                $connection->query("update stadions set moljal = '$text' where id = $stadion_id");
                $connection->query("update users set status = '0' where chat_id = $chat_id");
                $bot->sendMessage($chat_id, "Sizning stadioningiz manzili muaffaqiyatli o'zgartirildi");

                $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                    [['text' => 'Nomi 🔤', 'callback_data' => "name_$stadion_id"], ['text' => 'Narxi 💵', 'callback_data' => "narx_$stadion_id"]],
                    [['text' => 'Tel raqam 1 📲', 'callback_data' => "phone1_$stadion_id"], ['text' => 'Tel raqam 2 📲', 'callback_data' => "phone2_$stadion_id"]],
                    [['text' => 'Mo\'ljal 📍', 'callback_data' => "manzil_$stadion_id"], ['text' => 'Locatsiya 🗺', 'callback_data' => "location_$stadion_id"]],
                    [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]
                ]);

                $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
                $ega = $connection->query("select name from users where id = '$stadion[6]'")->fetch_assoc()["name"];
                $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[7]'")->fetch_assoc()['name'];
                $tuman = $connection->query("select name from tumanlars where id = '$stadion[8]'")->fetch_assoc()['name'];

                $phone_2 = '';
                if ($stadion[3] !== null) {
                    $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
                }
                $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $stadion[4]\n\n⏱ Soatlik narxi:  $stadion[5]\n\nTahrirlash uchun quyidagi bo'limlardan birini tanlang 👇👇👇 ";
                $bot->sendMessage($chat_id, $text, null, false, null, $btn);
            }
            if (strpos($status, 'locationEdit_') !== false) {
                $stadion_id = explode('_', $status)[1];

                $latitude = $update->getMessage()->getLocation()->getLatitude();
                $longitude = $update->getMessage()->getLocation()->getLongitude();
                $connection->query("update stadions set latitude = '$latitude', longitude = '$longitude' where id = $stadion_id");
                $connection->query("update users set status = '0' where chat_id = $chat_id");
                $bot->sendMessage($chat_id, "Sizning stadioningiz locatsiyasi muaffaqiyatli o'zgartirildi");

                $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                    [['text' => 'Nomi 🔤', 'callback_data' => "name_$stadion_id"], ['text' => 'Narxi 💵', 'callback_data' => "narx_$stadion_id"]],
                    [['text' => 'Tel raqam 1 📲', 'callback_data' => "phone1_$stadion_id"], ['text' => 'Tel raqam 2 📲', 'callback_data' => "phone2_$stadion_id"]],
                    [['text' => 'Mo\'ljal 📍', 'callback_data' => "manzil_$stadion_id"], ['text' => 'Locatsiya 🗺', 'callback_data' => "location_$stadion_id"]],
                    [['text' => "Bosh menyu 🏘", 'callback_data' => 'boshMenu']]
                ]);

                $stadion = $connection->query("select * from stadions where id = '$stadion_id'")->fetch_all()[0];
                $ega = $connection->query("select name from users where id = '$stadion[6]'")->fetch_assoc()["name"];
                $viloyat = $connection->query("select name from viloyatlars where id = '$stadion[7]'")->fetch_assoc()['name'];
                $tuman = $connection->query("select name from tumanlars where id = '$stadion[8]'")->fetch_assoc()['name'];

                $phone_2 = '';
                if ($stadion[3] !== null) {
                    $phone_2 .= "📞 Bog'lanish uchun raqam 2: +$stadion[3]\n";
                }
                $text = "🏟 Stadion nomi:  $stadion[1]\n👨‍💼 Ma'sul: $ega\n\n📞 Bog'lanish uchun raqam: +$stadion[2]\n$phone_2 \n📍 Stadion joylashgan joy: $viloyat viloyati, $tuman tumani\n📍 Mo'ljal: $stadion[4]\n\n⏱ Soatlik narxi:  $stadion[5]\n\nTahrirlash uchun quyidagi bo'limlardan birini tanlang 👇👇👇 ";
                $bot->sendMessage($chat_id, $text, null, false, null, $btn);
            }
            ///////////  Edit STADION END //////////////////

            ///////////   STADION vaqtlari write  START //////////////////
            if (strpos($status, "consumerPhone_") !== false) {
                $stadion_id = explode("_", $status)[1];
                $kun = explode("_", $status)[2];

                $filter = preg_match("/^[0-9]{12,12}/", $text);
                if ($filter === 1) {
                    $phone_unique = $connection->query("select phone from consumer where phone = '$text'")->num_rows;

                    if ($phone_unique == 0) {
                        $consumer = "consumerName_$text" . "_$stadion_id" . "_$kun";
                        $test = $connection->query("insert into consumer (chat_id, phone) values ('$chat_id','$text')");
                        if ($test) {
                            $sql = "update users set status = '$consumer' where chat_id='$chat_id'";
                            $tt = $connection->query("update users set status = '$consumer' where chat_id='$chat_id'");
                            $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => "Orqaga 🔙", 'callback_data' => "stdVaqtlari_$stadion_id"]]]);
                            $bot->sendMessage($chat_id, "Buyurtmachining ismini kiriting:", null, false, null, $b);
                        }
                    } else {
                        $phone_number = $connection->query("select phone from consumer where phone = '$text'")->fetch_assoc()['phone'];
                        $oldbuyurtmachiName = $connection->query("select * from consumer where phone = '$phone_number'")->fetch_assoc()['name'];

                        if ($oldbuyurtmachiName !== null) {
                            $text = "Ushbu raqam egasi oldin stadion buyurtma qilgan👇👇👇\n\nIsmi: $oldbuyurtmachiName\nTelefon raqami: $phone_number";
                            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text' => 'Shu buyurtmachini tanlash 🔂', 'callback_data' => "qaytaZakaz_$phone_number" . "_$kun" . "_$stadion_id"]], [['text' => "Orqaga 🔙", 'callback_data' => "stdVaqtlari_$stadion_id"]]]);
                            $bot->sendMessage($chat_id, $text, 'HTML', false, null, $btn);
                        }
                    }

                } else {
                    if ($text !== "Bosh menyu 🏘") {
                        $bot->sendMessage($chat_id, "❗Iltimos telefon raqamni namunadagidek kiriting!");
                    }
                }

            }
            if (strpos($status, "consumerName_") !== false) {
                $phone_number = explode("_", $status)[1];
                $stadion_id = explode("_", $status)[2];
                $kun = explode("_", $status)[3];
                $filter = preg_match("/^[a-zA-Z '`‘]*$/", $text);
                if ($filter === 1) {

                    $rep_text = str_replace("'", "\'", $text);
                    $connection->query("update consumer set name = '$rep_text' where phone='$phone_number'");
                    $connection->query("update users set status = '0' where chat_id='$chat_id'");

                    $oldbuyurtmachiId = $connection->query("select * from consumer where phone = '$phone_number'")->fetch_assoc()['id'];
                    $vaqtMassiv = [];
                    if (date('d-m-Y') == $kun) {
                        foreach ($vaqtlar_massiv as $key => $item) {
                            $vaqtNow = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$item' and stadion_id = '$stadion_id'")->num_rows;
                            $e = explode(":", explode(" - ", $item)[0])[0];
                            $v = date("H") <= $e || $e == 1 || $e == 2;

                            if ($vaqtNow == 0 && $v) {
                                $vaqtMassiv[] = ['text' => "$item", "callback_data" => "NewVaqt" . "_$kun" . "_$stadion_id" . "_$oldbuyurtmachiId" . "_$item"];
                            }
                        }
                    } else {
                        foreach ($vaqtlar_massiv as $key => $item) {
                            $vaqtNow = $connection->query("select vaqt from vaqtlar where kun = '$kun' and vaqt = '$item' and stadion_id = '$stadion_id'")->num_rows;
                            if ($vaqtNow == 0) {
                                $vaqtMassiv[] = ['text' => "$item", "callback_data" => "NewVaqt" . "_$kun" . "_$stadion_id" . "_$oldbuyurtmachiId" . "_$item"];
                            }
                        }
                    }
                    $vaqtMassiv = array_chunk($vaqtMassiv, '3');
                    $vaqtMassiv[] = [['text' => "Orqaga 🔙", 'callback_data' => "stdVaqtlari_$stadion_id"]];

                    $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($vaqtMassiv);
                    $bot->sendMessage($chat_id, 'Qaysi vaqtni buyurtma qildi ?', false, null, false, $btn);

                } else {
                    if ($text !== "Bosh menyu 🏘") {
                        $bot->sendMessage($chat_id, "❗Buyurtmachining Ismini to'g'ri kiriting");
                    }
                }

            }


            ///////////   STADION vaqtlari write END //////////////////


        } catch (Exception $exception) {
        }
    });


$bot->run();