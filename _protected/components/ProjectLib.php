<?php
namespace app\components;

use app\models\Catalog;
use app\models\Page;
use app\models\PageHistory;
use app\models\Project;
use app\models\ProjectMember;
use app\models\User;
use Yii;
use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;

class ProjectLib
{
    private static $instance;

    /**
     * @return ProjectLib
     */
    public static function getInstance(){
        if(empty(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __construct() { }
    public function __clone() { throw new \Exception('Clone is not allowed !'); }

    public function getMenu($projectId)
    {
        $projectId = intval($projectId);
        $cacheKey = "Project:Menu:$projectId";
        $cache = \Yii::$app->getCache()->get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $menu = [['label' => '>  '.Yii::t('app', 'Document List'), 'options' => ['class' => 'header']]];
        //项目根目录页面
        $pages = Page::find()
            ->where('`status`='.Page::STATUS_NORMAL)
            ->andWhere('`project_id`=:project_id and `catalog_id`=:catalog_id', [
                ':project_id' => $projectId,
                ':catalog_id' => 0
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        /** @var Page $page */
        foreach ($pages as $page) {
             $menu[] = [
                 'label' => $page->title,
                 'icon' => 'fa fa-file-text-o',
                 'url' => ['/page/view', 'id'=>$page->id, 'project_id'=>$projectId],
             ];
        }
        unset($pages);

        $cats = Catalog::find()
            ->where('`status`='.Catalog::STATUS_NORMAL)
            ->andWhere('`project_id`=:project_id and `parent_id`=:parent_id',[
                ':project_id' => $projectId,
                ':parent_id' => 0
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        /** @var Catalog $cat */
        foreach ($cats as $cat) {
            $menu[] = [
                'label' => $cat->name,
                'icon' => 'fa fa-folder-o',
                'url' => 'javascript:;',
                'items' => $this->getCatSubMenu($projectId, $cat->id),
            ];
        }
        unset($cats);

        $menu[] = [
            'label' => '==== ====',
            'options' => [
                'class' => 'header',
                'style'=>'padding:0 10px;letter-spacing:20px;'
            ]
        ];

        $menu[] = [
            'label' => Yii::t('app', 'Create Page'),
            'icon' => 'fa fa-file-o',
            'url' => ['/page/create','project_id'=>$projectId]
        ];
        $menu[] = [
            'label' => Yii::t('app', 'Create Catalog'),
            'icon' => 'fa fa-plus-square-o',
            'url' => ['/catalog/create','project_id'=>$projectId]
        ];
        $menu[] = [
            'label' => Yii::t('app', 'Manage Actions'),
            'icon' => 'fa fa-gear',
            'url' => 'javascript:;',
            'items' => [
                [
                    'label' => Yii::t('app', 'Manage Project Document'),
                    'icon' => 'fa fa-folder-open-o',
                    'url' => ['/project/manage','id'=>$projectId]
                ],
                [
                    'label' => Yii::t('app', 'Manage Project Member'),
                    'icon' => 'fa fa-users',
                    'url' => ['/project/member','id'=>$projectId]
                ],
            ],
        ];
 
        \Yii::$app->getCache()->set($cacheKey, $menu);
        return $menu;
    }

    public function getCatSubMenu($projectId, $catId)
    {
        $projectId = intval($projectId);
        $catId = intval($catId);
        $menu = [];
        $pages = Page::find()
            ->where('`status`='.Page::STATUS_NORMAL)
            ->andWhere('`project_id`=:project_id and `catalog_id`=:catalog_id', [
                ':project_id' => $projectId,
                ':catalog_id' => $catId
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        /** @var Page $page */
        foreach ($pages as $page) {
            $menu[] = [
                'label' => $page->title,
                'icon' => 'fa fa-file-text-o',
                'url' => ['/page/view', 'id'=>$page->id, 'project_id'=>$projectId],
            ];
        }
        unset($pages);

        $cats = Catalog::find()
            ->where('`status`='.Catalog::STATUS_NORMAL)
            ->andWhere('`parent_id`=:paid',[
                ':paid' => $catId
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        /** @var Catalog $cat */
        foreach ($cats as $cat) {
            $menu[] = [
                'label' => $cat->name,
                'icon' => 'fa fa-folder-o',
                'url' => 'javascript:;',
                'items' => $this->getCatSubMenu($projectId, $cat->id),
            ];
        }
        unset($cats);

        return $menu;
    }


    public function getCatOptions($projectId,$parentId=0,$depth = 3,$level=1)
    {
        if ($level > $depth) {
            return [];
        }
        $cacheKey = "Project:Catalog:$projectId";
        $cache = \Yii::$app->getCache()->get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $cats = Catalog::find()
            ->where('`status`='.Catalog::STATUS_NORMAL)
            ->andWhere('`project_id`=:project_id and `parent_id`=:parent_id', [
                ':project_id' => $projectId,
                ':parent_id' => $parentId
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        $list = [];
        $subPrefix = '';
        for($i = 1; $i<$level; $i++) {
            $subPrefix .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        }
        /** @var Catalog $cat */
        foreach ($cats as $cat) {
            $list[] = [
                'id' => $cat->id,
                'label' => $subPrefix.$cat->name,
            ];
            $subItems = $this->getCatOptions($projectId,$cat->id,$depth,$level+1);
            foreach ($subItems as $subItem) {
                $list[] = $subItem;
            }
        }
        if ($parentId==0 && $level==1) { 
            \Yii::$app->getCache()->set($cacheKey, $list);
        }
        return $list;
    }

    public function getSubCatOptions($projectId, $parentId=0)
    {
        $rows = Catalog::find()
            ->where('`status`='.Catalog::STATUS_NORMAL)
            ->andWhere('`project_id`=:project_id and `parent_id`=:parent_id', [
                ':project_id' => $projectId,
                ':parent_id' => $parentId
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        $list = [];
        /** @var Catalog $row */
        foreach ($rows as $row) {
            $list[] = [
                'id' => $row->id,
                'label' => $row->name,
            ];
        }
        return $list;
    }


    public function getPageHistoryList($pageId)
    {
        return PageHistory::find()
            ->where('`page_id`=:page_id', [
                ':page_id' => intval($pageId)
            ])
            ->orderBy('`id` desc')
            ->asArray()
            ->all();
    }


    public function getDocList($projectId)
    {
        $projectId = intval($projectId);
        $cacheKey = "Project:DocList:$projectId";
        $cache = \Yii::$app->getCache()->get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $list = [];
        $cats = Catalog::find()
            ->where('`status`='.Catalog::STATUS_NORMAL)
            ->andWhere('`project_id`=:project_id and `parent_id`=:parent_id',[
                ':project_id' => $projectId,
                ':parent_id' => 0
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        /** @var Catalog $cat */
        foreach ($cats as $cat) {
            $list[] = [
                'type' => 'catalog',
                'data' => $cat->toArray(),
                'items' => $this->getCatDocList($projectId, $cat->id),
            ];
        }
        unset($cats);

        //项目根目录页面
        $pages = Page::find()
            ->where('`status`='.Page::STATUS_NORMAL)
            ->andWhere('`project_id`=:project_id and `catalog_id`=:catalog_id', [
                ':project_id' => $projectId,
                ':catalog_id' => 0
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        /** @var Page $page */
        foreach ($pages as $page) {
            $list[] = [
                'type' => 'page',
                'data' => $page->toArray(),
            ];
        }
        unset($pages);
 
        \Yii::$app->getCache()->set($cacheKey, $list);
        return $list;

    }

    public function getCatDocList($projectId, $catId)
    {
        $projectId = intval($projectId);
        $catId = intval($catId);
        $list = [];
        $pages = Page::find()
            ->where('`status`='.Page::STATUS_NORMAL)
            ->andWhere('`project_id`=:project_id and `catalog_id`=:catalog_id', [
                ':project_id' => $projectId,
                ':catalog_id' => $catId
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        /** @var Page $page */
        foreach ($pages as $page) {
            $list[] = [
                'type' => 'page',
                'data' => $page->toArray(),
            ];
        }
        unset($pages);

        $cats = Catalog::find()
            ->where('`status`='.Catalog::STATUS_NORMAL)
            ->andWhere('`parent_id`=:paid',[
                ':paid' => $catId
            ])
            ->orderBy('`sort_number` asc,`id` asc')
            ->all();
        /** @var Catalog $cat */
        foreach ($cats as $cat) {
            $list[] = [
                'type' => 'catalog',
                'data' => $cat->toArray(),
                'items' => $this->getCatDocList($projectId, $cat->id),
            ];
        }
        unset($cats);

        return $list;
    }


    public function getMemberIds($projectId)
    {
        $cacheKey = "Project:MemberIds:$projectId";
        $cache = \Yii::$app->getCache()->get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $rows = ProjectMember::find()
            ->select(['user_id'])
            ->where('`project_id` = :project_id', [
                ':project_id' => $projectId
            ])
            ->asArray()
            ->all();
        $userIds = ArrayHelper::getColumn($rows,'user_id');

        //cache clear logics
        \Yii::$app->getCache()->set($cacheKey, $userIds);
        return $userIds;
    }

    public function getMemberList($projectId)
    {
        $cacheKey = "Project:MemberList:$projectId";
        $cache = \Yii::$app->getCache()->get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $projectId = intval($projectId);
        $list = [];
        $members = ProjectMember::find()->select(['user_id','level'])->where('`project_id`=:project_id', [
            ':project_id' => $projectId
        ])->orderBy('`level` desc')->all();
        /** @var ProjectMember $member */
        foreach ($members as $member) {
            $user = User::findOne($member->user_id);
            if (!$user) {
                continue;
            }
            $list[] = [
                'project_id' => $projectId,
                'user_id' => $member->user_id,
                'user_level' => $member->level,
                'username' => $user->username,
                'email' => $user->email,
            ];
        }

        Yii::$app->getCache()->set($cacheKey,$list);
        return $list;
    }

    public function getMemberLevel($projectId, $userId)
    {
        $cacheKey = "Project:MemberLevel:$projectId:$userId";
        $cache = Yii::$app->getCache()->get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $pm = ProjectMember::find()->where('`project_id`=:project_id and `user_id`=:user_id',[
            ':project_id' => $projectId,
            ':user_id' => $userId,
        ])->asArray()->one();

        Yii::$app->getCache()->set($cacheKey, $pm);
        return $pm;
    }

    public function getMemberLevelSetHtml($model)
    {
        if (Yii::$app->getUser()->getId() == $model['user_id']) {
            return '';
        }
        $pm = $this->getMemberLevel($model['project_id'], Yii::$app->getUser()->getId());
        if (!$pm || $pm['level']<ProjectMember::LEVEL_ADMIN || $pm['level']<$model['user_level']) {
            return '';
        }
        $html = '';
        foreach (ProjectMember::$levelList as $k=>$level) {
            if ($k > $pm['level']) {
                continue;
            }
            if ($k == $model['user_level']) {
                $html .= Html::tag(
                    'li',
                    Html::a(Yii::t('app',$level),'javascript:;'),
                    ['class'=>'active']
                );
            } else {
                $html .= Html::tag(
                    'li',
                    Html::a(Yii::t('app',$level),[
                        '/project/set-member-level',
                        'id' => $model['project_id'],
                        'user_id' => $model['user_id'],
                        'level' => $k,
                        ],['data-method'=>'post']),
                    []
                );
            }
        }
        return $html;
    }


}