<?php

// KDHTODO enable username, password, and email changing
// KDHTODO add support for view options on home page (show/hide mov, icons, etc)
// KDHTODO add support for timezone
// KDHTODO get rid of all the inline styles on these pages
// KDHTODO re-enable lightbox
// KDHTODO make save routines have consistent ways to show completion and errors (errors above the field in a div that takes up space whether or not it's empty, and successes as little save checkmarks to the left of each field that fade away)
    // the checkmark should default to being present, and then should disappear when the user changes the field value or while an AJAX request is pending.  Once successful, it will reappear next to the field.
// KDHTODO make it so on the inline edit fields, you can just hit ENTER to save

?>
<script>
var uploader,
    defaults = {
        email   : '<?php echo addslashes($user->email)?>',
        username: '<?php echo addslashes($user->username)?>'
    };

$(function() {
    var fnDynamicSave,
        fnInputChanged,
        fnUpdateTimezone,
        fnPasswordDefault,
        fnPasswordChange,
        $passwordContainer,
        uploadTimeout = null;

    
    fnInputChanged = function($input, $button, defaultValue, $msgArea) {
        if ($input.val() !== defaultValue) {
            if (!$button.is(':visible')) {
                $button.fadeIn('fast');
            }
        } else {
            if ($button.is(':visible')) {
                $button.fadeOut('fast');
            }
            $msgArea.html('');
        }
    };
    
    
    // function to add controls to inline input edits with save buttons that appear/disappear
    fnDynamicSave = function(options) {
        var $container = $(options.container),
            $input, $button, $msgArea, inputTimeout;
        
        $container
            .append($msgArea = $('<div style="color:red;"/>'))
            .append($input = $('<input style="float:left;"/>')
                .val(defaults[options.defaultKey])
                .bind('change keyup', function(e) {
                    clearTimeout(inputTimeout);
                    if (e.type == 'keyup') {
                        inputTimeout = setTimeout(function() {
                            fnInputChanged($input, $button, defaults[options.defaultKey], $msgArea);
                        }, 200);
                    } else {
                        fnInputChanged($input, $button, defaults[options.defaultKey], $msgArea);
                    }
                })
            )
            .append($('<div style="display:inline-block;float:left;"/>')
                .append($button = $('<button/>')
                    .addClass('button')
                    .html('Save')
                    .click(function(e) {
                        var newValue = $input.val();
                        e.preventDefault();
                        if (!options.validator(newValue)) {
                            $msgArea.html('The specified value is invalid.');
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
                                                    $msgArea.html(response.error);
                                                } else {
                                                    $msgArea.html('');
                                                    defaults[options.defaultKey] = newValue;
                                                    fnInputChanged($input, $button, defaults[options.defaultKey], $msgArea);
                                                }
                                            },
                                error:      function() {
                                                $msgArea.html('An error occurred, please try again.');
                                            },
                                complete:   function() {
                                                $button.prop('disabled', false).html('Save');
                                            },
                                dataType:   'json'
                            });
                        }
                    })
                    .hide()
                )
            );
    };
    
    
    // set up inline updaters
    fnDynamicSave({
        container:  '#username-area',
        defaultKey: 'username',
        ajaxUrl:    '<?php echo $this->createAbsoluteUrl('profile/username')?>',
        validator:  function(val) {
            return val != '';
        }
    });
    fnDynamicSave({
        container:  '#email-area',
        defaultKey: 'email',
        ajaxUrl:    '<?php echo $this->createAbsoluteUrl('profile/email')?>',
        validator:  globals.isEmail
    });


    // timezone updater
    fnUpdateTimezone = function() {
        $.ajax({
            url:        '<?php echo $this->createAbsoluteUrl('profile/timezone')?>',
            data:       {
                            uid:      <?php echo $user->id;?>,
                            timezone: $('#timezone').val(),
                            dst:      $('#use_dst').is(':checked') ? 1 : 0
                        },
            type:       'post',
            cache:      false,
            success:    function(response) {
                            if (response.hasOwnProperty('error') && response.error != '') {
                                $('#timezone-message').html(response.error);
                            } else {
                                $('#timezone-message').html('');
                                $('#timezone-saved').show();
                                setTimeout(function() {
                                    $('#timezone-saved').fadeOut('fast');
                                }, 2000);
                            }
                        },
            error:      function() {
                            $('#timezone-message').html('An error occurred, please try again.');
                        },
            dataType:   'json'
        });
    };
    $('#timezone').on('change', fnUpdateTimezone);
    $('#use_dst').on('click', fnUpdateTimezone);


    // view settings updater
    $('#collapse_history').add('#show_badges').add('#show_logos').add('#show_mov').on('click', function() {
        var $this = $(this);
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
                                $('#viewsetting-message').html(response.error);
                            } else {
                                $('#viewsetting-message').html('');
                                $('#viewsetting-saved').show();
                                setTimeout(function() {
                                    $('#viewsetting-saved').fadeOut('fast');
                                }, 2000);
                            }
                        },
            error:      function() {
                            $('#viewsetting-message').html('An error occurred, please try again.');
                        },
            dataType:   'json'
        });
    });
    
    
    // avatar upload
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
                $('#avatar<?php echo $user->id?>').fadeIn('fast');//.lightBox();
            });
            uploadTimeout = setTimeout(function() {
                $('ul.qq-upload-list li').fadeOut(400, function() {
                    $(this).remove();
                });
            }, 2000);
        },
        onCancel: function(id, filename) {
        }
    });


    // password changer
    $passwordContainer = $('#password-area');
    
    fnPasswordDefault = function(showChanged) {
        var pwChanged = typeof showChanged !== 'undefined' && showChanged === true,
            $changeMsg = $('<div style="color:green;font-weight:bold;">Your password has been changed</div>');
        $passwordContainer
            .empty()
            .append(pwChanged ? $changeMsg : '')
            .append('*****************<br />')
            .append($('<button/>')
                .addClass('button')
                .html('Change')
                .click(function(e) {
                    e.preventDefault();
                    fnPasswordChange();
                })
            );
        if (pwChanged) {
            setTimeout(function() {
                $changeMsg.fadeOut(function() { $changeMsg.remove(); });
            }, 3000);
        }
    };
    
    fnPasswordChange = function() {
        var $oldpw, $newpw1, $newpw2, $submitButton, $cancelButton, $msgArea;
        $passwordContainer
            .empty()
            .append($msgArea = $('<div style="color:red;"/>'))
            .append($('<table/>')
                .append($('<tr/>')
                    .append('<td>Current Password</td>')
                    .append($('<td/>')
                        .append($oldpw = $('<input type="password" />')
                            .keypress(function(e) {
                                if (e.which == 13) {
                                    $submitButton.trigger('click');
                                }
                            })
                        )
                    )
                )
                .append($('<tr/>')
                    .append('<td>New Password</td>')
                    .append($('<td/>')
                        .append($newpw1 = $('<input type="password" />')
                            .keypress(function(e) {
                                if (e.which == 13) {
                                    $submitButton.trigger('click');
                                }
                            })
                        )
                    )
                )
                .append($('<tr/>')
                    .append('<td>Repeat New Password</td>')
                    .append($('<td/>')
                        .append($newpw2 = $('<input type="password" />')
                            .keypress(function(e) {
                                if (e.which == 13) {
                                    $submitButton.trigger('click');
                                }
                            })
                        )
                    )
                )
                .append($('<tr/>')
                    .append($('<td colspan="2"/>')
                        .append($submitButton = $('<button/>')
                            .addClass('button')
                            .html('Save')
                            .click(function(e) {
                                var old = $oldpw.val(), new1 = $newpw1.val(), new2 = $newpw2.val();
                                e.preventDefault();
                                if (old == '') {
                                    $msgArea.html('Please enter your old password.');
                                    $oldpw.focus().select();
                                } else if (new1 == '') {
                                    $msgArea.html('Please enter your new password.');
                                    $newpw1.focus().select();
                                } else if (new2 == '') {
                                    $msgArea.html('Please confirm your new password.');
                                    $newpw2.focus().select();
                                } else if (new1 !== new2) {
                                    $msgArea.html('Your new password does not match the confirmation of your new password, please try again.');
                                    $newpw1.focus().select();
                                } else {
                                    $submitButton.prop('disabled', true).html('Changing...');
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
                                                            $msgArea.html(response.error);
                                                        } else {
                                                            fnPasswordDefault(true);
                                                        }
                                                    },
                                        error:      function() {
                                                        $msgArea.html('An error occurred, please try again.');
                                                    },
                                        complete:   function() {
                                                        $submitButton.prop('disabled', false).html('Save');
                                                        $cancelButton.prop('disabled', false);
                                                    },
                                        dataType:   'json'
                                    });
                                }
                            })
                        )
                        .append(' ')
                        .append($cancelButton = $('<button/>')
                            .addClass('button')
                            .html('Cancel')
                            .click(function(e) {
                                e.preventDefault();
                                fnPasswordDefault();
                            })
                        )
                    )
                )
            );
        
        $oldpw.focus();
    };
    
    fnPasswordDefault();
});
</script>


