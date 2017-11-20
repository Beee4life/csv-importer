<?php

    /**
     * Content for the settings page
     */
    function csv2wp_settings_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html( __( 'Sorry, you do not have sufficient permissions to access this page.', 'csv2wp' ) ) );
        }
        ?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>

            <h1>CSV Importer settings</h1>

	        <?php CSV_WP::csv2wp_show_admin_notices(); ?>

            <div id="csv-importer" class="">

	            <?php echo CSV_WP::csv2wp_admin_menu(); ?>

                <h2><?php esc_html_e( 'Who can import csv data ?', 'csv2wp' ); ?></h2>
                <p>
		            <?php esc_html_e( 'Here you can select what capability a user needs to import any data. The default setting is "manage_options" which belongs to administrator.', 'csv2wp' ); ?>
		            <?php esc_html_e( 'The reason why it\'s set per capability instead of per user is because two users with the same role can have different capabilities.', 'csv2wp' ); ?>
                </p>

                <form name="settings-form" id="settings-form" action="" method="post">
                    <input name="active_logs_nonce" type="hidden" value="<?php echo wp_create_nonce( 'active-logs-nonce' ); ?>"/>
                    <?php
                        $all_capabilities = get_role( 'administrator' )->capabilities;
                        $logs_user_role   = get_option( 'csv2wp_import_role' );
                        ksort( $all_capabilities );
                    ?>
                    <label for="select_cap" class="screen-reader-text"></label>
                    <select name="select_cap" id="select_cap">
                        <?php foreach ( $all_capabilities as $key => $value ) { ?>
                            <option value="<?php echo $key; ?>"<?php echo ( $logs_user_role == $key ? ' selected' : '' ); ?>><?php echo $key; ?></option>';
                        <?php } ?>
                    </select>
                    <br /><br />
                    <input type="submit" class="admin-button admin-button-small" value="<?php esc_html( _e( 'Save settings', 'csv2wp' ) ); ?>" />
                </form>

            </div><!-- end #csv-importer -->

        </div><!-- end .wrap -->
<?php }