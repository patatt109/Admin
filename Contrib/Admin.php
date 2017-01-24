<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 08/08/16 08:22
 */

namespace Modules\Admin\Contrib;


use Exception;
use Modules\Admin\Models\AdminConfig;
use Phact\Components\Flash;
use Phact\Exceptions\HttpException;
use Phact\Form\ModelForm;
use Phact\Helpers\ClassNames;
use Phact\Helpers\SmartProperties;
use Phact\Helpers\Text;
use Phact\Main\Phact;
use Phact\Orm\Expression;
use Phact\Orm\Fields\Field;
use Phact\Orm\Model;
use Phact\Orm\Q;
use Phact\Orm\QuerySet;
use Phact\Orm\TreeModel;
use Phact\Orm\TreeQuerySet;
use Phact\Pagination\Pagination;
use Phact\Template\Renderer;

abstract class Admin
{
    use SmartProperties, ClassNames, Renderer;

    public static $public = true;

    public $allTemplate = 'admin/all.tpl';
    public $listItemActionsTemplate = 'admin/list/_item_actions.tpl';
    public $listPaginationTemplate = 'admin/list/_pagination.tpl';

    public $createTemplate = 'admin/create.tpl';
    public $updateTemplate = 'admin/update.tpl';
    public $formTemplate = 'admin/form/_form.tpl';

    public $pageSize = 20;
    public $pageSizes = [20, 50, 100];

    /**
     * Sorting column
     *
     * @var null|string
     */
    public $sort = null;

    public $autoFixSort = true;

    public $parentId = null;

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
        /** @var Flash $flash */
        $flash = Phact::app()->flash;
        $request = Phact::app()->request;

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
        $message = 'Изменения успешно применены';

        if (is_array($result) && count($result) == 2 && is_bool($result[0]) && is_string($result[1])) {
            $success = $result[0];
            $message = $result[1];
        } elseif ($result !== true) {
            $success = false;
            if (is_string($result)) {
                $message = $result;
            } else {
                $message = 'При применении изменений произошла ошибка';
            }
        }

