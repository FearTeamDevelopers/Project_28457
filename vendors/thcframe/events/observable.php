<?php

namespace THCFrame\Events;

use THCFrame\Events\Observer;

/**
 *
 * @author Tomy
 */
interface Observable
{
    public function attach(Observer $observer);
    public function detach(Observer $observer);
    public function notify();
}
