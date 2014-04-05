<?php

// KDHTODO enable username, password, and email changing
// KDHTODO add support for view options on home page (show/hide mov, icons, etc)
// KDHTODO add support for timezone
// KDHTODO add support for address (which can be an address or paypal address or just a description of how to get paid if they win)



?>
<script>
var uploader,
    defaultEmail = '<?php echo addslashes($user->email)?>',
    defaultUsername = '<?php echo addslashes($user->username)?>';

function inputChanged($input, $button, defaultValue) {
    if ($input.val() !== defaultValue) {
        if (!$button.is(':visible')) {
            $button.fadeIn('fast');
        }
        return true;
    } else {
        if ($button.is(':visible')) {
            $button.fadeOut('fast');
        }
        return false;
    }
}


// avatar upload
$(function() {
    var uploadTimeout = null;
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
});

// username changer
$(function() {
    var $container = $('#username-area'),
        $input,
        $button,
        $msgArea,
        inputTimeout;
    
    function changeVal() {
        if (!inputChanged($input, $button, defaultUsername)) {
            $msgArea.html('');
        }
    }
    
    $container
        .append($msgArea = $('<div style="color:red;"/>'))
        .append($input = $('<input style="float:left;"/>')
            .val(defaultUsername)
            .bind('change keyup', function(e) {
                clearTimeout(inputTimeout);
                if (e.type == 'keyup') {
                    inputTimeout = setTimeout(changeVal, 200);
                } else {
                    changeVal();
                }
            })
        )
        .append($('<div style="display:inline-block;float:left;"/>')
            .append($button = $('<button/>')
                .addClass('button')
                .html('Save')
                .click(function(e) {
                    var newUsername = $input.val();
                    e.preventDefault();
                    if (newUsername == '') {
                        $msgArea.html('You must specify a username.');
                        $input.focus().select();
                    } else {
                        $button.prop('disabled', true).html('Saving...');
                        $.ajax({
                            url:        URL.AJAX,
                            data:       {
                                            method: 'changeusername',
                                            username: newUsername
                                        },
                            type:       'post',
                            cache:      false,
                            success:    function(response) {
                                            if (response.hasOwnProperty('errormsg') && response.errormsg != '') {
                                                $msgArea.html(response.errormsg);
                                            } else {
                                                $msgArea.html('');
                                                defaultUsername = newUsername;
                                                changeVal();
                                            }
                                        },
                            error:      function() {
                                            $msgArea.html('An error occurred, please try again.');
                                        },
                            complete:   function() {
                                            $button.prop('disabled', false).html('Save');
                                        }
                        });
                    }
                })
                .hide()
            )
        );
});

// email changer
$(function() {
    var $container = $('#email-area'),
        $input,
        $button,
        $msgArea,
        inputTimeout;
    
    function changeVal() {
        if (!inputChanged($input, $button, defaultEmail)) {
            $msgArea.html('');
        }
    }
    
    $container
        .append($msgArea = $('<div style="color:red;"/>'))
        .append($input = $('<input style="float:left;"/>')
            .val(defaultEmail)
            .bind('change keyup', function(e) {
                clearTimeout(inputTimeout);
                if (e.type == 'keyup') {
                    inputTimeout = setTimeout(changeVal, 200);
                } else {
                    changeVal();
                }
            })
        )
        .append($('<div style="display:inline-block;float:left;"/>')
            .append($button = $('<button/>')
                .addClass('button')
                .html('Save')
                .click(function(e) {
                    var newEmail = $input.val();
                    e.preventDefault();
                    if (!isEmail(newEmail)) {
                        $msgArea.html('Your email address does not appear to be valid.');
                        $input.focus().select();
                    } else {
                        $button.prop('disabled', true).html('Saving...');
                        $.ajax({
                            url:        URL.AJAX,
                            data:       {
                                            method: 'changeemail',
                                            email: newEmail
                                        },
                            type:       'post',
                            cache:      false,
                            success:    function(response) {
                                            if (response.hasOwnProperty('errormsg') && response.errormsg != '') {
                                                $msgArea.html(response.errormsg);
                                            } else {
                                                $msgArea.html('');
                                                defaultEmail = newEmail;
                                                changeVal();
                                            }
                                        },
                            error:      function() {
                                            $msgArea.html('An error occurred, please try again.');
                                        },
                            complete:   function() {
                                            $button.prop('disabled', false).html('Save');
                                        }
                        });
                    }
                })
                .hide()
            )
        );
});

// password changer
$(function() {
    var $container = $('#password-area');
    
    function passwordDefault(showChanged) {
        var pwChanged = typeof showChanged !== 'undefined' && showChanged === true,
            $changeMsg = $('<div style="color:green;font-weight:bold;">Your password has been changed</div>');
        $container
            .empty()
            .append(pwChanged ? $changeMsg : '')
            .append('*****************<br />')
            .append($('<button/>')
                .addClass('button')
                .html('Change')
                .click(function(e) {
                    e.preventDefault();
                    passwordChange();
                })
            );
        if (pwChanged) {
            setTimeout(function() {
                $changeMsg.fadeOut(function() { $changeMsg.remove(); });
            }, 3000);
        }
    }
    
    function passwordChange() {
        var $oldpw, $newpw1, $newpw2, $submitButton, $cancelButton, $msgArea;
        $container
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
                                        url:        URL.AJAX,
                                        data:       {
                                                        method: 'changepw',
                                                        old: old,
                                                        new1: new1,
                                                        new2: new2
                                                    },
                                        type:       'post',
                                        cache:      false,
                                        success:    function(response) {
                                                        if (response.hasOwnProperty('errormsg') && response.errormsg != '') {
                                                            $msgArea.html(response.errormsg);
                                                        } else {
                                                            passwordDefault(true);
                                                        }
                                                    },
                                        error:      function() {
                                                        $msgArea.html('An error occurred, please try again.');
                                                    },
                                        complete:   function() {
                                                        $submitButton.prop('disabled', false).html('Save');
                                                        $cancelButton.prop('disabled', false);
                                                    }
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
                                passwordDefault();
                            })
                        )
                    )
                )
            );
        
        $oldpw.focus();
    }
    
    passwordDefault();
});
</script>


<div style="max-width:800px;">
    <h1 class="abouth1">Profile/Options</h1>
    <p>
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
            <th nowrap="nowrap">Password</th>
            <td id="password-area"></td>
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
    </p>
    <br /><br /><br /><br />
</div>

<?php



