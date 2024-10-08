<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf;

use Kint\Kint;
/**
 * Debugging tools for developers
 *
 * @category  Xmf\Debug
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2021 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Debug extends Kint
{
    /**
     * doOnce - do some local housekeeping on first use. Any method needing this
     * assist just calls every time, the one time logic is all here.
     *
     * @return void
     */
    private static function doOnce()
    {
        static $done;
        if (true !== $done) {
            $done = true;
            $class = get_called_class();
            parent::$aliases[] = array($class, 'dump');
            parent::$aliases[] = array($class, 'backtrace');
            parent::$enabled_mode = true;
            parent::$mode_default = Kint::MODE_RICH;
            // display output inline ::folder = false, true puts all output at bottom of window
            \Kint\Renderer\RichRenderer::$folder = false;
            // options: 'original' (default), 'solarized', 'solarized-dark' and 'aante-light'
            \Kint\Renderer\RichRenderer::$theme = 'aante-light.css';
        }
    }

    /**
     * Dump one or more variables
     *
    * @psalm-param array ...$args
     * @return void
     */
    public static function dump(...$args)
    {

        static::doOnce();
        parent::dump(...$args);
    }

    /**
     * Display debug backtrace
     *
     * @return void
     */
    public static function backtrace()
    {
        static::doOnce();
        static::trace();
    }

    /**
     * start_trace - turn on xdebug trace
     *
     * Requires xdebug extension
     *
     * @param string $tracefile      file name for trace file
     * @param string $collect_params argument for ini_set('xdebug.collect_params',?)
     *                             Controls display of parameters in trace output
     * @param string $collect_return argument for ini_set('xdebug.collect_return',?)
     *                             Controls display of function return value in trace
     *
     * @return void
     */
    public static function startTrace($tracefile = '', $collect_params = '3', $collect_return = 'On')
    {
        if (function_exists('xdebug_start_trace')) {
            ini_set('xdebug.collect_params', $collect_params);
            ini_set('xdebug.collect_return', $collect_return);
            if ($tracefile == '') {
                $tracefile = XOOPS_VAR_PATH . '/logs/php_trace';
            }
            xdebug_start_trace($tracefile);
        }
    }

    /**
     * stop_trace - turn off xdebug trace
     *
     * Requires xdebug extension
     *
     * @return void
     */
    public static function stopTrace()
    {
        if (function_exists('xdebug_stop_trace')) {
            xdebug_stop_trace();
        }
    }
}
