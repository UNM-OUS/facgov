<?php
/* Digraph Project Template | https://gitlab.com/byjoby/digraph-project | MIT License */
namespace DigraphProject\Scripts;

use Composer\Script\Event;
use DigraphProject\ScriptHandler;

class Composer
{
    public static function installHandler(Event $event)
    {
        ScriptHandler::installHandler($event);
    }

    public static function updateHandler(Event $event)
    {
        ScriptHandler::updateHandler($event);
    }
}
