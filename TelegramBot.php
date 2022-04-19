<?php

use GuzzleHttp\Client;

class TelegramBot
{
    protected $token = "5142115351:AAG6KMkuZ2IA14nf0SZyiZpDKlUdbAns8pE";

    protected $updateId;
    private const MINUTES=2;
    private array $infoMessage;

    protected function query($method, $params = [])
    {
        $url = "https://api.telegram.org/bot";
        $url .= $this->token;
        $url .= "/" . $method;

        if (!empty($params)) {
            $url .= "?" . http_build_query($params);
        }

        $client = new Client([
            'base_uri' => $url
        ]);
        $result = $client->request('GET');

        return json_decode($result->getBody());
    }

    public function getUpdates()
    {
        $response = $this->query('getUpdates', [
            'offset' => $this->updateId + 1
        ]);

        if (!empty($response->result)) {
            $this->updateId = $response->result[count($response->result) - 1]->update_id;
        }
        return $response->result;
    }

    public function sendMessage(array $infoMessage)
    {
        $this->infoMessage=$infoMessage;
        if (strcasecmp($this->infoMessage['text'], "/getcode") == 0) {
            return $this->query('sendMessage', [
                'text' => $this->addCode(),
                'chat_id' => $this->infoMessage['chat_id']
            ]);
        }
//        $response = $this->query('sendMessage', [
//            'text' => $text,
//            'chat_id' => $chat_id
//        ]);

        return $this->sendUrl();
    }

    public function sendUrl(){
        $urlUser="tg://user?id=".$this->infoMessage['user_id'];
        return $this->query('sendMessage', [
            'text' => '<a href="'.$urlUser.'">'.$this->infoMessage['name'].'</a>',
            'chat_id' => $this->infoMessage['chat_id'],
            'parse_mode' => 'html'
        ]);
    }

    /**
     * @throws Exception
     */
    public function addCode(): int
    {
        $conn = mysqli_connect("localhost", "griffon", "Password1!", "kinopoisk_duo");
        $sql = "SELECT * FROM `tg_codes` WHERE tg_id='" . $this->infoMessage['user_id'] . "'";
        if (count(mysqli_fetch_all(mysqli_query($conn, $sql))) != 0) {
            mysqli_query($conn, "DELETE FROM `tg_codes` WHERE tg_id='" . $this->infoMessage['user_id'] . "'");
        }

        while (true){
            $code = mt_rand(10000000, 99999999);
            $sql = "SELECT * FROM `tg_codes` WHERE tg_code='" . $code . "'";
            if (count(mysqli_fetch_all(mysqli_query($conn, $sql))) == 0) {
                break;
            }
        }
        $date=(new DateTime(date('Y-m-d H:i:s')))->add(new DateInterval('PT' . self::MINUTES . 'M'));
        $sql = "INSERT INTO tg_codes (tg_code, tg_id, expires) VALUES ('" . $code . "', " . $this->infoMessage['user_id'] . ", " . "'" . $date->format('Y-m-d H:i:s') . "'" . ")";
        mysqli_query($conn, $sql);
        mysqli_close($conn);
        return $code;
    }
}
