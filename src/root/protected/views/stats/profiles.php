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
        users[i].shown = true;
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

function stylizeName(username) {
    var filterName = $('#name-filter').val().toLowerCase(),
        idx = username.toLowerCase().indexOf(filterName.toLowerCase());
    if (idx >= 0) {
        return username.substr(0, idx) + '<span class="highlight-search">' + username.substr(idx, filterName.length) + '</span>' + username.substr(idx + filterName.length);
    }
    return username;
}

function filter() {
    var i,
        filterName = $('#name-filter').val().toLowerCase(),
        showActive = $('#active-filter').is(':checked'),
        matches;
    for (i=0; i<users.length; i++) {
        matches = true;
        matches = matches && (filterName == '' || users[i].username.indexOf(filterName) >= 0);
        matches = matches && (showActive || users[i].active);
        users[i].shown = matches;
    }
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
                    .append($('<th class="text-right">Power Points</th>')
                        .on('click', function(e) {
                            e.preventDefault();
                            sort('power');
                        })
                    )
                )
            )
            .append($tbody);
    for (i=0; i<users.length; i++) {
        if (users[i].shown) {
            $tbody.append($('<tr/>')
                .attr('style', users[i].active ? 'font-weight:bold;' : '')
                .append('<td class="text-right">#' + users[i].power_ranking + '</td>')
                .append('<td class="text-center"><img class="avatar" src="' + globals.getUserAvatar(users[i]) + '" /></td>')
                .append('<td class="text-left"><a href="' + CONF.url.profile(users[i].id) + '">' + stylizeName(users[i].username) + '</a></td>')
                .append('<td class="text-right">' + globals.dollarFormat(users[i].money) + '</td>')
                .append('<td class="text-right">' + users[i].flare + '</td>')
                .append('<td class="text-right">' + users[i].power_points + '</td>')
            );
        }
    }
    $('#profile-list').html($table);
    globals.lightboxAvatars();  // re-activate the newly-drawn avatars as lightboxes
}

$(function() {
    aggregate();
    sort();
    $('#name-filter').on('keyup change', function() {
        filter();
        draw();
    });
    $('#active-filter').on('change', function() {
        filter();
        draw();
    });
});
</script>

<div class="container">
    <h2>Profiles List</h2>
    <div class="row">
        <div class="col-xs-12 col-sm-6 text-right">Filter Names:</div>
        <div class="col-xs-12 col-sm-6"><input type="text" id="name-filter" value="" /></div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-6 text-right">Show Inactive Users:</div>
        <div class="col-xs-12 col-sm-6"><input type="checkbox" id="active-filter" checked="checked" /></div>
    </div> 
    <div id="profile-list"></div>
</div>

<?php
if (count($users)) {
    foreach ($users as $user) {
//        echo getProfileLink($user) . '<br />';
    }
} else {
    echo 'No users found.';
}