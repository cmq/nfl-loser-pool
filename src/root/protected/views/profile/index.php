<script src="<?php echo baseUrl('/js/lib/fileuploader.js'); ?>"></script>

<?php

// KDHTODO make save routines have consistent ways to show completion and errors (errors above the field in a div that takes up space whether or not it's empty, and successes as little save checkmarks to the left of each field that fade away)
    // the checkmark should default to being present, and then should disappear when the user changes the field value or while an AJAX request is pending.  Once successful, it will reappear next to the field.
// KDHTODO clean up avatar upload section (general look, as well as error block)


?>
<script>
var uploader,
    defaults = {
        email   : '<?php echo addslashes($user->email)?>',
        username: '<?php echo addslashes($user->username)?>'
    };

$(function() {

    $('.help-link').popover({
        placement: 'auto top'
    }).click(function(e) {
        e.preventDefault();
        $('.spawns-popover').not(this).popover('hide');
        // next line is to fix a bug with the popover plugin (@see https://github.com/twbs/bootstrap/issues/10568)
        $('.popover:not(.in)').hide().detach();
        return false;
    });
    
    (function() {
        var fnDynamicSave,
            fnInputChanged;
        
        fnInputChanged = function($input, $button, defaultValue) {
            if ($input.val() !== defaultValue) {
                if (!$button.is(':visible')) {
                    $button.fadeIn('fast');
                }
                return true;
            } else {
                if ($button.is(':visible')) {
                    $button.fadeOut('fast');
                }
            }
            return false;
        };
        
        
        // function to add controls to inline input edits with save buttons that appear/disappear
        fnDynamicSave = function(options) {
            var $container = $('.fieldwrap-'+options.defaultKey),
                $input     = $('input', $container),
                $gutter    = $('.gutter', $container),
                $msgArea   = $('.help-block', $container),
                $button, inputTimeout, fnUpdateMessage, fnCheckInput;
    
            fnUpdateMessage = function(msg) {
                $msgArea.html(msg || '');
                if (typeof msg === 'undefined' || msg === '') {
                    $container.removeClass('has-error');
                } else {
                    $container.addClass('has-error');
                }
            };
            
            fnCheckInput = function() {
                if (!fnInputChanged($input, $button, defaults[options.defaultKey])) {
                    fnUpdateMessage();
                }
            };
    
            $input
                .val(defaults[options.defaultKey])
                .bind('change keyup', function(e) {
                    clearTimeout(inputTimeout);
                    if (e.type == 'keyup') {
                        if (e.which == 13) {
                            fnCheckInput();
                            e.preventDefault();
                            $button.trigger('click');
                        } else {
                            inputTimeout = setTimeout(fnCheckInput, 200);
                        }
                    } else {
                        fnCheckInput();
                    }
                });
    
            $button = $('<button type="button"/>')
                .addClass('btn btn-primary')
                .html('Save')
                .appendTo($gutter)
                .on('click', function(e) {
                    var newValue = $input.val();
                    e.preventDefault();
                    if (!options.validator(newValue)) {
                        fnUpdateMessage('The specified value is invalid.');
                        $input.focus().select();
                    } else {
                        $button.prop('disabled', true).html('Saving...');
                        $.ajax({
                            url:        options.ajaxUrl,
                            data:       {
                                            uid:   <?php echo $user->id;?>,
                                            value: newValue
                                        },
                            type:       'post',
                            cache:      false,
                            success:    function(response) {
                                            if (response.hasOwnProperty('error') && response.error != '') {
                                                fnUpdateMessage(response.error);
                                            } else {
                                                fnUpdateMessage();
                                                defaults[options.defaultKey] = newValue;
                                                fnCheckInput();
                                            }
                                        },
                            error:      function() {
                                            fnUpdateMessage('An error occurred, please try again.');
                                        },
                            complete:   function() {
                                            $button.prop('disabled', false).html('Save');
                                        },
                            dataType:   'json'
                        });
                    }
                })
                .hide();
        };
        
        
        // set up inline updaters
        fnDynamicSave({
            defaultKey: 'username',
            ajaxUrl:    '<?php echo $this->createAbsoluteUrl('profile/username')?>',
            validator:  function(val) {
                return val != '';
            }
        });
        fnDynamicSave({
            defaultKey: 'email',
            ajaxUrl:    '<?php echo $this->createAbsoluteUrl('profile/email')?>',
            validator:  globals.isEmail
        });
    })();


    // timezone updater
    (function() {
        var fnUpdateTimezone;
        fnUpdateTimezone = function() {
            var $container = $('.fieldwrap-timezone'),
                $timezone  = $('#timezone'),
                $dst       = $('#use_dst'),
                $gutter    = $('.gutter', $container),
                $msgArea   = $('.help-block', $container),
                fnUpdateMessage;
            
            fnUpdateMessage = function(msg) {
                $msgArea.html(msg || '');
                if (typeof msg === 'undefined' || msg === '') {
                    $container.removeClass('has-error');
                } else {
                    $container.addClass('has-error');
                }
            };
    
            // disable fields
            $timezone.prop('disabled', true);
            $dst.prop('disabled', true);
    
            // reset state
            $container.removeClass('has-success has-error');
            $gutter.html('<p>Saving...</p>');
            fnUpdateMessage();
    
            // make AJAX call
            $.ajax({
                url:        '<?php echo $this->createAbsoluteUrl('profile/timezone')?>',
                data:       {
                                uid:      <?php echo $user->id;?>,
                                timezone: $timezone.val(),
                                dst:      $dst.is(':checked') ? 1 : 0
                            },
                type:       'post',
                cache:      false,
                success:    function(response) {
                                if (response.hasOwnProperty('error') && response.error != '') {
                                    fnUpdateMessage(response.error);
                                } else {
                                    fnUpdateMessage();
                                    $container.addClass('has-success');
                                    $gutter.html('<p class="text-success">Saved.</p>');
                                    setTimeout(function() {
                                        $container.removeClass('has-success');
                                        $gutter.html('');
                                    }, 3000);
                                }
                            },
                error:      function() {
                                fnUpdateMessage('An error occurred, please try again.');
                            },
                complete:   function() {
                                $timezone.prop('disabled', false);
                                $dst.prop('disabled', false);
                            },
                dataType:   'json'
            });
        };
        $('#timezone').on('change', fnUpdateTimezone);
        $('#use_dst').on('click', fnUpdateTimezone);
    })();


    // view settings updater
    (function() {
        $('#collapse_history').add('#show_badges').add('#show_logos').add('#show_mov').on('click', function() {
            var $this = $(this),
                $container = $this.closest('.form-group'),
                $msgArea = $container.find('.help-block'),
                fnMessage;

            fnMessage = function(msg, success) {
                $container.removeClass('has-error has-success');
                success = success || false;
                $msgArea.html(msg);
                if (typeof msg !== 'undefined' && msg != '') {
                    $container.addClass(success ? 'has-success' : 'has-error');
                }
            };
            
            $.ajax({
                url:        '<?php echo $this->createAbsoluteUrl('profile/changeViewSetting')?>',
                data:       {
                                uid:     <?php echo $user->id;?>,
                                setting: $this.attr('id'),
                                value:   $this.is(':checked') ? 1 : 0
                            },
                type:       'post',
                cache:      false,
                success:    function(response) {
                                if (response.hasOwnProperty('error') && response.error != '') {
                                    fnMessage(response.error);
                                } else {
                                    fnMessage('Saved', true);
                                    setTimeout(function() {
                                        fnMessage('');
                                    }, 3000);
                                }
                            },
                error:      function() {
                                fnMessage('An error occurred, please try again.');
                            },
                dataType:   'json'
            });
        });
    })();
    
    
    // avatar upload
    (function() {
        var uploadTimeout;
        uploader = new qq.FileUploader({
            element: $('#file-uploader').get(0),
            action: '<?php echo $this->createAbsoluteUrl('profile/avatar')?>',
            allowedExtensions: ['<?php echo implode("', '", param('avatarExtensions'))?>'],
            sizeLimit: <?php echo param('avatarSizeLimit')?>,
            uploadButtonText: 'Upload New<br />Profile Image',
            params: {
                uid: <?php echo $user->id;?>
            },
            onSubmit: function(id, filename) {
                clearTimeout(uploadTimeout);
            },
            onProgress: function(id, filename, loaded, total) {
            },
            onComplete: function(id, filename, responseJSON) {
                var $avatar = $('#avatar<?php echo $user->id?>');
                $avatar.fadeOut('fast', function() {
                    $avatar.replaceWith($(globals.htmlDecode(responseJSON.thumbnail)).hide());
                    $('#avatar<?php echo $user->id?> img').attr('src', responseJSON.thumbnailurl + '?x=' + new Date().getTime());
                    $('#avatar<?php echo $user->id?>').fadeIn('fast');
                });
                uploadTimeout = setTimeout(function() {
                    $('ul.qq-upload-list li').fadeOut(400, function() {
                        $(this).remove();
                    });
                }, 3000);
            },
            onCancel: function(id, filename) {
            }
        });
    })();


    // password changer
    (function() {
        var $container      = $('.fieldwrap-password'),
            $formGroups     = $('.form-group', $container),
            $msgAreas       = $('.help-block', $container),
            $defaultDisplay = $('#change-password-static', $container),
            $changeDisplay  = $('#change-password-form', $container),
            $changeButton   = $('.btn', $defaultDisplay),
            $saveButton     = $('.btn.save', $changeDisplay),
            $cancelButton   = $('.btn.cancel', $changeDisplay),
            $inputs         = $('input', $container),
            $oldpw          = $('#oldpw'),
            $newpw1         = $('#newpw1'),
            $newpw2         = $('#newpw2'),
            fnClearMessage, fnSetMessage;

        fnClearMessage = function($input) {
            if (typeof $input === 'undefined') {
                $msgAreas.html('');
                $formGroups.removeClass('has-error');
            } else {
                $input.closest('.form-group').removeClass('has-error').find('.help-block').html('');
            }
        };

        fnSetMessage = function($input, msg) {
            $input.closest('.form-group').addClass('has-error').find('.help-block').html(msg);
            $input.focus().select();
        };
        
        $inputs.on('keypress', function(e) {
            if (e.which == 13) {
                $saveButton.trigger('click');
            }
        });
        $changeButton.on('click', function(e) {
            e.preventDefault();
            $defaultDisplay.hide();
            $changeDisplay.show();
            $inputs.val('');
        });
        $cancelButton.on('click', function(e) {
            e.preventDefault();
            $defaultDisplay.show();
            $changeDisplay.hide();
        });
        $saveButton.on('click', function(e) {
            var old = $oldpw.val(), new1 = $newpw1.val(), new2 = $newpw2.val(), error = false;
            e.preventDefault();
            if (old == '') {
                error = true;
                fnSetMessage($oldpw, 'Please enter your old password.');
            } else {
                fnClearMessage($oldpw);
            }
            if (new1 == '') {
                error = true;
                fnSetMessage($newpw1, 'Please enter your new password.');
            } else {
                fnClearMessage($newpw1);
            }
            if (new2 == '') {
                error = true;
                fnSetMessage($newpw2, 'Please confirm your new password.');
            } else {
                if (new1 != new2) {
                    error = true;
                    fnSetMessage($newpw2, 'The confirmation password does not match.');
                } else {
                    fnClearMessage($newpw2);
                }
            }
            if (!error) {
                $saveButton.prop('disabled', true).html('Changing...');
                $cancelButton.prop('disabled', true);
                $.ajax({
                    url:        '<?php echo $this->createAbsoluteUrl('profile/changepw')?>',
                    data:       {
                                    uid:  <?php echo $user->id;?>,
                                    old:  old,
                                    new1: new1,
                                    new2: new2
                                },
                    type:       'post',
                    cache:      false,
                    success:    function(response) {
                                    if (response.hasOwnProperty('error') && response.error != '') {
                                        fnSetMessage($oldpw, response.error);
                                    } else {
                                        fnClearMessage();
                                        $cancelButton.prop('disabled', false).trigger('click');
                                    }
                                },
                    error:      function() {
                                    fnSetMessage($oldpw, 'An error occurred, please try again.');
                                },
                    complete:   function() {
                                    $saveButton.prop('disabled', false).html('Save');
                                    $cancelButton.prop('disabled', false);
                                },
                    dataType:   'json'
                });
            }
        });
    })();
});
</script>


