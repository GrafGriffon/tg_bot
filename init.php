<?php
include('vendor/autoload.php');

include('TelegramBot.php');

$telegramApi = new TelegramBot();

while (true) {

    $updates = $telegramApi->getUpdates();

    foreach ($updates as $update) {
        if (!empty($update->message->chat->id) && !empty($update->message->text)) {
            if (($update->message->chat->id) > 0) {
                print_r($update);
                $infoMessage=[
                    'chat_id'=>$update->message->chat->id,
                    'text'=>$update->message->text,
                    'user_id'=>$update->message->from->id,
                    'name'=>$update->message->from->first_name,
                ];
                if (isset($update->message->entities)){
                    $infoMessage['type']=$update->message->entities[0]->type;
                }
                $telegramApi->sendMessage($infoMessage);
            }
        }
        //print_r($update);
    }
    sleep(2);
}
