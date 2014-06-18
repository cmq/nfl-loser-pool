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
                url:        CONF.url.like,
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
                                        i = CONF.userId;
                                        if (userLikes.hasOwnProperty(i)) {
                                            delete userLikes[i];
                                        }
                                        if (addActive) {
                                            userLikes[i] = CONF.username;
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
        $('.spawns-popover').not(this).popover('hide');
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
                if (likes.hasOwnProperty(i)) {
                    content += (content == '' ? '' : ', ') + '<a href="' + CONF.url.profile(i) + '">' + likes[i] + '</a>';
                }
            }
            return content;
        },
        placement: 'auto top'
    });
    
    // hide popovers if a click event propagates all the way to the body without being handled
    // @see http://mattlockyer.com/2013/04/08/close-a-twitter-bootstrap-popover-when-clicking-outside/
    // (Modify for this site, since we don't use data-toggle but instead initialize our popovers with JS
    $('body').on('click', function (e) {
        $('.spawns-popover').each(function () {
            //the 'is' for buttons that trigger popups
            //the 'has' for icons within a button that triggers a popup
            if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                $(this).popover('hide');
            }
        });
    });    
});
