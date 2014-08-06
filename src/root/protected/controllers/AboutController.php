<?php

class AboutController extends Controller
{
    
    public $layout = 'main';

    public function actionIndex()
    {
        $this->actionOverview();
    }
    
    public function actionOverview()
    {
        $totalSeasons = Yii::app()->db->createCommand('select count(distinct yr) num from loseruser')->queryRow();
        $totalPlayerSeasons = Yii::app()->db->createCommand('select count(*) num from loseruser')->queryRow();
        $perfectSeasons = Yii::app()->db->createCommand('select count(*) num from userbadge where badgeid=9')->queryRow();
        $this->render('overview', array('totalSeasons'=>$totalSeasons['num'], 'totalPlayerSeasons'=>$totalPlayerSeasons['num'], 'perfectSeasons'=>$perfectSeasons['num']));
    }
    
    public function actionMap()
    {
        $this->render('map');
    }

    public function actionHistory()
    {
        $this->render('history');
    }

    public function actionRules()
    {
        $this->render('rules');
    }

    public function actionPayout()
    {
        $this->render('payout');
    }

    public function actionBadges()
    {
        $sql = 'select distinct yr, pot, place, winnings, detail from winners where yr = ' . (getCurrentYear()-1) . ' order by place, pot';
        $trophies = Yii::app()->db->createCommand($sql)->queryAll();
        $sql = 'select distinct u.id, u.username, u.avatar_ext, w1.userid, w1.yr, w1.pot, w1.place, w1.detail from winners w1 inner join user u on w1.userid = u.id where w1.yr = (select min(yr) from winners w2 where w2.pot = w1.pot and w2.place = w1.place)';
        $trophiesUnlocked = Yii::app()->db->createCommand($sql)->queryAll();
        $badges = Badge::model()->with(array(
            'unlockedBy' => array(
                'select' => 'unlockedBy.id, unlockedBy.username, unlockedBy.avatar_ext',
            )
        ))->findAll(array(
            'order' => 't.zindex'
        ));
        $this->render('badges', array('trophies'=>$trophies, 'trophiesUnlocked'=>$trophiesUnlocked, 'badges'=>$badges));
    }

    public function actionPower()
    {
        $this->render('power');
    }

    public function actionBandwagon()
    {
        $this->render('bandwagon');
    }

    public function actionTech()
    {
        $this->render('tech');
    }
    
}