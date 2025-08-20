<?php

namespace Laraish\Foundation\WpHook;

abstract class WpHookListener
{
    protected string $hookName;
    protected WpHookType $hookType;

    protected int $priority = 10;
    protected int $argumentsNumber = 1;
}
