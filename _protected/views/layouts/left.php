<aside class="main-sidebar">
    <section class="sidebar">
        <?php if(Yii::$app->getUser()->getIsGuest()): ?>
            <?= dmstr\widgets\Menu::widget(
                [
                    'options' => ['class' => 'sidebar-menu'],
                    'items' => [
                        ['label' => Yii::t('app','Menu'), 'options' => ['class' => 'header']],
                        ['label' => Yii::t('app','Home'), 'icon' => 'fa fa-home', 'url' => ['/site/index']],
                        ['label' => Yii::t('app','News'), 'icon' => 'fa fa-newspaper-o', 'url' => ['/article/index']],
                        ['label' => Yii::t('app','About'), 'icon' => 'fa fa-info', 'url' => ['/site/about']],
                        ['label' => Yii::t('app','Contact'), 'icon' => 'fa fa-bug', 'url' => ['/site/contact']],
                        ['label' => Yii::t('app','Login'), 'icon' => 'fa fa-user', 'url' => ['/site/login']],
                    ],
                ]
            ) ?>
        <?php else: ?>
        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="pull-left image">
                <img src="<?php echo \yii\helpers\Url::to('@web/static/images/jlogo.png'); ?>" class="img-circle" alt="User Image"/>
            </div>
            <div class="pull-left info">
                <p>
                    <?php echo Yii::$app->getUser()->getIdentity()->username; ?>
                </p>

                <a href="javascript:;"><i class="fa fa-circle text-success"></i> <?php echo Yii::t('app','Online');?></a>
            </div>
        </div>

        <?= dmstr\widgets\Menu::widget(
            [
                'options' => ['class' => 'sidebar-menu'],
                'items' => isset($this->params['left-menu']) && $this->params['left-menu'] ? $this->params['left-menu'] : [
                    ['label' => Yii::t('app','Menu'), 'options' => ['class' => 'header']],
                    ['label' => Yii::t('app','Home'), 'icon' => 'fa fa-home', 'url' => ['/site/index']],
                    ['label' => Yii::t('app','Project'), 'icon' => 'fa fa-book', 'url' => ['/project/index']],
                    ['label' => Yii::t('app','News'), 'icon' => 'fa fa-newspaper-o', 'url' => ['/article/index']],
                    ['label' => Yii::t('app','About'), 'icon' => 'fa fa-info', 'url' => ['/site/about']],
                    ['label' => Yii::t('app','Contact'), 'icon' => 'fa fa-bug', 'url' => ['/site/contact']],
                ],
            ]
        ) ?>
        <?php endif; ?>
    </section>
</aside>