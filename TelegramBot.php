<?php

use GuzzleHttp\Client;

class TelegramBot
{
    protected string $token = "5142115351:AAG6KMkuZ2IA14nf0SZyiZpDKlUdbAns8pE";

    protected $updateId;
    private const MINUTES = 2;
    private array $infoMessage;


    public function __construct()
    {

    }

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

    /**
     * @throws Exception
     */
    public function sendMessage(array $infoMessage)
    {
        $this->infoMessage = $infoMessage;
        if (key_exists('type', $infoMessage)) {
            if (str_contains($this->infoMessage['text'], "/getcode")) { //getcode
                return $this->query('sendMessage', [
                    'text' => $this->addCode(),
                    'chat_id' => $this->infoMessage['id']
                ]);
            }
            if (str_contains($this->infoMessage['text'], "/pending")) {  //pending
                return $this->getPending();
            }
            if (str_contains($this->infoMessage['text'], "/accept")) {  //accept
                $number = stristr($this->infoMessage['text'], 6);
                if (!is_numeric($number)) {
                    return $this->getError();
                }
                return $this->getAccept($number);
            }
            if (str_contains($this->infoMessage['text'], "/discard")) {  //discard
                $number = stristr($this->infoMessage['text'], 6);
                if (!is_numeric($number)) {
                    return $this->getError();
                }
                return $this->getDiscard($number);
            }
            if (str_contains($this->infoMessage['text'], "/session")) {  //session
                return $this->getSessions();
            }
            if (str_contains($this->infoMessage['text'], "/settime")) {  //settime
                $number = explode(' ', $this->infoMessage['text']);
                if (count($number) != 3) {
                    return $this->getError();
                }
                if ($number[0] == "/settime") {
                    return $this->setTime($number);
                } else {
                    return $this->getError();
                }

            }
            if (str_contains($this->infoMessage['text'], "/cancel")) {  //cancel
                $number = explode(' ', $this->infoMessage['text']);
                if (count($number) != 2) {
                    return $this->getError();
                }
                if ($number[0] != "/cancel") {
                    return $this->getError();
                }
                if (is_numeric($number[1])) {
                    $this->getCancel($number[1]);
                } else {
                    return $this->getError();
                }
            }
            if (str_contains($this->infoMessage['text'], "/finish")) {  //cancel
                $number = explode(' ', $this->infoMessage['text']);
                if (count($number) != 2) {
                    return $this->getError();
                }
                if ($number[0] != "/finish") {
                    return $this->getError();
                }
                if (is_numeric($number[1])) {
                    $this->getFinish($number[1]);
                } else {
                    return $this->getError();
                }
            }
        } else {
            return $this->sendUrl();
        }
        return $this->query('sendMessage', [
            'text' => "Not command",
            'chat_id' => $this->infoMessage['id']
        ]);
    }

    public function setTime(array $number)
    {
        try {
            if (key_exists(3, $number)){
                $date=new DateTime($number[2].' '.$number[3]);
            } else{
                $date=new DateTime($number[2]);
            }
        } catch (Exception $e){
            return $this->getError();
        }
        $connect = mysqli_connect("localhost", "griffon", "Password1!", "kinopoisk_duo");
        $sql = "SELECT watch_together_sessions.watch_together_session_id, w.watch_together_session_id, watch_together_sessions.movie_id, u.user_id, l.user_id, watch_together_sessions.start_datetime
FROM `watch_together_sessions`
         JOIN watch_together_sessions w on watch_together_sessions.user_id = w.mate_user_id AND watch_together_sessions.movie_id=w.movie_id
         JOIN users u on u.user_id = watch_together_sessions.user_id
         JOIN users l on l.user_id = watch_together_sessions.mate_user_id
WHERE u.tg_id ='" . $this->infoMessage['id'] . "' and (w.watch_together_session_id='" . $number . "' 
or watch_together_sessions.watch_together_session_id='" . $number . "')";
        $local = mysqli_fetch_all(mysqli_query($connect, $sql));
        if (count($local) > 0) {
            foreach ($local as $element) {
                $sql = "UPDATE watch_together_sessions SET start_datetime='" . $date . "' WHERE watch_together_session_id='" . $element[0] . "' OR watch_together_session_id='" . $element[1] . "'";
                mysqli_query($connect, $sql);
            }
            $this->query('sendMessage', [
                'text' => "Success",
                'chat_id' => $this->infoMessage['id']
            ]);
        } else {
            $this->query('sendMessage', [
                'text' => "Id not isset",
                'chat_id' => $this->infoMessage['id']
            ]);
        }
    }

