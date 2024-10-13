<?php

namespace IBroStudio\PathSelectPrompt;

if (! function_exists('\IBroStudio\PathSelectPrompt\pathselect')) {
    /**
     * Prompt the user to select a path.
     *
     * @param  true|string  $required
     */
    function pathselect(string $label, string|false|null $root = null, int|string|null $default = null, string $target = 'directory', int $scroll = 5, mixed $validate = null, string $hint = 'Use right and left arrows to navigate in folders', bool|string $required = true): mixed
    {
        return (new PathSelectPrompt(...get_defined_vars()))->prompt();
    }
}
