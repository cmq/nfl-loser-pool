<?php
function generateRandomString($length = 8) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=,.', ceil($length/strlen($x)) )),1,$length);
}
?>
<script language="javascript">
$(function() {
    var fnSave, saving = false;
    
    fnSave = function() {
        var data = {
            email:      $('#email').val(),
            username:   $('#username').val(),
            password:   $('#password').val(),
            salt:       $('#salt').val(),
            firstname:  $('#firstname').val(),
            lastname:   $('#lastname').val(),
            referrer:   $('#referrer').val(),
            active:     $('#active').prop('checked') ? 1 : 0,
            paid:       $('#paid').prop('checked') ? 1 : 0,
            paidnote:   $('#paidnote').val()
        };
        if (saving) {
            // we're already saving
            return false;
        } else {
            saving = true;
            $('#btnSave').prop('disabled', true);
            $.ajax({
                url:        '<?php echo Yii::app()->createAbsoluteUrl('admin/createUser')?>',
                data:       data,
                type:       'post',
                cache:      false,
                success:    function(response) {
                                if (response.hasOwnProperty('error') && response.error != '') {
                                    alert(response.error);
                                } else {
                                    window.location.href = '<?php echo Yii::app()->createAbsoluteUrl('site/index')?>';
                                }
                            },
                error:      function() {
                                alert('An unknown error occurred.');
                            },
                complete:   function() {
                                $('#btnSave').prop('disabled', false);
                                saving = false;
                            },
                dataType:   'json'
            });
        }
    };
    
    $('#btnSave').on('click', fnSave);
});
</script>

<div class="container">
    <h2>Create User</h2>
    <table class="table">
        <tr>
            <th>Email</th>
            <td><input type="text" id="email" maxlength="128" /></td>
        </tr>
        <tr>
            <th>Username</th>
            <td><input type="text" id="username" maxlength="32" /></td>
        </tr>
        <tr>
            <th>Password</th>
            <td><input type="text" id="password" /></td>
        </tr>
        <tr>
            <th>Salt</th>
            <td><input type="text" id="salt" maxlength="8" value="<?php echo generateRandomString(); ?>" /></td>
        </tr>
        <tr>
            <th>First Name</th>
            <td><input type="text" id="firstname" maxlength="32" /></td>
        </tr>
        <tr>
            <th>Last Name</th>
            <td><input type="text" id="lastname" maxlength="32" /></td>
        </tr>
        <tr>
            <th>Referrer</th>
            <td>
                <select id="referrer">
                    <option value="0">-</option>
                    <?php
                    foreach ($users as $user) {
                        ?><option value="<? echo $user->id?>"><? echo $user->username . ' (' . $user->firstname . ' ' . $user->lastname . ')'?></option><?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="active">Active for <? echo getCurrentYear()?>?</label></th>
            <td><input type="checkbox" checked="checked" id="active" /></td>
        </tr>
        <tr>
            <th><label for="paid">Paid</label></th>
            <td><input type="checkbox" id="paid" /></td>
        </tr>
        <tr>
            <th>Paid Note</th>
            <td><input type="text" id="paidnote" maxlength="255" /></td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <button class="btn btn-primary" type="button" id="btnSave">Save</button>
            </td>
        </tr>
    </table>
</div>
