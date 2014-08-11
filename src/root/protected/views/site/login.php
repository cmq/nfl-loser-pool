<script>
var loggingIn = false;

function logIn() {
    var err = false, data = {}, fnClearError, fnLoginError, iClearError;

    fnClearError = function() {
        $('#login-error').fadeOut('fast', function() {
            $('#login-error').html('&nbsp;').show().css('visibility', 'hidden');
        });
    };
    
    fnLoginError = function(s) {
        err = true;
        $('#login-error').html(s).css('visibility', 'visible');
        $('input[name=username]').focus().select();
        iClearError = setTimeout(fnClearError, 3000);
    };
    
    if (!loggingIn) {
        loggingIn = true;
        clearTimeout(iClearError);
        fnClearError();
        $('#login-button').html('Logging In...').prop('disabled', true);
        data['username'] = $('input[name=username]').val();
        data['password'] = $('input[name=password]').val();
        if ($('input[name=rememberMe]:checked').size() > 0) {
            data['rememberMe'] = 1;
        }
        $.ajax({
            url: '<?php echo Yii::app()->request->requestUri;?>',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(response) {
                if (response.hasOwnProperty('error') && response.error !== '') {
                    fnLoginError(response.error);
                } else {
                    loggedIn();
                }
            },
            error: function() {
                fnLoginError('An unknown error occurred, please try again.');
            },
            complete: function() {
                if (err) {
                    loggingIn = false;
                    $('#login-button').html('Log In').prop('disabled', false);
                }
            }
        });
    }
}

function loggedIn() {
    var pos = $('#logo-large').offset();
    $('#login-form-wrapper').fadeOut(250, function() {
        $('#logo-large').css({
            position: 'fixed',
            'z-index': 99999,
            top: pos.top + 'px',
            left: pos.left + 'px'
        }).animate({
            width:  42,
            height: 32,
            left: '6px',
            top: '7px'
        }, 500, 'swing', function() {
            $('#navbar-logo').css('visibility', 'visible');
            $('#logo-large.remove');
            $('body').append('<div class="container text-center"><h3>Logged In.  Redirecting...</h3></div>');
            window.location.href = '<?php Yii::app()->createUrl('site/index')?>';
        });
    });
}

function handleSizes() {
    $('#logo-large').css({
        width: Math.min(window.originalLogoWidth, $(window).width())
    });
    $('#login-form-wrapper').css({
        width: $('#login-table').width(),
        'margin-top': ($('#logo-large').height() * -1)
    });
}

$(function() {
    window.originalLogoWidth = $('#logo-large').width();
    handleSizes();
    $(window).resize(function(e) {
        handleSizes();
    });
    $('#navbar-logo').css('visibility', 'hidden');
    $('input[name=username]').focus().select();
    $('#login-button').prop('disabled', false);
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        logIn();
    });
    $('#login-button').on('click', function(e) {
        e.preventDefault();
        logIn();
    });
});
</script>
<?php
$this->pageTitle=Yii::app()->name . ' - Login';

if (isset($errorMessage)):
    echo $errorMessage;
endif;
?>
<div id="login-wrapper">
    <img src="/images/loser-logo-large.png" id="logo-large" style="display:block;margin:0 auto;" />
    <div id="login-form-wrapper">
        <div id="login-error" class="text-danger text-center" style="visibility:hidden;">&nbsp;</div>
        <form method="post" action="<?php echo Yii::app()->request->requestUri;?>" id="login-form">
            <table id="login-table" style="border-spacing:3px;border-collapse:separate;">
                <tr><td>Username:</td><td><input type="text" name="username"/></td></tr>
                <tr><td>Password:</td><td><input type="password" name="password"/></td></tr>
                <tr><td>&nbsp;</td><td><input type="checkbox" name="rememberMe" checked="checked"/> Remember me</td></tr>
                <tr><td>&nbsp;</td><td><button type="submit" class="btn btn-primary" id="login-button">Login</button></td></tr>
            </table>
        </form>
    </div>
</div>