<?php
    
    /**
     * Content for the settings page
     */
    function csv2wp_preview_page() {
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html( __( 'Sorry, you do not have sufficient permissions to access this page.', 'csv2wp' ) ) );
        }
        ?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br/></div>

            <h1>CSV Importer overview</h1>
            
            <?php CSV2WP::csv2wp_show_admin_notices(); ?>

            <div id="csv-importer" class="">
                
                <?php echo CSV2WP::csv2wp_admin_menu(); ?>
                
                <?php
                    if ( isset( $_POST ) && isset( $_POST[ 'csv2wp_file_name' ] ) ) {
                        $file_name = $_POST[ 'csv2wp_file_name' ];
                    } else {
                        $file_name = false;
                    }
                ?>

                <?php $file_index  = csv2wp_check_if_files(); ?>
                <?php if ( $file_index ) { ?>
                    
                    <p><?php esc_html_e( 'Here you can preview any uploaded csv files.', 'csv2wp' ); ?></p>
                    <p><?php esc_html_e( 'Please keep in mind that all csv files are verified before displaying (and therefor can be deleted, when errors are encountered).', 'csv2wp' ); ?></p>
                    
                    <form name="select-preview-file" id="settings-form" action="" method="post">
                        <input name="select_preview_nonce" type="hidden" value="<?php echo wp_create_nonce( 'select-preview-nonce' ); ?>"/>
                        <label for="select-preview-file" class="screen-reader-text"></label>
                        <select name="csv2wp_file_name" id="select-preview-file">
                            <?php $posted_file = ( isset( $_POST[ 'csv2wp_file_name' ] ) ) ? $_POST[ 'csv2wp_file_name' ] : false; ?>
                            <?php if ( count( $file_index ) > 1 ) { ?>
                                <option value=""><?php esc_html_e( 'Choose a file', 'csv2wp' ); ?></option>
                            <?php } ?>
                            <?php foreach ( $file_index as $file ) { ?>
                                <option value="<?php echo $file; ?>"<?php echo( $posted_file == $file ? ' selected' : '' ); ?>><?php echo $file; ?></option>
                            <?php } ?>
                        </select>
                        <p>
                            <label for="csv2wp_header_row" class=""></label>
                            <input name="csv2wp_header_row" id="csv2wp_header_row" type="checkbox" value="true"/> Does the data contain a header row ?
                        </p>
                        <input type="submit" class="admin-button admin-button-small" value="<?php esc_html_e( 'Preview this file', 'csv2wp' ); ?>"/>
                    </form>
                <?php } else { ?>
                    <p><?php esc_html_e( 'You have no files to preview.', 'csv2wp' ); ?></p>
                <?php } ?>
                
                <?php
                    // Get imported data
                    if ( $file_name ) {
                        $delimiter  = ";"; // @TODO: make dynamic
                        $has_header = ( isset( $_POST[ 'csv2wp_header_row' ] ) ) ? true : false;
                        $csv_info   = csv2wp_csv_to_array( $file_name, $delimiter, true, $has_header, true );
                        $header_row = ( isset( $csv_info[ 'column_names' ] ) ) ? $csv_info[ 'column_names' ] : [];
                        
                        if ( isset( $csv_info[ 'data' ] ) && ! empty( $csv_info[ 'data' ] ) ) {
                            echo '<h2>CSV contents</h2>';
                            echo '<table class="csv-preview">';
                            if ( $has_header && ! empty( $header_row ) ) {
                                echo '<thead>';
                                echo '<tr>';
                                foreach ( $header_row as $column ) {
                                    echo '<th>' . $column . '</th>';
                                }
                                echo '</tr>';
                                echo '</thead>';
                            }
                            echo '<tbody>';
                            $line_number = 0;
                            foreach ( $csv_info[ 'data' ] as $line ) {
                                $line_number++;
                                if ( $has_header && '1' == $line_number ) {
                                    // do nothing
                                } else {
                                    echo '<tr>';
                                    foreach ( $line as $column ) {
                                        echo '<td>' . $column . '</td>';
                                    }
                                    echo '</tr>';
                                }
                            }
                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<p class="error_notice">';
                            echo sprintf( __( 'You either have errors in your CSV or there is no data. Verify this file on the <a href="%s">dashboard</a>.', 'csv2wp' ), admin_url() . 'admin.php?page=csv2wp-dashboard' );
                            echo '</p>';
                        }
                    }
                ?>
            </div><!-- end #csv-importer -->

        </div><!-- end .wrap -->
        <?php
    }
