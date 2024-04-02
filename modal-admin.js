jQuery(document).ready(function() {

   console.log('admin modal js loaded');

   ajaxSaveAuthorContent ( ajaxurl );

   ajaxGetAuthorContent ();
    
});

function ajaxGetAuthorContent () {

    jQuery('#associate-url-list').change ( function ( e ) {
        
        e.preventDefault();

        let id = jQuery('#associate-url-list :selected').val();

        console.log( id );

        jQuery.ajax({

            url: '/wp-json/modal-api/v1/author-content/?id=' + id,
    
            type: "GET",
    
            success: function(response) {
    
                console.log(response);

                let content = response['content'];

                let associate_url = response['associate_url'];

                let display = response['display'];

                if( parseInt(display) === 1) {

                    jQuery('#display').prop('checked', true);

                } else {

                    jQuery('#display').prop('checked', false);

                }

                jQuery('#author-content').val(content);

                jQuery('#associate-url').val(associate_url);
    
            }
    
          });

    } );

}

function ajaxSaveAuthorContent ( ajaxurl ) {

    jQuery('#save-author-content').click ( function ( e ) {
        
        e.preventDefault();

        let content = jQuery('#author-content').val();

        let associate_url = jQuery('#associate-url').val();

        let display = jQuery('#display').is(":checked");

        if( display ) {

            display = 1;

        } else {

            display = 0;

        }

        console.log( content );

        console.log( associate_url );

        console.log( display );

        jQuery.ajax ( {

            url: ajaxurl,

            type: 'POST',

            data: {

                action: 'save_author_content',

                content: content,

                associate_url: associate_url,

                display: display

            },

            success: function ( response ) {

                jQuery ( '#author-content-message' ).html( response );

            }

        } );

    } );

}
