jQuery(document).ready(function($) {

    jQuery('.search-duplicates').click(function() {

        var selected_post_type = jQuery('.rdp-post-types').val();
        var title_contains = jQuery('#rdp-title-contains').val();
        var status = jQuery('#rdp-post-status').find(":selected").val();

        rdp_show_loader();

        jQuery('.rdp-result').show();
        jQuery('.rdp-success').html('');

        var data = {
            'action': 'rdp_ajax_process',
            'selected_post_type': selected_post_type,
            'title_contains': title_contains,
            'status': status,
            'target': 'search_duplicates'
        };

        jQuery.post(ajaxurl, data, function(response) {
            rdp_hide_loader();
            jQuery('.rdp-result').html( response );
            if( response.indexOf('rdp-duplicate-link') > 0 ) // If duplicates found
                jQuery('.rdp-actions').show();
            else
                jQuery('.rdp-actions').hide();
        });
    });

    jQuery('.rdp-delt-permanently').click(function() {
        jQuery('.rdp-result').show();
        rdp_delete_posts( 0 );
    });

    var ajax_interval = 0; var counter = 0;
    function rdp_delete_posts( index_num ) {

        counter = index_num;

        rdp_show_loader();

        var rdp_duplicate_ids = jQuery('#rdp-duplicate-ids').val();

        rdp_duplicate_ids = rdp_duplicate_ids.split(',');

        var dp_id = rdp_duplicate_ids[index_num];

        ajax_interval += ajax_interval + 1000;

        var data = {
            'action': 'rdp_ajax_process',
            'duplicate_ids_to_remove': dp_id,
            'remove_type': 'trash',
            'target': 'remove_duplicates_posts'
        };

        jQuery.post(ajaxurl, data, function(response) {
            counter++;
            rdp_hide_loader();

            var percent = ( counter / rdp_duplicate_ids.length ) * 100;
            percent = percent.toFixed(2);

            if( percent <= 100 ) {
                jQuery('.rdp-progess').show();
                jQuery('.rdp-progress-fill').text( percent + '%');
                jQuery('.rdp-progress-fill').css( 'width',percent+'%' );
            }

            if( counter <= rdp_duplicate_ids.length )
                rdp_delete_posts( counter );

            if( counter == rdp_duplicate_ids.length ) {
                jQuery('.rdp-result').hide();
                jQuery('.rdp-message').html('<div class="rdp-success">All duplicate posts have been removed</div>');
                jQuery('.rdp-success').fadeIn();
                jQuery('.rdp-progess').hide();
                jQuery('.rdp-actions').hide();
            }
        });
    }

    jQuery('.rdp-adv-setting').click(function () {
        jQuery('.rdp-adv-setting-content').fadeIn();
    });
});

function rdp_show_loader() {
    jQuery('.rdp-loader').show();
    jQuery('.rdp-result').css('opacity','0.5');
}

function rdp_hide_loader() {
    jQuery('.rdp-loader').hide();
    jQuery('.rdp-result').css('opacity','1');
}