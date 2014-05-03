<script>
//KDHTODO change this file to be something that turns PHP vars into JS vars
//KDHTODO refactor function below so that it doesn't use PHP and can live in another global js file
$(function() {
    $('.like-button').on('click', function(e) {
        var $this     = $(this),
            like      = $this.hasClass('active') ? 0 : 1,
            addActive = like ? true : false,
            talkid    = $this.data('talkid');
        e.preventDefault();
        if (!$this.hasClass('pending')) {
            $this.addClass('pending');
            $.ajax({
                url:        '<?php echo $this->createAbsoluteUrl('talk/like')?>',
                data:       {
                                talkid: talkid,
                                like:   like
                            },
                type:       'post',
                cache:      false,
                success:    function(response) {
                                var userLikes, numLikes = 0, $likeLink, i;
                                if (response.hasOwnProperty('error') && response.error != '') {
                                    addActive = !addActive;
                                }
                                // try to dynamically update the popover "likes" detail
                                $likeLink = $('.likes[data-talkid=' + talkid + ']');
                                try {
                                    if ($likeLink) {
                                        userLikes = $likeLink.data('likes');
                                        i = <?php echo userId()?>;
                                        if (userLikes.hasOwnProperty(i)) {
                                            delete userLikes[i];
                                        }
                                        if (addActive) {
                                            userLikes[i] = '<?php echo addslashes(userField('username'))?>';
                                        }
                                        for (i in userLikes) {
                                            if (userLikes.hasOwnProperty(i)) {
                                                numLikes++;
                                            }
                                        }
                                        $likeLink.data('likes', userLikes).html('Liked by ' + numLikes + ' User' + (numLikes==1 ? '' : 's'));
                                        if (numLikes > 0) {
                                            $likeLink.removeClass('hidden');
                                        } else {
                                            $likeLink.addClass('hidden');
                                        }
                                    }
                                } catch (e) {
                                }
                            },
                error:      function() {
                                addActive = !addActive;
                            },
                complete:   function() {
                                $this.removeClass('pending');
                                if (addActive) {
                                    $this.addClass('active');
                                } else {
                                    $this.removeClass('active');
                                }
                            },
                dataType:   'json'
            });
        }
    });
    
    $('.likes').on('click', function() {
        $('.likes').not(this).popover('hide');
    }).popover({
        html: true,
        title: 'Message Liked By',
        content: function() {
            var $this = $(this),
                likes = $this.data('likes'),
                i,
                content;

            content = '';
            for (i in likes) {
                // KDHTODO turn these into profile links
                if (likes.hasOwnProperty(i)) {
                    content += (content == '' ? '' : ', ') + '<a href="#">' + likes[i] + '</a>';
                }
            }
            return content;
        },
        placement: 'auto top'
    });
});
</script>