    public function getCancel(int $number): void
    {
        $connect = mysqli_connect("localhost", "griffon", "Password1!", "kinopoisk_duo");
        $sql = "SELECT watch_together_sessions.watch_together_session_id, w.watch_together_session_id, watch_together_sessions.movie_id, u.user_id, l.user_id, watch_together_sessions.start_datetime
FROM `watch_together_sessions`
         JOIN watch_together_sessions w on watch_together_sessions.user_id = w.mate_user_id AND watch_together_sessions.movie_id=w.movie_id
         JOIN users u on u.user_id = watch_together_sessions.user_id
         JOIN users l on l.user_id = watch_together_sessions.mate_user_id
WHERE u.tg_id ='" . $this->infoMessage['id'] . "' and (w.watch_together_session_id='" . $number . "' 
or watch_together_sessions.watch_together_session_id='" . $number . "')";
        $local = mysqli_fetch_all(mysqli_query($connect, $sql));
        if (count($local) > 0) {
            foreach ($local as $element) {
                mysqli_query($connect, "DELETE FROM `watch_together_sessions` WHERE watch_together_session_id='" . $element[0] . "' OR watch_together_session_id='" . $element[1] . "'");
                mysqli_query($connect, "INSERT INTO `watch_together_request_ignores` (user_id, movie_id, ignore_user_id) VALUES ('" . $element[4] . "', " . $element[2] . ", " . "'" . $element[3] . "'" . ")");
                mysqli_query($connect, "INSERT INTO `watch_together_request_ignores` (user_id, movie_id, ignore_user_id) VALUES ('" . $element[3] . "', " . $element[2] . ", " . "'" . $element[4] . "'" . ")");
            }
            $this->query('sendMessage', [
                'text' => "Success",
                'chat_id' => $this->infoMessage['id']
            ]);
        } else {
            $this->query('sendMessage', [
                'text' => "Id not isset",
                'chat_id' => $this->infoMessage['id']
            ]);
        }
    }

    public function getFinish(int $number): void
    {
        $connect = mysqli_connect("localhost", "griffon", "Password1!", "kinopoisk_duo");
        $sql = "SELECT watch_together_sessions.watch_together_session_id, w.watch_together_session_id
FROM `watch_together_sessions`
         JOIN watch_together_sessions w on watch_together_sessions.user_id = w.mate_user_id AND watch_together_sessions.movie_id=w.movie_id
         JOIN users u on u.user_id = watch_together_sessions.user_id
         JOIN users l on l.user_id = watch_together_sessions.mate_user_id
WHERE u.tg_id ='" . $this->infoMessage['id'] . "' and (w.watch_together_session_id='" . $number . "' 
or watch_together_sessions.watch_together_session_id='" . $number . "')";
        $local = mysqli_fetch_all(mysqli_query($connect, $sql));
        if (count($local) > 0) {
            foreach ($local as $element) {
                mysqli_query($connect, "DELETE FROM `watch_together_sessions` WHERE watch_together_session_id='" . $element[0] . "' OR watch_together_session_id='" . $element[1] . "'");
            }
            $this->query('sendMessage', [
                'text' => "Success",
                'chat_id' => $this->infoMessage['id']
            ]);
        } else {
            $this->query('sendMessage', [
                'text' => "Id not isset",
                'chat_id' => $this->infoMessage['id']
            ]);
        }
    }

    public function getError()
    {
        return $this->query('sendMessage', [
            'text' => 'Wrong argument',
            'chat_id' => $this->infoMessage['id'],
        ]);
    }

    public function getSessions(): void
    {
        $connect = mysqli_connect("localhost", "griffon", "Password1!", "kinopoisk_duo");
        $sql = "SELECT watch_together_sessions.watch_together_session_id, w.watch_together_session_id, m.title, u.user_id, l.user_id, l.tg_id, l.username, watch_together_sessions.start_datetime
FROM `watch_together_sessions`
         JOIN watch_together_sessions w on watch_together_sessions.user_id = w.mate_user_id AND watch_together_sessions.movie_id=w.movie_id
         JOIN users u on u.user_id = watch_together_sessions.user_id
         JOIN movies m on m.movie_id = watch_together_sessions.movie_id
         JOIN users l on l.user_id = watch_together_sessions.mate_user_id
WHERE u.tg_id ='" . $this->infoMessage['id'] . "'";
        foreach (mysqli_fetch_all(mysqli_query($connect, $sql)) as $element) {
            $result = '#' . $element[0] . ' 🍿 ' . $element[2] . ' 🎬 <a href="' . "tg://user?id=" . $element[5] . '">' . $element[6] . '</a> 🍿 ' .
                ($element[7] == null ? 'not time' : $element[7]);
            $this->query('sendMessage', [
                'text' => $result,
                'chat_id' => $this->infoMessage['id'],
                'parse_mode' => 'html'
            ]);
        }
    }

