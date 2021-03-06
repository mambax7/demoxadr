<?php
namespace Geekwright\DemoXadr\App\Actions;

use Xmf\Xadr\Xadr;
use Xmf\Xadr\Action;
use Xmf\Xadr\CatalogedPrivilege;
use Xmf\Xadr\ResponseSelector;
use Xmf\Xadr\ValidatorManager;

class TodoEndWorkAction extends Action
{
    /**
     * @var Catalog a catalog object
     */
    protected $catalog = null;

    public function execute()
    {
        $todo = $this->request()->attributes()->get('todo');
        $todoHandler = $this->request()->attributes()->get('todoHandler');

        if ($todo->getVar('todo_active') && $todo->getVar('todo_lock_id')) {
            $logHandler = $this->controller()->getHandler('log');
            $log = $logHandler->get($todo->getVar('todo_lock_id'));
            if (is_object($log)) {
                $log->setVar('log_end_time', time());
                $log->updateWorkTime();
                $logHandler->insert($log);
            }
            $todo->setVar('todo_lock_id', 0);
            $todo->updateTotalTime();
            $todoHandler->insert($todo);
            $this->request()->attributes()->set('message', 'Work ended.');
        } else {
            $this->request()->setError('TodoEndWork', 'Todo entry is not eligble for this operation.');

            return new ResponseSelector(Xadr::RESPONSE_ERROR);
        }

        $this->controller()->forward('App', 'TodoDetail');

        return new ResponseSelector(Xadr::RESPONSE_NONE);
    }

    public function getDefaultResponse()
    {
        return new ResponseSelector(Xadr::RESPONSE_INDEX, 'App', 'TodoList');
    }

    /**
     * Retrieve the privilege required to access this action.
     */
    public function getRequiredPrivilege()
    {
        $return=null;

        $todo = $this->request()->attributes()->get('todo');
        if (is_object($todo)) {
            $todo_uid = $todo->getVar('todo_uid');
            if ($todo_uid!=$this->user()->id()) {
                $return = new CatalogedPrivilege('todo_permisions', 'edit_others_todo', $this->catalog);
            }
        }

        return $return;
    }

    public function getRequestMethods()
    {
        return Xadr::REQUEST_POST;
    }

    public function registerValidators(ValidatorManager $validatorManager)
    {
        $form_definition=$this->request()->attributes()->get('_fields');
        $fields=$form_definition['fields'];

        foreach ($fields as $fieldname => $fielddef) {
            $validators = $fielddef['input']['validate'];
            foreach ($validators as $validate) {
                $validatorManager->addValidation($fieldname, $validate['type'], $validate['criteria']);
            }
            if ($fielddef['required']) {
                $validatorManager->setRequired($fieldname, true);
            }
        }
    }

    public function validate()
    {
        $xoops = \Xoops::getInstance();
        if (!$xoops->security()->check()) {
            $msg = implode(',', $xoops->security()->getErrors());
            $this->request()->setError('global:xoopsSecurity', (empty($msg)?'Security check failed':$msg));
            return false;
        }

        $todo = $this->request()->attributes()->get('todo');
        if (!is_object($todo)) {
            $this->request()->setError('TodoFlipStatus', 'Requested todo item not found.');

            return false;
        }

        return true;
    }

    public function initialize()
    {
        $this->catalog = $this->domain()->getDomain('DemoXadrCatalog');

        $todoHandler = $this->controller()->getHandler('todo');

        $todo_id = $this->request()->getParameter('todo_id');
        $todo = $todoHandler->get($todo_id);
        $this->request()->attributes()->set('todo', $todo);
        $this->request()->attributes()->set('todoHandler', $todoHandler);

        $fields=array();

        $fields['todo_id'] = array(
                  'type' => 'integer'
                , 'length' => 10
                , 'default' => 0
                , 'description' => 'Todo Item'
                , 'required' => true
                , 'display' => array(
                      'form' => 'text'
                    , 'transform' => ''
                    )
                , 'input' => array(
                      'form' => 'text'
                    , 'validate' => array(
                              array(
                                  'type'=> 'Clean',
                                  'criteria' => array(
                                        'type' => 'int'
                                    ),
                                )
                            , array(
                                  'type'=> 'Number'
                                , 'criteria' => array(
                                      'trim'        => true
                                    , 'min'         => 1
                                    , 'min_error'   => 'A todo item is required'
                                    )
                                )
                            )
                        )
                    );

        $fielddefs=array('fields'=>$fields);
        $this->request()->attributes()->set('_fields', $fielddefs);

        return true;
    }
}
