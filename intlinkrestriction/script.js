var resume = 0;
jQuery(document).ready(function(){
    wp.data.subscribe(function () {
        var isSavingPost = wp.data.select('core/editor').isSavingPost();
        var isAutosavingPost = wp.data.select('core/editor').isAutosavingPost();

        if (isSavingPost && !isAutosavingPost && !resume) {
            var b = wp.data.select("core/editor");
            var str = b.getEditedPostContent();
            var tmp = new RegExp('href="'+window.location.origin.split('/').join('\\\/').split(':').join('\\\:'), 'g');
            var count = (str.match(tmp) || []).length;
            tmp = new RegExp("href='"+window.location.origin.split('/').join('\\\/').split(':').join('\\\:'), 'g');
            count += (str.match(tmp) || []).length;
            if (conf.count !== -1 && count < conf.count) {
                resume = 1;
                (function (wp) {
                    wp.data.dispatch('core/notices').createNotice(
                        'error', // Can be one of: success, info, warning, error.
                        conf.link_message, // Text string to display.
                        {
                            isDismissible: true, // Whether the user can dismiss the notice.
                        }
                    );
                })(window.wp);
                return false;
            } else {
                tmp = new RegExp('src="'+window.location.origin.split('/').join('\\\/').split(':').join('\\\:'), 'g');
                count = (str.match(tmp) || []).length;
                tmp = new RegExp("src='"+window.location.origin.split('/').join('\\\/').split(':').join('\\\:'), 'g');
                count += (str.match(tmp) || []).length;
                if (conf.image_count !== -1 && count < conf.image_count) {
                    resume = 1;
                    (function (wp) {
                        wp.data.dispatch('core/notices').createNotice(
                            'error', // Can be one of: success, info, warning, error.
                            conf.link_image_message, // Text string to display.
                            {
                                isDismissible: true, // Whether the user can dismiss the notice.
                            }
                        );
                    })(window.wp);
                    return false;
                } else {
                    resume = 1;
                }
            }
        } else {
            if (!(isSavingPost && !isAutosavingPost)) {
                resume = 0;
            }
        }
    });
});