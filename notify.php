<?php
$token = "TOKEN-BOT";
$day = date('j');

// فقط در روزهای ۲۸ و ۲۹ ماه اجرا شود
if ($day == 28 || $day == 29) {
    if (file_exists("users.txt")) {
        $users = explode("\n", file_get_contents("users.txt"));
        $users = array_unique(array_filter($users)); // حذف تکراری‌ها و خط‌های خالی

        foreach ($users as $u_id) {
            $u_id = trim($u_id);
            if (!empty($u_id)) {
                $text = urlencode("🔔یادآوری:
موعد پرداخت ماهانه فرا رسیده است،
لطفاً نسبت به واریز آن اقدام نمایید.🙏
🔄جهت تمدید به صورت خودکار 
دستور /photo را بزنید
و عکس رسید خود را ارسال نمایید.✅");
                file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$u_id&text=$text");
            }
        }
    }
}
?>
