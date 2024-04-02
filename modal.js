const browser_version = navigator.appVersion;

const current_url = document.URL;

jQuery(document).ready(function() {

    showModal(browser_version, current_url);
    
});

function displayModal(post_id) {

    jQuery.ajax({

        url: '/wp-json/modal-api/v1/modal-content/?post_id=' + post_id,

        type: "GET",

        success: function(response) {

            let content = response['content'];

            let url = response['url'];

            jQuery('body').append('<div class="modal-container"><div class="modal-content">' + content + 
            
            '<div class="accept-decline"><button onclick="window.location.href=\'https://www.blackstone.com\';">Decline</button>' +

            '<button onclick="window.location.href=\''+url+'\';">Accept</button></div>' +

            '</div>');

        }

    });

}

function showModal(browser_version, current_url) {

    jQuery.ajax({

        url: '/wp-json/wp/v2/modal',

        type: "GET",

        success: function(response) {

            const post = response.filter((link) => link['link'] == current_url)[0];

            if(post) {

                const post_id = post['id'];

                const link = post['link'];

                if( link == current_url ) {

                    jQuery.ajax({

                        url: '/wp-json/modal-api/v1/was-visited/?browser_version=' + browser_version + '&post_id=' + post_id,

                        type: "GET",

                        success: function(response) {

                            if (response['count'] === null || parseInt(response['count']) === 0) {

                                displayModal(post_id);

                                ajaxWasVisited(browser_version, post_id);

                            } else {

                                ajaxWasVisited(browser_version, post_id);

                            }

                        }

                    });

                }

            }

        }

    });

}

function ajaxWasVisited (browser_version, post_id) {

    jQuery.ajax ( {

        url: '/wp-admin/admin-ajax.php',

        type: 'POST',

        data: {

            action: 'was_visited',

            browser_version: browser_version,

            post_id: post_id,

        },

        success: function ( response ) {

            console.log( response );

        }

    } );

}

