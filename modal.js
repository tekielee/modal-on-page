const app_version = navigator.appVersion;
const link = document.URL;

console.log( navigator );

jQuery(document).ready(function() {

    showModal(app_version, link);
    
});

function displayModal(id) {

    console.log('displayModal');

    
    jQuery.ajax({

        url: '/wp-json/modal-api/v1/get-meta/' + id,

        type: "GET",

        success: function(response) {

            console.log(response);

            let content = response['content'];

            let associate_url = response['associate_url'];

            jQuery('body').append('<div class="modal-container"><div class="modal-content">' + content + 
            
            '<div class="accept-decline"><button onclick="window.location.href=\'https://www.blackstone.com\';">Decline</button>' +

            '<button onclick="window.location.href=\''+associate_url+'\';">Accept</button></div>' +

            '</div>');

        }

    });

}

function showModal(app_version, link) {

    console.log('/wp-json/modal-api/v1/browser-inf/?app_version=' + app_version);

    jQuery.ajax({

        url: '/wp-json/author_modal/v2/modal/',

        type: "GET",

        success: function(response) {

            //create link hashmap link and id
            let id = response['id'];

            jQuery.ajax({

                url: '/wp-json/modal-api/v1/browser-inf/?app_version=' + app_version,

                type: "GET",

                success: function(response) {

                    console.log(response['count']);

                    if (parseInt(response['count']) === 0) {

                        displayModal(id);

                        ajaxSaveBroswerFingerPrint(app_version);

                        console.log('save');

                    } else {

                        ajaxSaveBroswerFingerPrint(app_version);

                        console.log('save');

                    }

                }

            });

        }

    });

}

function ajaxSaveBroswerFingerPrint (app_version) {
    //console.log( app_version );

    jQuery.ajax ( {

        url: '/wp-admin/admin-ajax.php',

        type: 'POST',

        data: {

            action: 'save_browser_fingerprint',

            app_version: app_version
        },

        success: function ( response ) {

            console.log( response );

        }

    } );

}