        if ($request->getIsAjax()) {
            $this->jsonResponse([
                'success' => $success,
                'message' => $message
            ]);
            Phact::app()->end();
        } else {
            $flash->add($message, $success ? 'success' : 'error');
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
        $config = AdminConfig::fetch(static::getModuleName(), static::classNameShort());
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
            } else {
                return $model->objects()->roots();
            }
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
        } else if ($this->sort) {
            $qs->setOrder([
                $this->sort
            ]);
        }
        return $qs;
    }

    /**
     * @param $qs QuerySet
     * @return mixed
     */
    public function fixSort($qs)
    {
        if ($this->sort && $this->autoFixSort && $this->getCanSort($qs)) {
            $newQs = clone($qs);
            $raw = $newQs->group([$this->sort])->having(new Expression('c > 1'))->values([$this->sort, new Expression('count(*) as c')]);
            if ($raw) {
                $newQs = clone($qs);
                $qLayer = $newQs->getQueryLayer();
                $queryBuilder = $qLayer->getQueryBuilderRaw();
                $queryBuilder->query('SET @position = 0;');

                $model = $this->getModel();
                $pk = $model->getPkAttribute();

                $newQs->order([$this->sort, $pk])->update([
                    $this->sort => new Expression("@position := (@position + 1)")
                ]);
            }
        }
        return $qs;
    }

    /**
     * @return array
     */
    public function getCommonData()
    {
        return [
            'admin' => $this,
            'adminClass' => static::classNameShort(),
            'moduleClass' => static::getModuleName()
        ];
    }

    public function getId()
    {
        return implode('-', [static::getModuleName(), static::classNameShort()]);
    }

    public function getAllUrl($parentId = null)
    {
        $route = 'admin:all';
        $params = [
            'module' => static::getModuleName(),
            'admin' => static::classNameShort()
        ];
        if ($parentId) {
            $route = 'admin:all_children';
            $params['parentId'] = $parentId;
        }
        return Phact::app()->router->url($route, $params);
    }

    public function getCreateUrl($parentId = null)
    {
        $route = 'admin:create';
        $params = [
            'module' => static::getModuleName(),
            'admin' => static::classNameShort()
        ];
        if ($parentId || $this->parentId) {
            $route = 'admin:create_child';
            $params['parentId'] = $parentId ? $parentId : $this->parentId;
        }
        return Phact::app()->router->url($route, $params);
    }

    public function getUpdateUrl($pk = null)
    {
        return Phact::app()->router->url('admin:update', [
            'module' => static::getModuleName(),
            'admin' => static::classNameShort(),
            'pk' => $pk
        ]);
    }

    public function getInfoUrl($pk = null)
    {
        return Phact::app()->router->url('admin:info', [
            'module' => static::getModuleName(),
            'admin' => static::classNameShort(),
            'pk' => $pk
        ]);
    }

    public function getRemoveUrl($pk = null)
    {
        return Phact::app()->router->url('admin:remove', [
            'module' => static::getModuleName(),
            'admin' => static::classNameShort(),
            'pk' => $pk
        ]);
    }

    public function getGroupActionUrl()
    {
        return Phact::app()->router->url('admin:group_action', [
            'module' => static::getModuleName(),
            'admin' => static::classNameShort()
        ]);
    }

    public function getSortUrl($parentId = null)
    {
        if ($this->sort || $this->getIsTree()) {
            $route = 'admin:sort';
            $params = [
                'module' => static::getModuleName(),
                'admin' => static::classNameShort()
            ];
            if ($parentId || $this->parentId) {
                $route = 'admin:sort_children';
                $params['parentId'] = $parentId ? $parentId : $this->parentId;
            }
            return Phact::app()->router->url($route, $params);
        }
        return null;
    }

    public function getColumnsUrl()
    {
        return Phact::app()->router->url('admin:columns', [
            'module' => static::getModuleName(),
            'admin' => static::classNameShort()
        ]);
    }

    public function getItemProperty(Model $item, $property)
    {
        $value = $item;
        $data = explode('__', $property);
        foreach ($data as $name) {
            $value = $value->{$name};
        }
        return $value;
    }

    public function all()
    {
        $search = isset($_GET['search']) ? $_GET['search'] : null;

        $qs = $this->getQuerySet();
        $qs = $this->handleSearch($qs, $search);
        $qs = $this->applyOrder($qs);
        $qs = $this->fixSort($qs);

        $pagination = new Pagination($qs, [
            'defaultPageSize' => $this->pageSize,
            'pageSizes' => $this->pageSizes
        ]);

        $this->render($this->allTemplate, [
            'objects' => $pagination->getData(),
            'pagination' => $pagination,
            'order' => $this->getOrder(),
            'search' => $this->getSearchColumns(),
            'columns' => $this->buildListColumns(),
            'canSort' => $this->getCanSort($qs)
        ]);
    }

    public function remove($pk)
    {
        $object = $this->getModelOr404($pk);
        $removed = $object->delete();
        if ($removed) {
            $data = ['success' => true];
        } else {
            $data = ['error' => 'При удалении объекта произошла ошибка'];
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
        return [true, "Объекты успешно удалены"];
    }

    public function render($template, $data = [])
    {
        echo $this->renderTemplate($template, array_merge($data, $this->getCommonData()));
    }

    public function jsonResponse($data = [])
    {
        header('Content-Type: application/json');
        echo json_encode($data);
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

        if ($this->getIsTree() && $parentId) {
            $model->parent_id = $parentId;
        }

        $form->setModel($model);
        $form->setInstance($model);

        $request = Phact::app()->request;
        if ($request->getIsPost() && $form->fill($_POST, $_FILES)) {
            if ($form->valid && $form->save()) {
                if ($request->getIsAjax()) {

                } else {
                    Phact::app()->flash->success('Изменения сохранены');
                    $next = isset($_POST['save']) ? $_POST['save']: 'save';
                    if ($next == 'save') {
                        $request->redirect($this->getAllUrl($this->parentId));
                    } elseif ($next == 'save-stay') {
                        $request->redirect($this->getUpdateUrl($model->pk));
                    } else {
                        $request->redirect($this->getCreateUrl($this->parentId));
                    }
                }
            } else {
                if (!$request->getIsAjax()) {
                    Phact::app()->flash->error('Пожалуйста, исправьте ошибки');
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

    /**
     * @param $qs QuerySet
     * @return bool
     */
    public function getCanSort($qs)
    {
        if ($this->sort) {
            $order = $qs->getOrder();
            return $order == [$this->sort];
        } else {
            return false;
        }
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
            $sortColumn = $this->sort;
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
        $config = AdminConfig::fetch(static::getModuleName(), static::classNameShort());
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

    /**
     * @return string
     */
    public static function getName()
    {
        return static::classNameShort();
    }

    /**
     * @return string
     */
    public static function getItemName()
    {
        return static::classNameShort();
    }
}