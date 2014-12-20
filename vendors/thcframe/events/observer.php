<?php

namespace THCFrame\Events;

use THCFrame\Events\Observable;

/**
 *  Basic interface for observer objects
 */
interface Observer
{
    public function update(Observable $observable);
}
