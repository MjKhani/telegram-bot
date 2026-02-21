<?php
// --- تنظیمات اصلی ---
$token = "TOKEN-BOT";
$admin_id = 123456;
$url = "https://api.telegram.org/bot$token/";

$update = json_decode(file_get_contents("php://input"), TRUE);
if (!$update) exit;

$message = $update['message'] ?? null;
if (!$message) exit;

$chat_id = $message['chat']['id'];
$text = $message['text'] ?? "";
$first_name = $message['from']['first_name'] ?? "ندارد";
$username = isset($message['from']['username']) ? "@" . $message['from']['username'] : "ندارد";

// --- توابع کمکی ---
function sendMessage($chat_id, $text) {
    global $url;
    $post_data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    return file_get_contents($url . "sendMessage?" . http_build_query($post_data));
}

function load_users() {
    if (!file_exists("users.txt")) return [];
    return array_unique(array_filter(explode("\n", file_get_contents("users.txt"))));
}

function set_step($chat_id, $step) {
    file_put_contents("step_$chat_id.txt", $step);
}

function get_step($chat_id) {
    return file_exists("step_$chat_id.txt") ? file_get_contents("step_$chat_id.txt") : "none";
}

function delete_step($chat_id) {
    if (file_exists("step_$chat_id.txt")) unlink("step_$chat_id.txt");
}

// --- بخش دستورات ---

if ($text == "/cancel") {
    delete_step($chat_id);
    sendMessage($chat_id, "❌ عملیات لغو شد.");
    exit;
}

if ($text == "/start") {
    delete_step($chat_id);
    $users = load_users();
    if (!in_array($chat_id, $users)) {
        file_put_contents("users.txt", $chat_id . "\n", FILE_APPEND);
        // آیدی در خط اول، مونو شده و مرتب
        $admin_msg = "👤 کاربر جدید وارد شد:\n\n" .
                     "<b>آیدی عددی:</b> <code>$chat_id</code>\n" .
                     "<b>نام:</b> $first_name\n" .
                     "<b>یوزرنیم:</b> $username";
        sendMessage($admin_id, $admin_msg);
    }
    sendMessage($chat_id, "سلام🌹\nخوش آمدید❤️\nشما در ربات اعلانات پرداخت ثبت شدید✅");
}

elseif ($text == "/users" && $chat_id == $admin_id) {
    $users = load_users();
    $report = "👥 لیست کاربران:\n\n";
    foreach ($users as $uid) {
        $uid = trim($uid);
        $get_chat = json_decode(file_get_contents($url . "getChat?chat_id=$uid"), TRUE);
        if ($get_chat['ok']) {
            $res = $get_chat['result'];
            $report .= "ID: <code>$uid</code>\nName: " . ($res['first_name'] ?? "ندارد") . "\nUser: " . ($res['username'] ?? "ندارد") . "\n\n";
        }
    }
    sendMessage($chat_id, $report);
}

elseif (strpos($text, "/send") === 0 && $chat_id == $admin_id) {
    $parts = explode(" ", $text);
    if (count($parts) == 1) {
        set_step($chat_id, "send_all");
        sendMessage($chat_id, "📝 پیام همگانی را بنویسید یا /cancel بزنید:");
    } 
    elseif (count($parts) == 2 && is_numeric($parts[1])) {
        set_step($chat_id, "send_single:" . $parts[1]);
        sendMessage($chat_id, "📝 پیام برای <code>" . $parts[1] . "</code> را بنویسید:");
    }
    else {
        $content = substr($text, 6);
        $content_parts = explode(" ", $content, 2);
        if (is_numeric($content_parts[0]) && isset($content_parts[1])) {
            sendMessage($content_parts[0], $content_parts[1]);
            sendMessage($admin_id, "✅ ارسال شد.");
        } else {
            foreach (load_users() as $uid) sendMessage(trim($uid), $content);
            sendMessage($admin_id, "✅ ارسال همگانی انجام شد.");
        }
    }
}

elseif ($text == "/photo") {
    set_step($chat_id, "wait_photo");
    sendMessage($chat_id, "📸 لطفاً عکس رسید را بفرستید یا /cancel بزنید:");
}

elseif (get_step($chat_id) !== "none") {
    $step = get_step($chat_id);
    if ($step == "send_all") {
        foreach (load_users() as $uid) sendMessage(trim($uid), $text);
        sendMessage($admin_id, "✅ پیام همگانی ارسال شد.");
        delete_step($chat_id);
    } 
    elseif (strpos($step, "send_single:") === 0) {
        $target_id = str_replace("send_single:", "", $step);
        sendMessage($target_id, $text);
        sendMessage($admin_id, "✅ پیام به کاربر ارسال شد.");
        delete_step($chat_id);
    }
    elseif ($step == "wait_photo" && isset($message['photo'])) {
        $file_id = end($message['photo'])['file_id'];
        // مشخصات زیر عکس با آیدی در خط اول و مونو
        $caption = "📸 رسید جدید از کاربر:\n\n" .
                   "<b>آیدی عددی:</b> <code>$chat_id</code>\n" .
                   "<b>نام:</b> $first_name\n" .
                   "<b>یوزرنیم:</b> $username";
        file_get_contents($url . "sendPhoto?chat_id=$admin_id&photo=$file_id&parse_mode=HTML&caption=" . urlencode($caption));
        sendMessage($chat_id, "✅ رسید شما دریافت و برای مدیریت ارسال شد.");
        delete_step($chat_id);
    }
}
?>
