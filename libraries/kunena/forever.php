<?php

// Patches for Kunena 4 Forever version

defined ( '_JEXEC' ) or die ();

class KunenaForever {

// Replacement for:
//    $dispatcher = JDispatcher::getInstance();
//    ... = $dispatcher->trigger('...');
public static function dispatchEvent ($eventName, $args = []) {
   return JFactory::getApplication()->triggerEvent($eventName, $args); }

}
