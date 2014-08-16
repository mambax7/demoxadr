<?php
namespace Geekwright\DemoXadr\Admin\Actions;

use Xmf\Xadr\Xadr;
use Xmf\Xadr\Action;

class IndexAction extends Action
{

    /**
     * This action does not handle execution.
     */
    public function execute()
    {
        return Xadr::RESPONSE_NONE;
    }

    /**
     * Retrieve the default response to be displayed when getRequestMethods() does
     * not return the current method.
     *
     * @return one of the defined responses
     */
    public function getDefaultResponse()
    {
        // our default response is the success response, since no validation or
        // execution will occur.
        return Xadr::RESPONSE_SUCCESS;
    }

    public function getRequestMethods()
    {
        // we want to skip validation and execution and go directly to the
        // responder, so we tell the framework that no request methods are served
        // by this action.
        return Xadr::REQUEST_NONE;
    }

    /**
     * Check that we have the admin environment -- $xoops->isAdminSide == true
     *
     * @return bool TRUE if action initializes successfully, otherwise FALSE.
     */
    public function initialize()
    {
        return \Xoops::getInstance()->isAdminSide;
    }

    /**
     * Determine if this action requires authentication.
     *
     * @return bool TRUE if this action requires authentication, otherwise FALSE.
     */
    public function isSecure()
    {
        return true;
    }
}
