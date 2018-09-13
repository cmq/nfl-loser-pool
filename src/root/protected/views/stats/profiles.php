<style>
button:disabled {
    color: #c0c0c0;
}
</style>
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
        if (!users[i].hasOwnProperty('firstname')) {
            users[i].firstname = '';
        }
        if (!users[i].hasOwnProperty('lastname')) {
            users[i].lastname = '';
        }
        if (!users[i].hasOwnProperty('active')) {
            users[i].active = 0;
        }
        if (users[i].hasOwnProperty('userYears') && users[i].userYears.length > 0) {
            users[i].paid = parseInt(users[i].userYears[0].paid, 10);
        } else {
            users[i].paid = 0;
        }
        users[i].realname = users[i].firstname + ' ' + users[i].lastname;
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
                case 'realname':
                    val1 = users[i].realname.toLowerCase();
                    val2 = users[j].realname.toLowerCase();
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
        matches = matches && (filterName == '' || users[i].username.toLowerCase().indexOf(filterName) >= 0 || users[i].realname.toLowerCase().indexOf(filterName) >= 0);
        matches = matches && (showActive || users[i].active);
        users[i].shown = matches;
    }
}

function draw() {
    var i, $tbody = $('<tbody/>'),
        $table = $('<table class="table table-striped table-nonfluid"/>')
            .append($('<thead/>')
                .append($('<tr class="sortable" />')
                    .append($('<th class="text-right">Power Rank</th>')
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
                    <?php if (isSuperadmin()) { ?>
                    .append($('<th class="text-left">Real Name</th>')
                        .on('click', function(e) {
                            e.preventDefault();
                            sort('realname');
                        })
                    )
                    <?php } ?>
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
                    <?php if (isSuperadmin()) { ?>
                    .append('<th class="text-center">Admin</th>')
                    <?php } ?>
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
                <?php if (isSuperadmin()) { ?>
                .append('<td class="text-left">' + stylizeName(users[i].realname) + '</td>')
                <?php } ?>
                .append('<td class="text-right">' + globals.dollarFormat(users[i].money) + '</td>')
                .append('<td class="text-right">' + users[i].flare + '</td>')
                .append('<td class="text-right">' + users[i].power_points + '</td>')
                <?php if (isSuperadmin()) { ?>
                .append($('<td class="text-left" style="font-weight:normal;">')
                    .append($('<button title="Mark User Paid">$</button>')
                        .on('click', userPaid.bind(this, users[i]))
                        .prop('disabled', users[i].active == 0 || users[i].paid == 1)
                    )
                    .append(' ')
                    .append($('<button title="Activate User for This Season">*</button>')
                        .on('click', userActive.bind(this, users[i]))
                        .prop('disabled', users[i].active == 1)
                    )
                    .append(' ')
                    .append($('<button title="Reset User\'s Password">PW</button>')
                        .on('click', userPassword.bind(this, users[i]))
                    )
                )
                <?php } ?>
            );
        }
    }
    $('#profile-list').html($table);
    globals.lightboxAvatars();  // re-activate the newly-drawn avatars as lightboxes
}

<?php if (isSuperadmin()) { ?>
function userPaid(user) {
    var paidnote = $.trim(prompt('Please enter a note about the payment.'));
    if (paidnote != null & paidnote != '') {
        $.ajax({
            url:        '<?php echo Yii::app()->createAbsoluteUrl('admin/markPaid')?>',
            data:       {
                            id: user.id,
                            paidnote: paidnote
                        },
            type:       'post',
            cache:      false,
            success:    function(response) {
                            if (response.hasOwnProperty('error') && response.error != '') {
                                alert(response.error);
                            } else {
                                var i;
                                for (i=0; i<users.length; i++) {
                                    if (users[i].id == user.id) {
                                        users[i].paid = 1;
                                        break;
                                    }
                                }
                                filter();
                                draw();
                            }
                        },
            error:      function() {
                            alert('An unknown error occurred.');
                        },
            dataType:   'json'
        });
    }
}

function userActive(user) {
    if (confirm('Are you sure you want to activate ' + user.username + ' for <?php echo getCurrentYear() ?>?')) {
        $.ajax({
            url:        '<?php echo Yii::app()->createAbsoluteUrl('admin/activateUser')?>',
            data:       {
                            id: user.id
                        },
            type:       'post',
            cache:      false,
            success:    function(response) {
                            if (response.hasOwnProperty('error') && response.error != '') {
                                alert(response.error);
                            } else {
                                var i;
                                for (i=0; i<users.length; i++) {
                                    if (users[i].id == user.id) {
                                        users[i].active = 1;
                                        break;
                                    }
                                }
                                filter();
                                draw();
                            }
                        },
            error:      function() {
                            alert('An unknown error occurred.');
                        },
            dataType:   'json'
        });
    }
}

function userPassword(user) {
    var newpw = $.trim(prompt('Please enter a new password.'));
    if (newpw != null & newpw != '') {
        $.ajax({
            url:        '<?php echo Yii::app()->createAbsoluteUrl('admin/resetPassword')?>',
            data:       {
                            id: user.id,
                            newpw: newpw
                        },
            type:       'post',
            cache:      false,
            success:    function(response) {
                            if (response.hasOwnProperty('error') && response.error != '') {
                                alert(response.error);
                            }
                        },
            error:      function() {
                            alert('An unknown error occurred.');
                        },
            dataType:   'json'
        });
    }
}
<?php } ?>

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
