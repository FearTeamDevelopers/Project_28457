<?php

namespace THCFrame\Events;

use THCFrame\Events\Observable;
/**
 *
 * @author Tomy
 */
interface Observer
{
    public function update(Observable $observable);
}
