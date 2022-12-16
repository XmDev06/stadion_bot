<?php
require_once __DIR__ . '/vendor/autoload.php';
include "config.php";

$botToken = "5823532001:AAFe5Y1I1JsmxC8XoUc2aPiRqtmuf83oek8";
// https://api.telegram.org/bot5823532001:AAFe5Y1I1JsmxC8XoUc2aPiRqtmuf83oek8/setWebhook?url=https://31b3-213-230-102-85.eu.ngrok.io/projects/stadion_bot/user.php

$bot = new \TelegramBot\Api\Client($botToken);


$bot->command('start', static function (\TelegramBot\Api\Types\Message $message) use ($bot, $connection) {
    try {
        $chatId = $message->getChat()->getId();
        $firstname = $message->getChat()->getFirstName();

        $bot->sendMessage($chatId, "salom");

    } catch (Exception $exception) {
        //
    }
});


$bot->callbackQuery(static function (\TelegramBot\Api\Types\CallbackQuery $callbackquery) use ($bot, $connection) {
    try {

        $chatId = $callbackquery->getMessage()->getChat()->getId();
        $data = $callbackquery->getData();
        $firstname = $callbackquery->getMessage()->getChat()->getFirstName();
        $messageId = $callbackquery->getMessage()->getMessageId();

//        if ($data == 'orqa'){
//            $bot->deleteMessage($chatId, $messageId);
//        }

    } catch (Exception $exception) {
    }
});


$bot->on(static function () {
},
    static function (\TelegramBot\Api\Types\Update $update) use ($bot, $connection) {

        try {
            $chat_id = $update->getMessage()->getChat()->getId();
            $text = $update->getMessage()->getText();
            $messageId = $update->getMessage()->getMessageId();

//            if ($status == 'search') {
//
//            }

        } catch (Exception $exception) {
        }
    });


$bot->run();