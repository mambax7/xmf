<?php

namespace Xmf\Mvc;

/**
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 *
 * @author          Richard Griffith
 * @author          Sean Kerr
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright       Portions Copyright (c) 2003 Sean Kerr
 * @license         (license terms)
 * @package         Xmf\Mvc
 * @since           1.0
 */

/**
 * The XoopsAuthHandler implements an AuthorizationHandler that
 * uses XOOPS for user authentication.
 *
 * If a user has not signed in and attempts access to a secure Action,
 * the session will redirect to the system login with the xoops_redirect
 * option set to return to reattempt the secure Action.
 */
class XoopsAuthHandler extends AuthorizationHandler
{

    /**
     * Create a new PrivilegeAuthorizationHandler instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {
        parent::__construct ();
    }

    /**
     * Determine the user authorization status for an action request by
     * verifying against a required privilege.
     *
     *  _This should never be called manually._
     *
     * @param $action     An Action instance.
     *
     * @since  1.0
     */
    public function execute (&$action)
    {

        if (!$this->User()->isAuthenticated()) {
            // restore_error_handler(); error_reporting(-1); // tough to debug
            // if we need to authenticate, do XOOPS login rather than
            // using AUTH_UNIT AUTH_ACTION conventions

            $url=$this->Controller()->getControllerPath();
            if (isset($_SERVER['QUERY_STRING'])) {
                $query=\Xmf\Request::getString('QUERY_STRING','','server');
                $url=$this->Controller()->getControllerPath().'?'.urlencode ($query);
            }
            $parts=parse_url($url);
            $url=$parts['path'].(empty($parts['query'])?'':'?'.$parts['query']);

            redirect_header(XOOPS_URL.'/user.php?xoops_redirect='.$url, 2, _NOPERM);

        }

        $privilege = $action->getPrivilege();

        if (is_array($privilege) && !isset($privilege[1])) {
            // use secure unit as default namespace
            $privilege[1] = Config::get('SECURE_UNIT', 'Default');
        }

        if ($privilege != NULL &&
           !$this->User()->hasPrivilege($privilege[0], $privilege[1]))
        {
            $secure_unit=Config::get('SECURE_UNIT', 'Default');
            $secure_action=Config::get('SECURE_ACTION', 'NoPermission');
            // user doesn't have privilege to access
            if ($this->Controller()->actionExists($secure_unit, $secure_action)) {
                $this->Controller()->forward($secure_unit, $secure_action);

                return FALSE;
            }

            // cannot find secure action
            $error = 'Invalid configuration setting(s): ' .
                     'SECURE_UNIT (' . $secure_unit . ') or ' .
                     'SECURE_ACTION (' . $secure_action . ')';

            trigger_error($error, E_USER_ERROR);

            exit;

        }

        // user is authenticated, and has the required privilege or a privilege
        // is not required
        return TRUE;

    }

}
