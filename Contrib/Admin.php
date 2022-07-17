<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 08/08/16 08:22
 */

namespace Modules\Admin\Contrib;


use Modules\Admin\Models\AdminConfig;
use Phact\Components\FlashInterface;
use Phact\Exceptions\HttpException;
use Phact\Form\ModelForm;
use Phact\Helpers\ClassNames;
use Phact\Helpers\SmartProperties;
use Phact\Helpers\Text;
use Phact\Interfaces\AuthInterface;
use Phact\Module\Module;
use Phact\Orm\Expression;
use Phact\Orm\Fields\Field;
use Phact\Orm\Fields\PositionField;
use Phact\Orm\HasManyManager;
use Phact\Orm\ManyToManyManager;
use Phact\Orm\Model;
use Phact\Orm\Q;
use Phact\Orm\QuerySet;
use Phact\Orm\TreeModel;
use Phact\Orm\TreeQuerySet;
use Phact\Pagination\Pagination;
use Phact\Request\HttpRequestInterface;
use Phact\Router\RouterInterface;
use Phact\Template\Renderer;
use Phact\Translate\Translate;

abstract class Admin
{
    use SmartProperties, ClassNames, Renderer;

    /**
     * @var Model|TreeModel|null
     */
    protected $_instance;

    public $allTemplate = 'admin/all.tpl';

    public $listItemActionsTemplate = 'admin/list/_item_actions.tpl';
    public $listPaginationTemplate = 'admin/list/_pagination.tpl';

    public $createTemplate = 'admin/create.tpl';
    public $updateTemplate = 'admin/update.tpl';
    public $formTemplate = 'admin/form/_form.tpl';

    public $pageSize = 20;
    public $pageSizes = [20, 50, 100];

    /**
     * @var bool
     */
    public $autoFixSort = true;

    /**
     * @var string|null
     */
    protected $_sortColumn;

    /**
     * Parent id for TreeModel
     *
     * @var int|null
     */
    public $parentId = null;

    /**
     * Owner Pk for RelatedAdmins
     * @var null
     */
    public $ownerPk = null;

    /**
     * Owner attribute for RelatedAdmins or BoundAdmins
     * @var null
     */
    public static $ownerAttribute = null;

    /**
     * @var array
     */
    public $updateList = [];

    /**
     * @var Admin[]
     */
    protected $_boundAdmins;

    /**
     * @var ModelForm - used for bound admins
     */
    protected $_currentForm;

    /**
     * @var FlashInterface|null
     */
    protected $_flash;

    /**
     * @var RouterInterface
     */
    protected $_router;

    /**
     * @var HttpRequestInterface
     */
    protected $_request;

    /**
     * @var Translate|null
     */
    protected $_translate;

    /**
     * @var AuthInterface
     */
    protected $_auth;

    /**
     * Current admin name in module
     * @var string
     */
    protected $_name;

    /**
     * Module name
     * @var string
     */
    protected $_moduleName;

    public function __construct(
        string $name,
        string $moduleName,
        HttpRequestInterface $request,
        RouterInterface $router,
        AuthInterface $auth,
        FlashInterface $flash = null,
        Translate $translate = null
    )
    {
        $this->_name = $name;
        $this->_moduleName = $moduleName;
        $this->_router = $router;
        $this->_flash = $flash;
        $this->_request = $request;
        $this->_translate = $translate;
        $this->_auth = $auth;
    }

    /**
     * Sort attribute/column
     */
    public function getSortColumn()
    {
        if (is_null($this->_sortColumn)) {
            $model = $this->getModel();
            $fields = $model->getFields();
            $this->_sortColumn = false;
            foreach ($fields as $name => $config) {
                $class = null;
                if (is_string($config)) {
                    $class = $config;
                } elseif (is_array($config) && isset($config['class'])) {
                    $class = $config['class'];
                }
                if (is_a($class, PositionField::class, true)) {
                    $this->_sortColumn = $name;
                }
            }
        }
        return $this->_sortColumn;
    }
    /**
     * @return bool
     */
    public static function getIsPublic()
    {
        return !static::$ownerAttribute;
    }
    /**
     * Get current object
     *
     * @return null|Model|TreeModel
     */
    public function getInstance()
    {
        return $this->_instance;
    }

