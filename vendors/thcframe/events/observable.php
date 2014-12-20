<?php

namespace THCFrame\Events;

use THCFrame\Events\Observer;

/**
 * Basic interface for observable objects
 */
interface Observable
{
    public function attach(Observer $observer);
    public function detach(Observer $observer);
    public function notify();
}
