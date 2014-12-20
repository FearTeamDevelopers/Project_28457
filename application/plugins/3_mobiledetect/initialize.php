<?php

if (!isset($_SESSION['app_devicetype'])) {
    require_once 'MobileDetect.php';

    $detect = new MobileDetect();

    if ($detect->isMobile() && !$detect->isTablet()) {
        $deviceType = 'phone';
    } elseif ($detect->isTablet() && !$detect->isMobile()) {
        $deviceType = 'tablet';
    } else {
        $deviceType = 'computer';
    }

    $_SESSION['app_devicetype'] = $deviceType;
} else {
    $deviceType = $_SESSION['app_devicetype'];
}


THCFrame\Events\Events::fire('plugin.mobiledetect.devicetype', array($deviceType));
