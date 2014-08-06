<?php

class ArchiveController extends Controller
{
    
    public $layout = 'main';

    public function actionIndex()
    {
        $this->actionWinners();
    }
    
    public function actionWinners()
    {
        $wins = Win::model()->with(array(
            'user' => array(
                'joinType' => 'inner join',
                'select'   => 'user.id, user.username, user.power_ranking, user.power_points, user.avatar_ext, user.active',
            )
        ))->findAll();
        $this->render('winners', array('wins'=>$wins));
    }

    public function actionYear()
    {
        $year = min(getCurrentYear()-1, max(param('earliestYear'), (int) getRequestParameter('y', getCurrentYear())));
        
        // for some reason if these scopes aren't applied in exactly the right order, something gets missed.
        // ironically, if you apply them in the same order twice, the second time everything works properly... must be something jacked with Yii
        $boardData = User::model()->withPicks($year, true, true)->withBadges()->withWins()->findAll(array(
            'select' => 't.id, t.username, t.avatar_ext, t.power_points, t.power_ranking',
            'order' => 't.username, t.id, picks.yr, picks.week, wins.place, wins.pot, wins.yr, badge.zindex',
        ));
        
        $bandwagon = Bandwagon::model()->with(array('team', 'chief'))->findAll(array(
            'condition' => "t.yr = $year"
        ));
        
        $talk = Talk::model()->withLikes()->findAll(array(
            'condition' => "t.yr = $year" . (isSuperadmin() ? '' : ' and t.active = 1'),
            'order'     => 't.postedon desc'
        ));
        
        $this->render('year', array('boardData'=>$boardData, 'bandwagon'=>$bandwagon, 'talk'=>$talk, 'year'=>$year));
    }
    
}