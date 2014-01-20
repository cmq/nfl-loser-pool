<script>
$(function() {
    $('input[name=username]').focus().select();
});
</script>
<?php
$this->pageTitle=Yii::app()->name . ' - Login';

if (isset($errorMessage)):
    echo $errorMessage;
endif;
?>
<form method="post">
	Username: <input type="text" name="username"/><br />
	Password: <input type="password" name="password"/><br />
	<input type="checkbox" name="rememberMe" checked="checked"/> Remember me<br />
	<input type="submit" value="Login"/><br />
</form>
