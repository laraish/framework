<?php

namespace Laraish\Foundation\WpHook;

enum WpHookType: string
{
    case ACTION = 'action';
    case FILTER = 'filter';
}
