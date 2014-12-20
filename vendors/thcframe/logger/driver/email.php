<?php

namespace THCFrame\Logger\Driver;

use THCFrame\Logger;
use THCFrame\Registry\Registry;

/**
 * Email logger class
 */
class Email extends Logger\Driver
{

    /**
     * 
     * @param type $message
     */
    public function log($message)
    {
        require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
        $transport = Swift_MailTransport::newInstance();
        $mailer = Swift_Mailer::newInstance($transport);

        $config = Registry::get('configuration');
        $sendTo = $config->system->adminemail;
        $appName = $config->system->appname;
        
        $email = Swift_Message::newInstance()
                ->setSubject($appName . ' error' )
                ->setFrom('info@fear-team.cz')
                ->setTo($sendTo)
                ->setBody($message);

        $result = $mailer->send($email);
    }

}
