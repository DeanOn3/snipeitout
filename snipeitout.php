<?php

require 'vendor/autoload.php';

use danog\TelegramBot\Telegram;

// مشخصات حساب تلگرام
$phone_number = '+64212035040';  // شماره تلفن
$api_id = 1187232;  // API ID
$api_hash = '250511852f37772c0a1a32ca143245fb';  // API Hash

// کانال مبدا و مقصد
$source_channel = 'https://t.me/BINEROS_CCS2';
$destination_channel = 'https://t.me/asfuckcc';

function get_country_name($country_code)
{
    $country_names = array(
        '🇦🇫' => 'Afghanistan',
        '🇦🇽' => 'Åland Islands',
        '🇦🇱' => 'Albania',
        '🇩🇿' => 'Algeria',
        // Add more country code mappings here
    );
    return $country_names[$country_code] ?? '';
}

function parse_card_info($message_text)
{
    $card_info = array(
        'CardNumber' => '',
        'MM' => '',
        'YY' => '',
        'CVV' => '',
        'BIN' => '',
        'emoji' => '',
        'Country' => ''
    );

    // جداکردن اطلاعات کارت از متن پیام
    $parts = preg_split('/\s+/', $message_text);

    // جداکردن شماره کارت
    foreach ($parts as $part) {
        if (ctype_digit($part) && strlen($part) >= 16) {
            $card_info['CardNumber'] = $part;
            break;
        }
    }

    // یافتن ماه و سال انقضا
    foreach ($parts as $part) {
        if (strpos($part, '/') !== false) {
            [$month, $year] = explode('/', $part);
            $card_info['MM'] = $month;
            $card_info['YY'] = $year;
            break;
        }
    }

    // یافتن CVV
    foreach ($parts as $part) {
        if (ctype_digit($part) && strlen($part) === 3) {
            $card_info['CVV'] = $part;
            break;
        }
    }

    // یافتن BIN
    foreach ($parts as $part) {
        if (ctype_digit($part) && strlen($part) >= 6) {
            $card_info['BIN'] = substr($part, 0, 6);
            break;
        }
    }

    // شناسایی ایموجی در متن
    $emoji_regex = '/[\x{10000}-\x{10FFFF}]/u';
    foreach ($parts as $part) {
        if (preg_match($emoji_regex, $part)) {
            $country_name = get_country_name($part);
            if ($country_name) {
                $card_info['emoji'] = $part;
                $card_info['Country'] = $country_name;
                break;
            }
        }
    }

    return $card_info;
}

// اتصال به تلگرام
$telegram = new Telegram($api_id, $api_hash);
$telegram->getEventBus()->addListener(
    'update',
    function ($update) use ($telegram, $source_channel, $destination_channel) {
        $message = $update->getMessage();

        // این قسمت را بر اساس فرمت‌های مختلف کارت تغییر دهید
        $card_info = parse_card_info($message->getText());

        // ایجاد پیام مقصد
        $destination_message = "Card  : {$card_info['CardNumber']}\n"
            . "Month : {$card_info['MM']}\n"
            . "Year : {$card_info['YY']}\n"
            . "CVV : {$card_info['CVV']}\n"
            . "Bin : {$card_info['BIN']}\n"
            . "Country : {$card_info['emoji']} {$card_info['Country']}\n"
            . "Live✅\n"
            . "@FloxxyChannel";

        // ارسال پیام به کانال مقصد
        $telegram->sendMessage(
            [
                'chat_id' => $destination_channel,
                'text' => $destination_message,
            ]
        );
    }
);

// شروع گوش دادن به پیام‌های جدید
$telegram->run();
