<?php

namespace App\Util;

/**
 * Class SecurityUtil
 *
 * Util for security related functionality
 *
 * @package App\Util
 */
class SecurityUtil
{
    /**
     * Escape string to secure string format
     *
     * @param string $string The string to escape
     *
     * @return string|null The escaped string
     */
    public function escapeString(string $string): ?string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