    public function sendUrl()
    {
        $urlUser = "tg://user?id=" . $this->infoMessage['id'];
        return $this->query('sendMessage', [
            'text' => '<a href="' . $urlUser . '">' . $this->infoMessage['name'] . '</a>',
            'chat_id' => $this->infoMessage['id'],
            'parse_mode' => 'html'
        ]);
    }

    public function getDiscard(int $number): void
    {
        $connect = mysqli_connect("localhost", "griffon", "Password1!", "kinopoisk_duo");
        $sql = "SELECT l.tg_id, watch_together_offers.is_accepted, w.is_accepted,
       watch_together_offers.watch_together_offer_id, w.watch_together_offer_id
FROM `watch_together_offers`
         JOIN watch_together_offers w on watch_together_offers.user_id = w.offered_user_id AND watch_together_offers.movie_id=w.movie_id
         JOIN users u on u.user_id = watch_together_offers.user_id
         JOIN users l on l.user_id = watch_together_offers.offered_user_id
WHERE u.tg_id ='" . $this->infoMessage['id'] . "' AND watch_together_offers.watch_together_offer_id='" . $number . "'";
        $local = mysqli_fetch_all(mysqli_query($connect, $sql));
        if (count($local) > 0) {
            $sql = "SELECT watch_together_offers.watch_together_offer_id, w.watch_together_offer_id, m.movie_id, l.user_id, u.user_id
FROM `watch_together_offers`
         JOIN watch_together_offers w on watch_together_offers.user_id = w.offered_user_id AND watch_together_offers.movie_id=w.movie_id
         JOIN users u on u.user_id = watch_together_offers.user_id
         JOIN movies m on m.movie_id = watch_together_offers.movie_id
         JOIN users l on l.user_id = watch_together_offers.offered_user_id
WHERE u.tg_id ='" . $this->infoMessage['id'] . "' AND (w.watch_together_offer_id='" . $number . "' or watch_together_offers.watch_together_offer_id='" . $number . "')";
            foreach (mysqli_fetch_all(mysqli_query($connect, $sql)) as $element) {
                mysqli_query($connect, "DELETE FROM `watch_together_offers` WHERE tg_id='" . $element[0] . "' OR tg_id='" . $element[1] . "'");
                mysqli_query($connect, "INSERT INTO `watch_together_request_ignores` (user_id, movie_id, ignore_user_id) VALUES ('" . $element[4] . "', " . $element[2] . ", " . "'" . $element[3] . "'" . ")");
                mysqli_query($connect, "INSERT INTO `watch_together_request_ignores` (user_id, movie_id, ignore_user_id) VALUES ('" . $element[3] . "', " . $element[2] . ", " . "'" . $element[4] . "'" . ")");
            }
            $this->query('sendMessage', [
                'text' => "Success",
                'chat_id' => $this->infoMessage['id']
            ]);
        } else {
            $this->query('sendMessage', [
                'text' => "Id not isset",
                'chat_id' => $this->infoMessage['id']
            ]);
        }
    }