<table border="0" cellspacing="2" cellpadding="10" class="options">
    <tr>
        <th nowrap="nowrap">Username</th>
        <td id="username-area"></td>
    </tr>
    <tr>
        <th nowrap="nowrap">Email</th>
        <td id="email-area"></td>
    </tr>
    <tr>
        <th nowrap="nowrap">Timezone</th>
        <td>
            <div id="timezone-message" style="color:red;"></div>
            <select id="timezone">
                <?php
                $defaultTimezone = userField('timezone');
                echo createOption(-2, 'Pacific Time', $defaultTimezone);
                echo createOption(-1, 'Mountain Time', $defaultTimezone);
                echo createOption(0, 'Central Time', $defaultTimezone);
                echo createOption(1, 'Eastern Time', $defaultTimezone);
                ?>
            </select>
            <input type="checkbox" id="use_dst"<?php echo userField('use_dst') ? ' checked="checked"' : ''?> /> Enable Automatic Daylight Savings Time Adjustments
            <div id="timezone-saved" style="display:none;">Saved</div>
        </td>
    </tr>
    <tr>
        <th nowrap="nowrap">Password</th>
        <td id="password-area"></td>
    </tr>
    <tr>
        <th nowrap="nowrap">View Settings</th>
        <td>
            <div id="viewsetting-message" style="color:red;"></div>
            <input type="checkbox" id="collapse_history"<?php echo userField('collapse_history') ? ' checked="checked"' : ''?> /> Collapse History<br />
            <input type="checkbox" id="show_badges"<?php echo userField('show_badges') ? ' checked="checked"' : ''?> /> Show User Badges<br />
            <input type="checkbox" id="show_logos"<?php echo userField('show_logos') ? ' checked="checked"' : ''?> /> Show Team Logos<br />
            <input type="checkbox" id="show_mov"<?php echo userField('show_mov') ? ' checked="checked"' : ''?> /> Show Margin of Defeat<br />
            <div id="viewsetting-saved" style="display:none;">Saved</div>
        </td>
    </tr>
    <tr>
        <th nowrap="nowrap">Profile Image</th>
        <td>
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
        </td>
    </tr>
</table>

<?php



