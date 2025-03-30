<?php
namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Latte\Engine;
use App\Utils\LogType;
use App\Utils\LogUtil;

class MailUtil {
    private static $config;

    /**
     * Loads the configuration from the config.php file.
     *
     * This function is lazy and only loads the configuration the first time it is
     * called. Subsequent calls will return the previously loaded configuration.
     */
    public static function loadConfig() {
        if (!self::$config) {
            self::$config = include __DIR__ . '/../../config.php';
        }
    }

    /**
     * Sends an email using the specified template and data.
     *
     * This function configures and sends an email using PHPMailer with SMTP settings
     * loaded from the configuration. It uses a Latte template to render the email body.
     *
     * @param string $to The recipient's email address.
     * @param string $subject The subject of the email.
     * @param string $template The name of the Latte template to use for the email body.
     * @param array $data An associative array of data to pass to the template.
     * @return bool Returns true if the email was sent successfully, false otherwise.
     */

    public static function sendMail($to, $subject, $template, $data = []) {
        self::loadConfig();

        $mail = new PHPMailer(true);
        try {
            // SMTP-Einstellungen aus der config.php
            $mail->isSMTP();
            $mail->Host = self::$config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = self::$config['mail']['username'];
            $mail->Password = self::$config['mail']['password'];
            $mail->SMTPSecure = self::$config['mail']['encryption'];
            $mail->Port = self::$config['mail']['port'];

            $mail->setFrom(self::$config['mail']['fromEmail'], self::$config['mail']['fromName']);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;

            // Latte Template rendern
            $latte = new Engine();
            $templatePath = __DIR__ . '/../views/emails/' . $template . '.latte';
            $mail->Body = $latte->renderToString($templatePath, $data);
            
            if($template == 'welcome' && self::$config['mail']['enableWelcomeMail'] === true) {
                LogUtil::logAction(LogType::MAIL, "MailUtil", "sendMail", "Welcome Mail an {$to} wurde versendet");
                return $mail->send();
            } else if ($template !== "welcome") {
                LogUtil::logAction(LogType::MAIL, "MailUtil", "sendMail", "{$template} Mail an {$to} wurde versendet");
                return $mail->send();
            }
            
        } catch (Exception $e) {
            LogUtil::logAction(LogType::MAIL, "MailUtil", "sendMail", $e->getMessage());
            error_log("Mail konnte nicht gesendet werden: {$mail->ErrorInfo}");
            return false;
        }
    }
}
?>