    public function getAccept(int $number): void
    {
        $connect = mysqli_connect("localhost", "griffon", "Password1!", "kinopoisk_duo");
        $sql = "SELECT l.tg_id, watch_together_offers.is_accepted, w.is_accepted,
       watch_together_offers.watch_together_offer_id, w.watch_together_offer_id
FROM `watch_together_offers`
         JOIN watch_together_offers w on watch_together_offers.user_id = w.offered_user_id AND watch_together_offers.movie_id=w.movie_id
         JOIN users u on u.user_id = watch_together_offers.user_id
         JOIN users l on l.user_id = watch_together_offers.offered_user_id
WHERE u.tg_id ='" . $this->infoMessage['id'] . "' AND watch_together_offers.watch_together_offer_id='" . $number . "'";
        $local = mysqli_fetch_all(mysqli_query($connect, $sql));
        if (count($local) > 0) {
            if ($local[0][3] == 1) {
                $sql = "SELECT watch_together_offers.watch_together_offer_id, w.watch_together_offer_id, m.movie_id, l.user_id, u.user_id
FROM `watch_together_offers`
         JOIN watch_together_offers w on watch_together_offers.user_id = w.offered_user_id AND watch_together_offers.movie_id=w.movie_id
         JOIN users u on u.user_id = watch_together_offers.user_id
         JOIN movies m on m.movie_id = watch_together_offers.movie_id
         JOIN users l on l.user_id = watch_together_offers.offered_user_id
WHERE u.tg_id ='" . $this->infoMessage['id'] . "' AND (w.watch_together_offer_id='" . $number . "' or watch_together_offers.watch_together_offer_id='" . $number . "')";
                foreach (mysqli_fetch_all(mysqli_query($connect, $sql)) as $element) {
                    mysqli_query($connect, "DELETE FROM `watch_together_offers` WHERE tg_id='" . $element[0] . "' OR tg_id='" . $element[1] . "'");
                    mysqli_query($connect, "INSERT INTO `watch_together_sessions` (user_id, movie_id, mate_user_id) VALUES ('" . $element[4] . "', " . $element[2] . ", " . "'" . $element[3] . "'" . ")");
                    mysqli_query($connect, "INSERT INTO `watch_together_sessions` (user_id, movie_id, mate_user_id) VALUES ('" . $element[3] . "', " . $element[2] . ", " . "'" . $element[4] . "'" . ")");
                }
                $this->query('sendMessage', [
                    'text' => "Success",
                    'chat_id' => $this->infoMessage['id']
                ]);
            } else {
                $sql = "UPDATE watch_together_offers SET is_accepted=1 WHERE watch_together_offer_id='" . $number . "'";
                mysqli_query($connect, $sql);
                $this->query('sendMessage', [
                    'text' => "Success",
                    'chat_id' => $this->infoMessage['id']
                ]);
            }
        } else {
            $this->query('sendMessage', [
                'text' => " Id not isset",
                'chat_id' => $this->infoMessage['id']
            ]);
        }
    }

    public function getPending(): void
    {
        $connect = mysqli_connect("localhost", "griffon", "Password1!", "kinopoisk_duo");
        $sql = "SELECT m.title, l.username, l.tg_id, u.username, watch_together_offers.is_accepted, w.is_accepted,
       watch_together_offers.watch_together_offer_id
FROM `watch_together_offers`
         JOIN watch_together_offers w on watch_together_offers.user_id = w.offered_user_id AND watch_together_offers.movie_id=w.movie_id
         JOIN users u on u.user_id = watch_together_offers.user_id
         JOIN movies m on m.movie_id = watch_together_offers.movie_id
         JOIN users l on l.user_id = watch_together_offers.offered_user_id
WHERE u.tg_id ='" . $this->infoMessage['id'] . "'";
        foreach (mysqli_fetch_all(mysqli_query($connect, $sql)) as $element) {
            $result = '#' . $element[6] . ' 🍿 ' . $element[0] . ' 🎬 <a href="' . "tg://user?id=" . $element[2] . '">' . $element[1] . '</a>:' .
                ($element[5] == 1 ? 'accept' : 'repulse') . ' 🎥 your:' . ($element[4] == 1 ? 'accept' : 'repulse');
            $this->query('sendMessage', [
                'text' => $result,
                'chat_id' => $this->infoMessage['id'],
                'parse_mode' => 'html'
            ]);
        }
    }

    /**
     * @throws Exception
     */
    public function addCode(): int
    {
        $connect = mysqli_connect("localhost", "griffon", "Password1!", "kinopoisk_duo");
        $sql = "SELECT * FROM `tg_codes` WHERE tg_id='" . $this->infoMessage['id'] . "'";
        if (count(mysqli_fetch_all(mysqli_query($connect, $sql))) != 0) {
            mysqli_query($connect, "DELETE FROM `tg_codes` WHERE tg_id='" . $this->infoMessage['id'] . "'");
        }

        while (true) {
            $code = mt_rand(10000000, 99999999);
            $sql = "SELECT * FROM `tg_codes` WHERE tg_code='" . $code . "'";
            if (count(mysqli_fetch_all(mysqli_query($connect, $sql))) == 0) {
                break;
            }
        }
        $date = (new DateTime(date('Y-m-d H:i:s')))->add(new DateInterval('PT' . self::MINUTES . 'M'));
        $sql = "INSERT INTO tg_codes (tg_code, tg_id, expires) VALUES ('" . $code . "', " . $this->infoMessage['id'] . ", " . "'" . $date->format('Y-m-d H:i:s') . "'" . ")";
        mysqli_query($connect, $sql);
        mysqli_close($connect);
        return $code;
    }
}