    /**
     * Set current object
     *
     * @param $instance
     * @return self
     */
    public function setInstance($instance)
    {
        $this->_instance = $instance;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAvailableListColumns()
    {
        return [
            'id' => [
                'title' => 'ID',
                'template' => 'admin/list/columns/default.tpl',
                'order' => 'id'
            ],
            '(string)' => [
                'title' => $this->getItemName(),
                'template' => 'admin/list/columns/default.tpl',
                'order' => 'id'
            ],
        ];
    }

    public function getListColumns()
    {
        return ['id', '(string)'];
    }

    public function getExcludedColumns()
    {
        $columns = [];
        if ($this->getIsTree()) {
            $columns[] = 'lft';
            $columns[] = 'rgt';
            $columns[] = 'root';
            $columns[] = 'depth';
        }
        if ($this::$ownerAttribute) {
            $columns[] = $this::$ownerAttribute;
        }
        return $columns;
    }

    /**
     * Available string options: "update", "view", "remove", "info", "create" (only for TreeModel)
     * @return array
     */
    public function getListItemActions()
    {
        return [
            'update',
            'view',
            'remove',
            'create'
        ];
    }

    /**
     * @return array
     *
     * Example:
     *
     * [
     *  'remove',
     *  'activate' => 'Activate items',
     *  'process' => [
     *      'title' => 'Process',
     *      'callback' => function ($qs, $ids) {
     *          $qs->filter(['status' => 1])->delete();
     *          return true;
     *      }
     *  ],
     * 'example' => [
     *      'title' => 'Example return',
     *      'callback' => function ($qs, $ids) {
     *          $qs->filter(['status' => 3])->delete();
     *          return [true, "Objects successfully removed"];
     *      }
     *  ],
     *  'do' => [
     *      'title' => 'Do some action',
     *      'callback' => [$this, 'do']
     *  ]
     * ]
     */
    public function getListGroupActions()
    {
        return [
            'update',
            'remove'
        ];
    }

    public function getListGroupActionsConfig()
    {
        $actions = $this->getListGroupActions();
        $result = [];
        foreach ($actions as $key => $item) {
            $title = null;
            $callback = null;

            if (is_numeric($key) && is_string($item)) {
                $id = $item;
            } elseif (is_string($key) && $item) {
                $id = $key;
                if (is_array($item)) {
                    $title = isset($item['title']) ? $item['title'] : [];
                    $callback = isset($item['callback']) ? $item['callback'] : [];
                } elseif (is_string($item)) {
                    $title = $item;
                }
            } else {
                continue;
            }
            if (!$title) {
                $title = Text::ucfirst($id);
            }
            if (!$callback) {
                $callback = [$this, 'group' . Text::ucfirst($id)];
            }
            $result[$id] = [
                'title' => $title,
                'callback' => $callback
            ];
        }
        return $result;
    }

    public function handleGroupAction($action, $pkList = [])
    {
        $request = $this->_request;

        $actions = $this->getListGroupActionsConfig();
        if (!isset($actions[$action])) {
            throw new HttpException(404);
        }
        $actionConfig = $actions[$action];
        $callback = $actionConfig['callback'];
        $qs = $this->getQuerySet();
        $qs = $qs->filter(['pk__in' => $pkList]);
        $result = call_user_func($callback, $qs, $pkList);

        $success = true;
        $message = $this->t('Admin.main', 'Changes successfully applied');

        if (is_array($result) && count($result) == 2 && is_bool($result[0]) && is_string($result[1])) {
            $success = $result[0];
            $message = $result[1];
        } elseif ($result !== true) {
            $success = false;
            if (is_string($result)) {
                $message = $result;
            } else {
                $message = $this->t('Admin.main', 'An error occurred while applying the changes');
            }
        }

        if ($request->getIsAjax()) {
            $this->jsonResponse([
                'success' => $success,
                'message' => $message
            ]);
            exit();
        } else {
            if ($this->_flash) {
                $this->_flash->add($message, $success ? 'success' : 'error');
            }
            $request->redirect($this->getAllUrl());
        }
    }

    public function getListDropDownGroupActions()
    {
        $actions = $this->getListGroupActionsConfig();
        if (array_key_exists('remove', $actions)) {
            unset($actions['remove']);
        }
        if (array_key_exists('update', $actions)) {
            unset($actions['update']);
        }
        return $actions;
    }

    /**
     * @TODO From cookies/db/etc
     * @return null|string[]
     */
    public function getUserColumns()
    {
        $config = AdminConfig::fetch($this->_moduleName, $this->_name, $this->_auth->getUser()->getLogin());
        return $config->getColumnsList();
    }

    public function buildListColumns()
    {
        $defaultColumns = $this->getListColumns();
        $userColumns = $this->getUserColumns();

        $availableColumns = $this->getAvailableListColumns();
        $excludedColumns = $this->getExcludedColumns();
        $fields = $this->getModel()->getFields();

        $config = [];
        $enabled = [];
        foreach ($defaultColumns as $key => $value) {
            if (is_string($key) && is_array($value)) {
                $enabled[] = $value;
                $config[$key] = $value;
            } elseif (is_string($value)) {
                $config[$value] = [];
                $enabled[] = $value;
            }
        }
        foreach ($availableColumns as $key => $value) {
            if (is_string($key) && is_array($value) && (!array_key_exists($key, $config) || empty($config[$key]))) {
                $config[$key] = $value;
            } elseif (is_string($value) && !array_key_exists($value, $config)) {
                $config[$value] = [];
            }
        }
        foreach ($fields as $name => $field) {
            if (is_array($field) && !in_array($name, $excludedColumns)) {
                $columnConfig = isset($config[$name]) ? $config[$name] : [];
                if (!isset($columnConfig['title']) && isset($field['label'])) {
                    $columnConfig['title'] = $field['label'];
                }
                if (!isset($columnConfig['order'])) {
                    /** @var Field $modelField */
                    $modelField = $this->getModel()->getField($name);
                    $attribute = $modelField->getAttributeName();
                    if ($attribute) {
                        $columnConfig['order'] = $attribute;
                    }
                }
                $columnConfig['template'] = 'admin/list/columns/default.tpl';
                $config[$name] = $columnConfig;
            }
        }
        foreach ($config as $key => $item) {
            if (!isset($item['title'])) {
                $config[$key]['title'] = ucfirst($key);
            }
            if (!isset($item['order'])) {
                $config[$key]['order'] = null;
            }
            if (!isset($item['template'])) {
                $config[$key]['template'] = 'admin/list/columns/default.tpl';
            }
        }
        if ($userColumns) {
            $safeUserColumns = [];
            foreach ($userColumns as $column) {
                if (array_key_exists($column, $config)) {
                    $safeUserColumns[] = $column;
                }
            }
            if ($safeUserColumns) {
                $enabled = $safeUserColumns;
            }
        }

        return [
            'enabled' => $enabled,
            'config' => $config
        ];
    }

    public function getSearchColumns()
    {
        return [];
    }

    /**
     * @return Model|TreeModel
     */
    abstract public function getModel();

    /**
     * @return bool
     */
    public function getIsTree()
    {
        return $this->getModel() instanceof TreeModel;
    }

    /**
     * @return TreeModel|null
     */
    public function getTreeParent()
    {
        if ($this->getIsTree() && $this->parentId) {
            $model = $this->getModel();
            return $model->objects()->filter(['id' => $this->parentId])->get();
        }
        return null;
    }

    /**
     * @return Model
     */
    public function newModel()
    {
        $model = $this->getModel();
        return new $model;
    }

    /**
     * @return ModelForm
     */
    public function getForm()
    {
        return new ModelForm();
    }

    /**
     * @return ModelForm
     */
    public function getUpdateForm()
    {
        return $this->getForm();
    }

    /**
     * @return QuerySet|TreeQuerySet
     */
    public function getQuerySet()
    {
        /** @var Model|TreeModel $model */
        $model = $this->getModel();
        if ($this->getIsTree()) {
            $parent = $this->getTreeParent();
            if ($parent) {
                return $parent->objects()->children();
            }
            return $model->objects()->roots();
        }
        if ($this::$ownerAttribute && $this->ownerPk) {
            return $model->objects()->filter([
                $this::$ownerAttribute => $this->ownerPk
            ]);
        }
        return $model->objects()->getQuerySet();
    }

    /**
     * @return array|null
     */
    public function getOrder()
    {
        $order = isset($_GET['order']) ? $_GET['order'] : null;
        if ($order) {
            $clean = $order;
            $asc = true;
            if (Text::startsWith($clean, '-')) {
                $asc = false;
                $clean = mb_substr($clean, 1);
            }
            return [
                'raw' => $order,
                'clean' => $clean,
                'asc' => $asc,
                'desc' => !$asc
            ];
        }
        return null;
    }

    /**
     * @param $qs QuerySet
     * @return QuerySet
     */
    public function handleSearch($qs, $search)
    {
        $columns = $this->getSearchColumns();
        if ($search && $columns) {
            $orData = [];
            foreach ($columns as $column) {
                $orData[] = [$column . '__contains' => $search];
            }
            $filter = call_user_func_array([Q::class, 'orQ'], $orData);
            $qs = $qs->filter($filter);
        }
        return $qs;
    }

    /**
     * @param $qs QuerySet
     * @return QuerySet
     */
    public function applyOrder($qs)
    {
        $order = $this->getOrder();

        if ($order && isset($order['raw'])) {
            $qs->setOrder([
                $order['raw']
            ]);
        } else if ($sort = $this->getSortColumn()) {
            $qs->setOrder([
                $sort
            ]);
        }
        return $qs;
    }

    /**
     * Fix duplicate position values
     * @param $qs QuerySet
     * @return mixed
     */
    public function fixSort($qs)
    {
        if (($sort = $this->getSortColumn()) && $this->autoFixSort && $this->getCanSort($qs)) {

            $model = $this->getModel();
            $pk = $model->getPkAttribute();

            while ($needFix = (clone($qs))->
                group([$sort])->
                limit(1)->
                having(new Expression('c > 1'))->
                values([$sort, new Expression('count(*) as c')]
            )) {
                $item = $needFix[0];

                $ids = (clone($qs))->filter([
                    $sort => $item[$sort]
                ])->offset(1)->order([$pk])->values([$pk], true);

                $increaseSort = count($ids);

                // Update records with greater positions
                (clone($qs))->filter([
                    "{$sort}__gt" => $item[$sort]
                ])->update([
                    $sort => new Expression("{{$sort}} + ?", [$increaseSort])
                ]);

                // Update records with duplicate positions
                foreach ($ids as $key => $id) {
                    $position = $key + 1;
                    (clone($qs))->filter([
                        $pk => $id
                    ])->update([
                        $sort => new Expression("{{$sort}} + ?", [$position])
                    ]);
                }
            }
        }
        return $qs;
    }

    public function getId()
    {
        return implode('-', [$this->_moduleName, $this->_name]);
    }

    public function getAllUrl($parentId = null)
    {
        $route = 'admin:all';
        $params = [
            'module' => $this->_moduleName,
            'admin' => $this->_name
        ];
        if ($parentId) {
            $route = 'admin:all_children';
            $params['parentId'] = $parentId;
        }
        return $this->_router->url($route, $params);
    }

    public function getCreateUrl($parentId = null)
    {
        $route = 'admin:create';
        $params = [
            'module' => $this->_moduleName,
            'admin' => $this->_name
        ];
        if ($parentId || $this->parentId) {
            $route = 'admin:create_child';
            $params['parentId'] = $parentId ? $parentId : $this->parentId;
        }
        if ($this->ownerPk) {
            $params['ownerPk'] = $this->ownerPk;
        }
        return $this->_router->url($route, $params);
    }

    public function getUpdateUrl($pk = null)
    {
        return $this->_router->url('admin:update', [
            'module' => $this->_moduleName,
            'admin' => $this->_name,
            'pk' => $pk
        ]);
    }

    public function getInfoUrl($pk = null)
    {
        return $this->_router->url('admin:info', [
            'module' => $this->_moduleName,
            'admin' => $this->_name,
            'pk' => $pk
        ]);
    }

    public function getRemoveUrl($pk = null)
    {
        return $this->_router->url('admin:remove', [
            'module' => $this->_moduleName,
            'admin' => $this->_name,
            'pk' => $pk
        ]);
    }

    public function getGroupActionUrl()
    {
        return $this->_router->url('admin:group_action', [
            'module' => $this->_moduleName,
            'admin' => $this->_name,
        ]);
    }

    public function getSortUrl($parentId = null)
    {
        if (($sort = $this->getSortColumn()) || $this->getIsTree()) {
            $route = 'admin:sort';
            $params = [
                'module' => $this->_moduleName,
                'admin' => $this->_name
            ];
            if ($parentId || $this->parentId) {
                $route = 'admin:sort_children';
                $params['parentId'] = $parentId ? $parentId : $this->parentId;
            }
            return $this->_router->url($route, $params);
        }
        return null;
    }

    public function getColumnsUrl()
    {
        return $this->_router->url('admin:columns', [
            'module' => $this->_moduleName,
            'admin' => $this->_name
        ]);
    }

    public function getItemProperty(Model $item, $property)
    {
        $value = $item;
        $data = explode('__', $property);
        foreach ($data as $name) {
            if (is_object($value)) {
                $value = $value->{$name};
            } else {
                return null;
            }
        }
        if ($value instanceof ManyToManyManager || $value instanceof HasManyManager) {
            $value = $value->all();
        }
        return $value;
    }

    public function all($template = null)
    {
        $template = $template ?: $this->allTemplate;
        $search = isset($_GET['search']) ? $_GET['search'] : null;

        $qs = $this->getQuerySet();
        $qs = $this->handleSearch($qs, $search);
        $qs = $this->applyOrder($qs);
        $qs = $this->fixSort($qs);

        $pagination = new Pagination($qs, [
            'defaultPageSize' => $this->pageSize,
            'pageSizes' => $this->pageSizes
        ]);

        $updateForm = null;
        if ($this->updateList) {
            $updateForm = $this->getUpdateForm();
        }

        $this->render($template, [
            'objects' => $pagination->getData(),
            'pagination' => $pagination,
            'order' => $this->getOrder(),
            'search' => $this->getSearchColumns(),
            'columns' => $this->buildListColumns(),
            'canSort' => $this->getCanSort($qs),
            'updateList' => $this->updateList,
            'updateForm' => $updateForm
        ]);
    }

    public function remove($pk)
    {
        $object = $this->getModelOr404($pk);
        $removed = $object->delete();
        if ($removed) {
            $data = ['success' => true];
        } else {
            $data = ['error' => $this->t('Admin.main', 'An error occurred while deleting the object')];
        }
        $this->jsonResponse($data);
    }

    /**
     * @param $qs QuerySet
     * @param $pkList
     * @return bool
     */
    public function groupRemove($qs, $pkList)
    {
        $qs->delete();
        return [true, $this->t('Admin.main', 'Objects deleted successfully')];
    }



    public function groupUpdate()
    {
        $updateForm = $this->getUpdateForm();
        $errors = $this->handleGroupUpdateForms($updateForm, $this->updateList, function($form) {
            /** @var $form ModelForm */
            if (!$form->valid) {
                return $form->getErrors();
            }
            return [];
        });
        $response = [
            'status' => 'success'
        ];
        if (!$errors) {
            $this->handleGroupUpdateForms($updateForm, $this->updateList, function($form) {
                /** @var $form ModelForm */
                $form->save();
            });
        } else {
            $response['status'] = 'error';
            $response['errors'] = $errors;
        }
        $this->jsonResponse($response);
    }

    /**
     * @param $form ModelForm
     * @param $pkList int[]
     * @param $callable callable
     * @return array
     */
    public function handleGroupUpdateForms($form, $pkList, $callable)
    {
        $errors = [];
        foreach ($pkList as $pk) {
            $object = $this->getQuerySet()->filter(['pk' => $pk])->get();
            if ($object) {
                $form->prefix = $pk . '_';
                $form->setInstance($object);
                $form->setInstanceValues();
                if ($form->fill($_POST, $_FILES)) {
                    $formErrors = $callable($form);
                    if ($formErrors) {
                        $errors[$form->getName()] = $formErrors;
                    }
                }
            }
        }
        return $errors;
    }

    public function render($template, $data = [])
    {
        echo $this->renderTemplate($template, array_merge($data, [
            'admin' => $this,
            'adminName' => $this->_name,
            'moduleName' => $this->_moduleName
        ]));
    }

    public function jsonResponse($data = [])
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * @param $pk
     * @return null|Model
     * @throws HttpException
     */
    public function getModelOr404($pk)
    {
        $object = $this->getModel()->objects()->filter(['pk' => $pk])->limit(1)->get();
        if (!$object) {
            throw new HttpException(404);
        }
        return $object;
    }

    public function getFormFieldsets()
    {
        return null;
    }

    public function create($parentId = null)
    {
        $this->update(null, $parentId);
    }

    public function update($pk = null, $parentId = null)
    {
        $new = false;
        if (is_null($pk)) {
            $new = true;
            $model = $this->newModel();
            $form = $this->getForm();
        } else {
            $model = $this->getModelOr404($pk);
            $form = $this->getUpdateForm();
        }

        if ($this::$ownerAttribute) {
            $form->exclude[] = $this::$ownerAttribute;
        }
        $this->setInstance($model);
        if ($this->getIsTree() && $parentId) {
            $model->parent_id = $parentId;
        }

        $form->setModel($model);
        $form->setInstance($model);

        $request = $this->_request;

        if ($request->getIsPost() && $form->fill($_POST, $_FILES) && $this->fillBound($_POST, $_FILES)) {
            $safeAttributes = [];
            if ($this::$ownerAttribute && $this->ownerPk) {
                $safeAttributes[$this::$ownerAttribute] = $this->ownerPk;
            }
            if ($form->valid && $this->validBound()) {
                $form->save($safeAttributes);
                $this->saveBound($form->getInstance());
                if ($request->getIsAjax()) {
                    $this->jsonResponse([
                        'content' => $this->renderTemplate('admin/form/ajax_success.tpl'),
                        'status' => 'success'
                    ]);
                    exit();
                }

                if ($this->_flash) {
                    $this->_flash->success($this->t('Admin.main', 'Changes saved'));
                }

                $next = isset($_POST['save']) ? $_POST['save']: 'save';
                if ($next === 'save') {
                    $request->redirect($this->getAllUrl($this->parentId));
                } elseif ($next === 'save-stay') {
                    $request->redirect($this->getUpdateUrl($model->pk));
                } else {
                    $request->redirect($this->getCreateUrl($this->parentId));
                }
            } else {
                if (!$request->getIsAjax()) {
                    $this->_flash->error($this->t('Admin.main', 'Please correct errors'));
                }
            }
        }
        
        $template = $new ? $this->createTemplate : $this->updateTemplate;
        $this->render($template, [
            'form' => $form,
            'model' => $model,
            'new' => $new
        ]);
    }

    public function fillBound($data, $files)
    {
        $filled = true;
        foreach ($this->getInitBoundAdmins() as $bound) {
            $form = $bound->getCurrentForm();
            $filled = $filled && $form->fill($data, $files);
        }
        return $filled;
    }

    public function validBound()
    {
        $valid = true;
        foreach ($this->getInitBoundAdmins() as $bound) {
            $form = $bound->getCurrentForm();
            $valid = $valid && $form->valid;
        }
        return $valid;
    }

    public function saveBound($ownerInstance)
    {
        foreach ($this->getInitBoundAdmins() as $bound) {
            $form = $bound->getCurrentForm();
            if ($bound instanceof BoundAdmin) {
                $bound->save($form, $ownerInstance);
            } else {
                $form->save([$bound::$ownerAttribute => $ownerInstance->pk]);
            }
        }
    }

    /**
     * @param $qs QuerySet
     * @return bool
     */
    public function getCanSort($qs)
    {
        if ($sort = $this->getSortColumn()) {
            $order = $qs->getOrder();
            return $order == [$sort];
        }
        return false;
    }

    public function sort($pkList, $to, $prev, $next)
    {
        /** @var Model|TreeModel $model */
        $model = $this->getQuerySet()->filter(['pk' => $to])->get();
        if ($this->getIsTree()) {
            if ($model) {
                if ($model->getIsRoot()) {
                    /** @var TreeModel[] $roots */
                    $roots = $model->objects()->filter(['pk__in' => $pkList])->all();
                    $old = [];
                    $descendants = [];
                    foreach ($roots as $root) {
                        $descendants[$root->id] = $root->objects()->descendants(true)->values(['id'], true);
                        $old[] = $root->root;
                    }
                    asort($old);
                    foreach ($pkList as $pk) {
                        $newRoot = array_shift($old);
                        $model->objects()->filter(['pk__in' => $descendants[$pk]])->update([
                            'root' => $newRoot
                        ]);
                    }
                } else {
                    if ($prev && ($prevModel = $model->objects()->filter(['pk' => $prev])->get())) {
                        $model->setAfter($prevModel);
                    } elseif ($next && ($nextModel = $model->objects()->filter(['pk' => $next])->get())) {
                        $model->setBefore($nextModel);
                    }
                }
            }
        } else {
            $sortColumn = $this->getSortColumn();
            $positions = $this->getQuerySet()->filter(['pk__in' => $pkList])->values([$sortColumn], true);
            asort($positions);
            $result = array_combine($pkList, $positions);
            foreach ($result as $pk => $position) {
                $this->getModel()->objects()->filter(['pk' => $pk])->update([$sortColumn => $position]);
            }
        }
        $this->jsonResponse([
            'success' => true
        ]);
    }

    public function setColumns($columns)
    {
        $config = AdminConfig::fetch($this->_moduleName, $this->_name, $this->_auth->getUser()->getLogin());
        $config->setColumnsList($columns);
        $this->jsonResponse([
            'success' => true
        ]);
    }

    public function getBreadcrumbs()
    {
        $breadcrumbs = [];
        $breadcrumbs[] = [
            'name' => $this->getName(),
            'url' => $this->getAllUrl()
        ];
        $parent = $this->getTreeParent();
        if ($parent) {
            $ancestors = $parent->objects()->ancestors(true)->all();
            foreach ($ancestors as $ancestor) {
                $breadcrumbs[] = [
                    'name' => (string) $ancestor,
                    'url' => $this->getAllUrl($ancestor->pk)
                ];
            }
        }
        return $breadcrumbs;
    }

    public function getRelatedAdmins()
    {
        return [];
    }

    public function getInitRelatedAdmins()
    {
        $config = $this->getRelatedAdmins();
        $instance = $this->getInstance();
        $admins = [];
        foreach ($config as $relation => $class) {
            /** @var Admin $admin */
            $admin = new $class($class::classNameShort(), $this->_moduleName, $this->_request, $this->_router, $this->_auth, $this->_flash, $this->_translate);
            if ($instance && $instance->pk) {
                $admin->ownerPk = $instance->pk;
            }
            $admin->afterInit();
            $admins[] = $admin;
        }
        return $admins;
    }

    public function getBoundAdmins()
    {
        return [];
    }

    public function getInitBoundAdmins()
    {
        if (is_null($this->_boundAdmins)) {
            $config = $this->getBoundAdmins();
            $instance = $this->getInstance();
            $this->_boundAdmins = [];
            foreach ($config as $relation => $class) {
                /** @var Admin $admin */
                $admin = new $class($class::classNameShort(), $this->_moduleName, $this->_request, $this->_router, $this->_auth, $this->_flash, $this->_translate);
                if ($instance && $instance->pk) {
                    $admin->ownerPk = $instance->pk;
                }

                // Fetch bound instance
                $ownerInstance = $this->getInstance();
                if ($admin instanceof BoundAdmin) {
                    $model = $admin->fetchModel($ownerInstance);
                } else {
                    $class = $admin->getModel();
                    $model = $class::objects()->filter([$admin::$ownerAttribute => $ownerInstance->pk])->get();
                }
                $model = $model ?: $admin->newModel();

                // Make bound form
                $form = $admin->getForm();
                $form->setModel($model);
                $form->setInstance($model);
                $admin->setCurrentForm($form);

                $admin->afterInit();

                $this->_boundAdmins[] = $admin;
            }
        }
        return $this->_boundAdmins;
    }

    public function setCurrentForm($form)
    {
        $this->_currentForm = $form;
    }

    public function getCurrentForm()
    {
        return $this->_currentForm;
    }

    public function afterInit()
    {
        $id = $this->getId();
        $name = 'update_' . $id;
        if (isset($_GET[$name]) && ($updateList = $_GET[$name])) {
            if (!is_array($updateList)) {
                $updateList = [$updateList];
            }
            $this->updateList = $updateList;
            if ($this->_request->getIsPost()) {
                $this->groupUpdate();
            }
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return string
     */
    public function getItemName()
    {
        return $this->_name;
    }

    /**
     * Translate
     *
     * @param $domain
     * @param string $key
     * @param null $number
     * @param array $parameters
     * @param null $locale
     * @return string
     */
    public function t($domain, $key = "", $number = null, $parameters = [], $locale = null)
    {
        if ($this->_translate) {
            return $this->_translate->t($domain, $key, $number, $parameters, $locale);
        }
        return $key;
    }

    public function getViewUrl(Model $model): ?string
    {
        return null;
    }
}