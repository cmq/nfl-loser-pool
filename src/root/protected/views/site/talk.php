<script>
$(function() {
    $('#talksubmit').on('click', function(e) {
        var $button = $(this),
            oldHtml = $button.html();
        e.preventDefault();
        $button.prop('disabled', true).html('Saving...');
        $.ajax({
            url:        '<?php echo Yii::app()->createAbsoluteUrl('talk/save')?>',
            data:       {
                            user:    $('#talkuser').val(),
                            message: $('#talkmessage').val()<?php
                            echo isSuperadmin() ? ",admin: $('#talksuperadmin').is(':checked') ? 1 : 0" : '';?>
                        },
            type:       'post',
            cache:      false,
            success:    function(response) {
                            if (response.hasOwnProperty('error') && response.error != '') {
                                // KDHTODO handle error differently
                                alert(response.error);
                            } else {
                                location.href = '<?php echo Yii::app()->createAbsoluteUrl('site/index')?>';
                            }
                        },
            error:      function() {
                            // KDHTODO handle error differently
                            alert('An error occurred, please try again.');
                        },
            complete:   function() {
                            $button.prop('disabled', false).html(oldHtml);
                        },
            dataType:   'json'
        });
            
    });
});
</script>

<?php
// KDHTODO clean up the display of this
?>
<form role="form">
    <div class="form-group">
        <label for="talkuser">Direct Message At</label>
        <select class="form-control" id="talkuser">
            <option value="0"></option>
            <?php
            foreach ($users as $user) {
                echo createOption($user->id, $user->username);
            }
            ?>
        </select>
    </div>
    <div class="form-group">
        <label for="talkuser">Message</label>
        <textarea class="form-control" id="talkmessage"></textarea>
    </div>
    <?php
    if (isSuperadmin()) {
        ?>
        <div class="checkbox">
            <label>
                <input type="checkbox" id="talksuperadmin" /> Post as Administrative Message
            </label>
        </div>
        <?php
    }
    ?>
    <button type="submit" class="btn btn-default" id="talksubmit">Post Message</button><br />
</form>

<?php
foreach ($talks as $talk) {
    $this->renderPartial('//_partials/talk', array('talk'=>$talk));
}