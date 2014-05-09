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
// KDHTODO clean up this form
?>
<form method="post" action="<?php echo Yii::app()->request->requestUri;?>">
	Username: <input type="text" name="username"/><br />
	Password: <input type="password" name="password"/><br />
	<input type="checkbox" name="rememberMe" checked="checked"/> Remember me<br />
	<button type="submit">Login</button><br />
</form>