<div class="container">
    <div class="row">
        <div class="col-sm-offset-6"><?php echo CHtml::link('View Profile', array('stats/profile', 'id'=>$user->id)) . '<br />'?></div>
    </div>
    <form class="form-horizontal" role="form" style="margin-top:10px;">
        <div class="form-group fieldwrap-username">
            <label class="control-label col-sm-2" for="username">Username</label>
            <div class="col-sm-5">
                <input type="text" id="username" class="form-control" />
                <span class="help-block"></span>
            </div>
            <div class="col-sm-5 gutter"></div>
        </div>
        <div class="form-group fieldwrap-email">
            <label class="control-label col-sm-2" for="email">Email</label>
            <div class="col-sm-5">
                <input type="text" id="email" class="form-control" />
                <span class="help-block"></span>
            </div>
            <div class="col-sm-5 gutter"></div>
        </div>
        <div class="form-group fieldwrap-timezone">
            <label class="control-label col-sm-2" for="email">Timezone</label>
            <div class="col-sm-5">
                <select id="timezone" class="form-control">
                    <?php
                    $defaultTimezone = userField('timezone');
                    echo createOption(-2, 'Pacific Time', $defaultTimezone);
                    echo createOption(-1, 'Mountain Time', $defaultTimezone);
                    echo createOption(0, 'Central Time', $defaultTimezone);
                    echo createOption(1, 'Eastern Time', $defaultTimezone);
                    ?>
                </select>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="use_dst"<?php echo userField('use_dst') ? ' checked="checked"' : ''?> /> Enable Automatic Daylight Savings Time Adjustments
                    </label>
                </div>
                <span class="help-block"></span>
            </div>
            <div class="col-sm-5 gutter"></div>
        </div>
        <div class="form-group fieldwrap-password">
            <label class="control-label col-sm-2" for="username">Password</label>
            <div class="col-sm-5">
                <div id="change-password-static">   <!-- KDHTODO add a top padding of about 9px for this -->
                    *****************
                    <button type="button" class="btn btn-xs btn-default">Change</button>
                </div>
                <div id="change-password-form" style="display:none;">
                    <!-- KDHTODO make sure these placeholders work for all devices, otherwise we might need to show some form labels for certain devices -->
                    <div class="form-group">
                        <div class="col-sm-6">
                            <input type="password" id="oldpw" class="form-control" placeholder="Old Password" />
                        </div>
                        <span class="help-block"></span>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <input type="password" id="newpw1" class="form-control" placeholder="New Password" />
                        </div>
                        <span class="help-block"></span>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <input type="password" id="newpw2" class="form-control" placeholder="Confirm New Password" />
                        </div>
                        <span class="help-block"></span>
                    </div>
                    <button type="button" class="btn btn-primary save">Save</button>
                    <button type="button" class="btn btn-default cancel">Cancel</button>
                    <span class="help-block"></span>
                </div>
            </div>
            <div class="col-sm-5 gutter"></div>
        </div>
        <div class="form-group fieldwrap-view">
            <label class="control-label col-sm-2">View Settings</label>
            <div class="col-sm-5">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="collapse_history"<?php echo userField('collapse_history') ? ' checked="checked"' : ''?> /> Collapse History
                    </label>
                    (<a class="help-link spawns-popover" title="Collapse History" data-content="If this option is enabled, the pick board on the home screen will collapse all weeks prior to the current week into a single column showing each user's record for those collapsed week.  For example, if the current week were Week 15, there would be one column for &quot;Week 1 - Week 14&quot;, and then another column for &quot;Week 15&quot; (the current week).  This prevents you from seeing each pick for each user for each week, but can greatly reduce the width of the board making it easier to see everything.">?</a>)
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="show_badges"<?php echo userField('show_badges') ? ' checked="checked"' : ''?> /> Show User Badges and Trophies
                    </label>
                    (<a class="help-link spawns-popover" title="Show User Badges and Trophies" data-content="If this option is enabled, the badges and trophies earned by each user will be displayed next to their name.">?</a>)
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="show_logos"<?php echo userField('show_logos') ? ' checked="checked"' : ''?> /> Show Team Logos
                    </label>
                    (<a class="help-link spawns-popover" title="Show Team Logos" data-content="If this option is enabled, a small logo of each team will be shown on the pick board.  If this is disabled, the logo will be replaced with the team's name or abbreviation.">?</a>)
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="show_mov"<?php echo userField('show_mov') ? ' checked="checked"' : ''?> /> Show Margin of Defeat
                    </label>
                    (<a class="help-link spawns-popover" title="Show Margin of Defeat" data-content="If this option is enabled, each finished pick will show the Margin of Defeat for that game.">?</a>)
                </div>
                <span class="help-block"></span>
                <div id="viewsetting-saved" style="display:none;">Saved</div>   <!-- KDHTODO use help-block instead -->
            </div>
        </div>
        <div class="form-group fieldwrap-avatar">
            <label class="control-label col-sm-2">Profile Image</label>
            <div class="col-sm-5">
                <?php
                echo getUserAvatar($user->id, $user->avatar_ext);
                ?>
                <br />
                This is your profile image that will appear on the home page.  Note:<br />
                <ul>
                    <li>File size limit of 1MB.</li>
                    <li>Only images of type .jpg, .gif, or .png are allowed.</li>
                    <li>The images will be automatically resized for you.</li>
                </ul>
                Click the button below to select an image, or drag an image over the button to upload.<br />(Unless you have IE in which case you suck and don't deserve to have such convenient features.)<br />
                <br />
                <div id="file-uploader"></div>
                <span class="help-block"></span>
            </div>
            <div class="col-sm-5 gutter"></div>
        </div>
    </form>
</div>

<?php



