<script>
$(function() {
    $('#navbar-logo').css('visibility', 'hidden');
    $('input[name=username]').focus().select();
    // KDHTODO remove this:
    $('#test-animation').on('click', function(e) {
        e.preventDefault();
        loggedIn();
    });
});

function loggedIn() {
    $('#login-form-wrapper').fadeOut(250, function() {
        $('#logo-large').css('z-index', 99999).animate({
            width:  42,
            height: 32,
            left: '6px',
            top: '7px'
        }, 500, 'swing', function() {
            $('#navbar-logo').css('visibility', 'visible');
            $('#logo-large.remove');
            window.location.href = '/';
        });
    });
}
</script>
<?php
$this->pageTitle=Yii::app()->name . ' - Login';

if (isset($errorMessage)):
    echo $errorMessage;
endif;
// KDHTODO clean up this form
// KDHTODO remove the test button
// KDHTODO make the form AJAX-y
// KDHTODO do the animation upon successful login
?>
<div id="login-wrapper">
    <img src="/images/loser-logo-large.png" id="logo-large" style="position:fixed;" />
    <div id="login-form-wrapper">
        <form method="post" action="<?php echo Yii::app()->request->requestUri;?>">
        	Username: <input type="text" name="username"/><br />
        	Password: <input type="password" name="password"/><br />
        	<input type="checkbox" name="rememberMe" checked="checked"/> Remember me<br />
        	<button type="submit" class="btn btn-primary">Login</button>
        	<button class="btn" id="test-animation">Test Animation</button>
        </form>
    </div>
</div>