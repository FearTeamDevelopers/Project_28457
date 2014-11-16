<?php

namespace THCFrame\Logger\Driver;

use THCFrame\Logger;

/**
 * Email logger class
 */
class Email extends Logger\Driver
{

    public function log($message)
    {
        require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
        $transport = Swift_MailTransport::newInstance();
        $mailer = Swift_Mailer::newInstance($transport);

        $message = Swift_Message::newInstance()
                ->setSubject('THCFrame error: ' . $server->getServerName())
                ->setFrom('info@fear-team.cz')
                ->setTo($user->getEmail())
                ->setBody($body);

        $result = $mailer->send($message);
    }

}
