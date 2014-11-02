<?php

if (!isset($_SESSION['app_devicetype'])) {
    require_once 'MobileDetect.php';

    $detect = new MobileDetect();
    $isMobile = $detect->isMobile();
    $isTablet = $detect->isTablet();

    $deviceType = ($isMobile ? ($isTablet ? 'tablet' : 'phone') : 'computer');
    

    $_SESSION['app_devicetype'] = $deviceType;
} else {
    $deviceType = $_SESSION['app_devicetype'];
}


THCFrame\Events\Events::fire('plugin.mobiledetect.devicetype', array($deviceType));
