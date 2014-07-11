<?php
// KDHTODO add searching?
// KDHTODO allow hiding of inactive players

?>

<script>
var users = <?php echo CJSON::encode($users);?>,
    order = 'power',
    filters = {
        name: '',
        active: -1
    };
    

function aggregate() {
    var i, j, money;
    for (i=0; i<users.length; i++) {
        money = 0;
        for (j=0; j<users[i].wins.length; j++) {
             money += users[i].wins[j].winnings;
        }
        users[i].money = money;
        users[i].flare = users[i].wins.length + users[i].userBadges.length;
        users[i].active = !!(parseInt(users[i].active, 10));
    }
}

function sort(order) {
    var i, j, val1, val2, swap;
    for (i=0; i<users.length-1; i++) {
        for (j=i+1; j<users.length; j++) {
            switch (order) {
                case 'username':
                    val1 = users[i].username.toLowerCase();
                    val2 = users[j].username.toLowerCase();
                    break;
                case 'money':
                    val1 = users[j].money;
                    val2 = users[i].money;
                    break;
                case 'flare':
                    val1 = users[j].flare;
                    val2 = users[i].flare;
                    break;
                default:
                    val1 = parseInt(users[i].power_ranking, 10);
                    val2 = parseInt(users[j].power_ranking, 10);
                    break;
            }
            if (val1 == val2) {
                // fall back to power ranking
                val1 = parseInt(users[i].power_ranking, 10);
                val2 = parseInt(users[j].power_ranking, 10);
            }
            if (val1 > val2) {
                swap = users[i];
                users[i] = users[j];
                users[j] = swap;
            }
        }
    }
    draw();
}

function draw() {
    var i, $tbody = $('<tbody/>'),
        $table = $('<table class="table table-striped table-nonfluid"/>')
            .append($('<thead/>')
                .append($('<tr class="sortable" />')
                    .append($('<th class="text-right">Power Ranking</th>')
                        .on('click', function(e) {
                            e.preventDefault();
                            sort('power');
                        })
                    )
                    .append($('<th colspan="2" class="text-center">Username</th>')
                        .on('click', function(e) {
                            e.preventDefault();
                            sort('username');
                        })
                    )
                    .append($('<th class="text-right">Money Won</th>')
                        .on('click', function(e) {
                            e.preventDefault();
                            sort('money');
                        })
                    )
                    .append($('<th class="text-right">Badges &amp; Trophies</th>')
                        .on('click', function(e) {
                            e.preventDefault();
                            sort('flare');
                        })
                    )
                )
            )
            .append($tbody);
    for (i=0; i<users.length; i++) {
        $tbody.append($('<tr/>')
            .attr('style', users[i].active ? 'font-weight:bold;' : '')
            .append('<td class="text-right">#' + users[i].power_ranking + '</td>')
            .append('<td class="text-center"><img class="avatar" src="' + globals.getUserAvatar(users[i]) + '" /></td>')
            .append('<td class="text-left"><a href="' + CONF.url.profile(users[i].id) + '">' + users[i].username + '</a></td>')
            .append('<td class="text-right">' + globals.dollarFormat(users[i].money) + '</td>')
            .append('<td class="text-right">' + users[i].flare + '</td>')
        );
    }
    $('#profile-list').html($table);
    globals.lightboxAvatars();  // re-activate the newly-drawn avatars as lightboxes
}

$(function() {
    aggregate();
    sort();
});
</script>

<div class="container" id="profile-list"></div>

<?php
if (count($users)) {
    foreach ($users as $user) {
//        echo getProfileLink($user) . '<br />';
    }
} else {
    echo 'No users found.';
}