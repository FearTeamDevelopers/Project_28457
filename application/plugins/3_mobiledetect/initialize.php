<?php

require_once 'MobileDetect.php';

$detect = new MobileDetect();
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
$_SESSION['app_devicetype'] = $deviceType;

THCFrame\Events\Events::fire('plugin.mobiledetect.devicetype', array($deviceType));
