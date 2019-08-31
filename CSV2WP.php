<?php
    /*
    Plugin Name: CSV to WP
    Version: 0.1
    Plugin URI: https://github.com/Beee4life/csv-to-wp/
    Description: This plugin allows you to import an verify CSV data and imports it to your WordPress database.
    Author: Beee
    Author URI: https://berryplasman.com
    Text-domain: csv2wp
    License: GPL2
       ___  ____ ____ ____
      / _ )/ __/  __/  __/
     / _  / _/   _/   _/
    /____/___/____/____/

    */
    
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    } // Exit if accessed directly
    
    if ( ! class_exists( 'CSV2WP' ) ) :
        
        class CSV2WP {
            var $settings;
            
            public function initialize() {
                $this->settings = array(
                    'path'    => trailingslashit( dirname( __FILE__ ) ),
                    'version' => '0.1',
                );

                // (de)activation hooks
                register_activation_hook( __FILE__,     array( $this, 'csv2wp_plugin_activation' ) );
                register_deactivation_hook( __FILE__,   array( $this, 'csv2wp_plugin_deactivation' ) );
                
                // actions
                add_action( 'admin_menu',               array( $this, 'csv2wp_add_admin_pages' ) );
                add_action( 'admin_enqueue_scripts',    array( $this, 'csv2wp_enqueue_scripts' ) );
                add_action( 'admin_init',               array( $this, 'csv2wp_errors' ) );
                add_action( 'admin_init',               array( $this, 'csv2wp_admin_menu' ) );
                add_action( 'plugins_loaded',           array( $this, 'csv2wp_load_textdomain' ) );
                
                // csv actions
                add_action( 'admin_init',               array( $this, 'csv2wp_upload_functions' ) );
                add_action( 'admin_init',               array( $this, 'csv2wp_handle_file_functions' ) );
                add_action( 'admin_init',               array( $this, 'csv2wp_create_uploads_directory' ) );
                // add_action( 'admin_init',               array( $this, 'csv2wp_import_raw_csv_data' ) );
                
                // add settings link to plugin
                add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'csv2wp_plugin_link' ) );
    
                include( 'includes/functions.php' );
                include( 'includes/csv2wp-help-tabs.php' );
                
                $this->csv2wp_create_uploads_directory();
                
                // add_action( 'admin_menu', array( $this, 'test_this' ) );
                
            }
            
            public function test_this() {
            }
            
            
            /**
             * Function which runs upon plugin deactivation
             */
            public function csv2wp_plugin_activation() {
                $this->csv2wp_store_default_values();
            }
            
            /**
             * Function which runs upon plugin deactivation
             */
            public function csv2wp_plugin_deactivation() {
                delete_option( 'csv2wp_import_role' );
            }
            
            public function csv2wp_create_uploads_directory() {
                if ( true != is_dir( wp_upload_dir()[ 'basedir' ] . '/csv2wp' ) ) {
                    mkdir( wp_upload_dir()[ 'basedir' ] . '/csv2wp', 0755 );
                }
            }
            
            /**
             * Store default values (upon activation)
             */
            public function csv2wp_store_default_values() {
                update_option( 'csv2wp_import_role', 'manage_options' );
            }
    
            public function csv2wp_load_textdomain() {
                // set text domain
                load_plugin_textdomain( 'csv2wp', false, basename( dirname( plugin_basename( __FILE__ ) ) ) . '/languages' );
            }
    
            /**
             * @return WP_Error
             */
            public static function csv2wp_errors() {
                static $wp_error; // Will hold global variable safely
                
                return isset( $wp_error ) ? $wp_error : ( $wp_error = new WP_Error( null, null, null ) );
            }
            
            /**
             * Displays error messages from form submissions
             */
            public static function csv2wp_show_admin_notices() {
                if ( $codes = CSV2WP::csv2wp_errors()->get_error_codes() ) {
                    if ( is_wp_error( CSV2WP::csv2wp_errors() ) ) {
                        
                        // Loop error codes and display errors
                        $error      = false;
                        $span_class = false;
                        $prefix     = false;
                        foreach ( $codes as $code ) {
                            if ( strpos( $code, 'success' ) !== false ) {
                                $span_class = 'notice-success ';
                                $prefix     = false;
                            } elseif ( strpos( $code, 'warning' ) !== false ) {
                                $span_class = 'notice-warning ';
                                $prefix     = esc_html( __( 'Warning', 'csv2wp' ) );
                            } elseif ( strpos( $code, 'info' ) !== false ) {
                                $span_class = 'notice-info ';
                                $prefix     = false;
                            } else {
                                $error      = true;
                                $span_class = 'notice-error ';
                                $prefix     = esc_html( __( 'Error', 'csv2wp' ) );
                            }
                        }
                        echo '<div class="notice ' . $span_class . 'is-dismissible">';
                        foreach ( $codes as $code ) {
                            $message = CSV2WP::csv2wp_errors()->get_error_message( $code );
                            echo '<div class="">';
                            if ( true == $prefix ) {
                                echo '<strong>' . $prefix . ':</strong> ';
                            }
                            echo $message;
                            echo '</div>';
                            echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html( __( 'Dismiss this notice', 'csv2wp' ) ) . '</span></button>';
                        }
                        echo '</div>';
                    }
                }
            }
            
            /**
             * Handle raw uploaded csv data (not in use right now)
             */
            public function csv2wp_import_raw_csv_data() {
                
                if ( current_user_can( 'manage_options' ) && isset( $_POST[ "import_raw_rankings_nonce" ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ "import_raw_rankings_nonce" ], 'import-raw-rankings-nonce' ) ) {
                        CSV2WP::csv2wp_errors()->add( 'error_nonce_no_match', __( 'Something went wrong. Please try again.', 'csv2wp' ) );
                        
                        return;
                    } else {
                        // nonce ok + verified
    
                        $verify    = ! empty( $_POST[ 'verify' ] ) ? $_POST[ 'verify' ] : false;
                        $raw_data  = ! empty( $_POST[ 'raw_csv_import' ] ) ? $_POST[ 'raw_csv_import' ] : false;
                        $csv_array = csv2wp_verify_raw_csv_data( $raw_data );
                        
                        if ( false != $csv_array ) {
                            
                            if ( false != $verify ) {
                                CSV2WP::csv2wp_errors()->add( 'success_no_errors_in_csv', __( 'Congratulations, there appear to be no errors in your CSV.', 'csv2wp' ) );
                                
                                return;
                            } else {
                                if ( count( $csv_array ) > 0 ) {
                                    $count = 0;
                                    foreach ( $csv_array as $csv_line ) {
                                        // do something with $csv_line
                                        $count++;
                                    }
                                    CSV2WP::csv2wp_errors()->add( 'success_raw_data_imported', sprintf( __( '%d lines imported through raw import.', 'csv2wp' ), $count ) );
                                }
                            }
                        }
                    }
                }
            }
            
            /**
             * Read uploaded file for verification or import
             * Or delete the file
             */
            public function csv2wp_handle_file_functions() {
                
                if ( current_user_can( 'manage_options' ) && isset( $_POST[ "select_file_nonce" ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ "select_file_nonce" ], 'select-file-nonce' ) ) {
                        CSV2WP::csv2wp_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'csv2wp' ) ) );
                        
                        return;
                    } else {
                        // nonce ok + verified
                        
                        if ( ! isset( $_POST[ 'csv2wp_row_id' ] ) ) {
                            CSV2WP::csv2wp_errors()->add( "error_no_file_selected", __( "You didn't select a file to handle.", "csv2wp" ) );
                            
                            return;
                        }
                        $row_id       = $_POST[ 'csv2wp_row_id' ];
                        $delimiter    = ! empty( $_POST[ 'csv2wp_delimiter-' . $row_id ] ) ? $_POST[ 'csv2wp_delimiter-' . $row_id ] : ',';
                        $file_name    = ! empty( $_POST[ 'csv2wp_file_name-' . $row_id ] ) ? $_POST[ 'csv2wp_file_name-' . $row_id ] : false;
                        $has_header   = isset( $_POST[ 'csv2wp_header-' . $row_id ] ) ? true : false;
                        $import_where = $_POST[ 'csv2wp_import_in-' . $row_id ];
                        $remove       = ! empty( $_POST[ 'csv2wp_remove' ] ) ? true : false;
                        $verify       = ! empty( $_POST[ 'csv2wp_verify' ] ) ? true : false;
                        
                        if ( false == $remove ) {
                            $csv_array = csv2wp_csv_to_array( $file_name, $delimiter, $verify, $has_header );
                            
                            // import data or verify file
                            if ( false === $verify ) {
    
                                if ( 'table' == $import_where && empty( $_POST[ 'csv2wp_table-' . $row_id ] ) ) {
                                    CSV2WP::csv2wp_errors()->add( "error_no_table_entered", __( "You didn't enter a table, where to import it.", "csv2wp" ) );
    
                                    return;
    
                                } elseif ( 'table' == $import_where && empty( $_POST[ 'csv2wp_header-' . $row_id ] ) ) {
                                    CSV2WP::csv2wp_errors()->add( "error_no_header", __( "You unchecked whether the file has a header row. For insert into table, you must have a header row.", "csv2wp" ) );
    
                                    return;

                                } else {
                                    
                                    // There are no (more) errors, so files can be processed
                                    // $verify = false, so this is for real, this is no verification (aready done in csv2wp_csv_to_array)
    
                                    $line_limit = ( ! empty( $_POST[ 'csv2wp_max_lines-' . $row_id ] ) ) ? $_POST[ 'csv2wp_max_lines-' . $row_id ] : false;
                                    $success    = false;
                                    $table      = $_POST[ 'csv2wp_table-' . $row_id ];
                                    $user_id    = get_current_user_id();
                                    
                                    if ( is_array( $csv_array[ 'data' ] ) ) {
                                        $line_number = 0;
            
                                        if ( 'table' == $import_where ) {
                                            global $wpdb;
                                            foreach( $csv_array[ 'data' ] as $line ) {
                                                $data_line = [];
                                                foreach( $line as $column_name => $value ) {
                                                    $data_line[ 'user_id' ] = $user_id;
                                                    $data_line[ 'added' ]   = date( 'U', current_time( 'timestamp' ) );
                                                    if ( 'type' == $column_name ) {
                                                        $data_line[ strtolower( $column_name ) ] = strtolower( $value );
                                                    } else {
                                                        $data_line[ strtolower( $column_name ) ] = $value;
                                                    }
                                                }
                                                $result = $wpdb->insert( $table, $data_line );
                                                if ( 1 == $result ) {
                                                    $line_number++;
                                                }
                                                if ( false !== $line_limit && $line_limit == $line_number ) {
                                                    break;
                                                }
                                            }
                                            
                                            $success = true;
                                            
                                        } elseif ( in_array( $import_where, [ 'usermeta', 'postmeta' ] ) ) {
    
                                            foreach( $csv_array[ 'data' ] as $line ) {
                                                $header_row = ( true == $has_header ) ? $csv_array[ 'column_names' ] : [];
                                                $post_id    = false;
                                                $user_id    = false;
    
                                                if ( 'postmeta' == $import_where ) {
                                                    if ( ! empty( $header_row ) ) {
                                                        if ( ! in_array( 'post_id', $header_row ) ) {
                                                            CSV2WP::csv2wp_errors()->add( "error_no_postid", sprintf( __( "%s has no column 'post_id'.", "csv2wp" ), $file_name ) );
        
                                                            return;
                                                        } else {
                                                            // get key from post id
                                                            $post_id = $line[ 'post_id' ];
                                                            unset( $line[ 'post_id' ] );
                                                        }
                                                    }
                                                } elseif ( 'usermeta' == $import_where ) {
                                                    if ( ! empty( $header_row ) ) {
                                                        if ( ! in_array( 'user_id', $header_row ) ) {
                                                            CSV2WP::csv2wp_errors()->add( "error_no_userid", sprintf( __( "%s has no column 'user_id'.", "csv2wp" ), $file_name ) );
        
                                                            return;
                                                        } else {
                                                            // get key from user id
                                                            $user_id = $line[ 'user_id' ];
                                                            unset( $line[ 'user_id' ] );
                                                        }
                                                    }
                                                }
    
                                                $result = false;
                                                if ( ! empty( $header_row ) ) {
                                                    foreach ( $line as $meta => $value ) {
                                                        if ( 'postmeta' == $import_where ) {
                                                            if ( false != $post_id ) {
                                                                $result = update_post_meta( $post_id, $meta, $value );
                                                            }
                                                        } elseif ( 'usermeta' == $import_where ) {
                                                            if ( false != $user_id ) {
                                                                $result = update_user_meta( $user_id, $meta, $value );
                                                            }
                                                        }
                                                        if ( false != $result ) {
                                                            $line_number++;
                                                            $success = true;
                                                        }
                                                    }
                                                    
                                                } else {
                                                    // prepare data for update_*_meta
                                                    $id         = $line[ 0 ];
                                                    $meta_key   = $line[ 1 ];
                                                    $meta_value = $line[ 2 ];
    
                                                    if ( false != $id ) {
                                                        if ( 'postmeta' == $import_where ) {
                                                            $result = update_post_meta( $id, $meta_key, $meta_value );
                                                        } elseif ( 'usermeta' == $import_where ) {
                                                            $result = update_user_meta( $id, $meta_key, $meta_value );
                                                        }
                                                        if ( false != $result ) {
                                                            $line_number++;
                                                            $success = true;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
    
                                    if ( true === $success ) {
                                        $result = unlink( wp_upload_dir()[ 'basedir' ] . '/csv2wp/' . $file_name );
                                        if ( true == $result ) {
                                            CSV2WP::csv2wp_errors()->add( 'success_data_imported', __( 'YAY ! ' . $line_number . ' lines are imported and the file is deleted.', 'csv2wp' ) );
                                        } else {
                                            CSV2WP::csv2wp_errors()->add( 'success_data_imported', __( 'YAY ! ' . $line_number . ' lines are imported but the file is not deleted.', 'csv2wp' ) );
                                        }
                                        do_action( 'csv2wp_successful_csv_import', $line_number );
                                    
                                        return;
                                    }
                                }
    
                            } else {
                                // verify == true
    
                                if ( ! empty( $csv_array[ 'data' ] ) ) {
                                    CSV2WP::csv2wp_errors()->add( 'success_no_errors_in_csv', esc_html( __( 'Congratulations, there appear to be no errors in your CSV.', 'csv2wp' ) ) );
    
                                    return;
                                    
                                }
                            }
                            
                        } else {
                            // delete file
                            if ( isset( $_POST[ 'csv2wp_file_name-' . $row_id ] ) ) {
                                unlink( wp_upload_dir()[ 'basedir' ] . '/csv2wp/' . $file_name );
                                CSV2WP::csv2wp_errors()->add( 'success_file_deleted', __( 'File "' . $file_name . '" successfully deleted.', 'csv2wp' ) );
                            }
                        }
                    }
                }
            }
            
            /**
             * Upload a CSV file
             */
            public function csv2wp_upload_functions() {
                
                if ( current_user_can( 'manage_options' ) && isset( $_POST[ "upload_file_nonce" ] ) ) {
                    if ( ! wp_verify_nonce( $_POST[ "upload_file_nonce" ], 'upload-file-nonce' ) ) {
                        CSV2WP::csv2wp_errors()->add( 'error_nonce_no_match', esc_html( __( 'Something went wrong. Please try again.', 'csv2wp' ) ) );
                        
                        return;
                    } else {
                        
                        if ( true != is_dir( wp_upload_dir()[ 'basedir' ] . '/csv2wp' ) ) {
                            mkdir( wp_upload_dir()[ 'basedir' ] . '/csv2wp', 0755 );
                        }
                        $target_file = wp_upload_dir()[ 'basedir' ] . '/csv2wp/' . basename( $_FILES[ 'csv_uploaded_file' ][ 'name' ] );
                        
                        if ( move_uploaded_file( $_FILES[ 'csv_uploaded_file' ][ 'tmp_name' ], $target_file ) ) {
                            // file uploaded succeeded
                            do_action( 'csv2wp_successful_csv_upload' );
                            CSV2WP::csv2wp_errors()->add( 'success_file_uploaded', __( 'File "' . $_FILES[ 'csv_uploaded_file' ][ 'name' ] . '" is successfully uploaded and now shows under <b>\'Handle a CSV file\'</b>.', 'csv2wp' ) );
                            
                            return;
                            
                        } else {
                            // file upload failed
                            CSV2WP::csv2wp_errors()->add( 'error_file_uploaded', esc_html( __( 'Upload failed. Please try again.', 'csv2wp' ) ) );
                            
                            return;
                        }
                    }
                }
            }
            
            /**
             * Adds a link in the plugin menu
             *
             * @param $links
             *
             * @return array
             */
            public function csv2wp_plugin_link( $links ) {
                
                array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=csv2wp-dashboard' ) . '">' . __( 'Import', 'csv2wp' ) . '</a>' );
                
                return $links;
            }
            
            /**
             * Adds a menu on top of the pages
             * @return string
             */
            public static function csv2wp_admin_menu() {
                
                $menu = '<p><a href="' . admin_url() . 'admin.php?page=csv2wp-dashboard">' . esc_html( __( 'Dashboard', 'csv2wp' ) ) . '</a>';
                if ( is_array( csv2wp_check_if_files() ) ) {
                    $menu .= ' | <a href="' . admin_url() . 'admin.php?page=csv2wp-preview">' . esc_html( __( 'Preview file', 'csv2wp' ) ) . '</a>';
                }
                // $menu .= ' | <a href="' . admin_url() . 'admin.php?page=csv2wp-mappings">' . esc_html( __( 'Mappings', 'csv2wp' ) ) . '</a>';
                if ( function_exists( 'csv2wp_settings_page' ) ) {
                    $menu .= ' | <a href="' . admin_url() . 'admin.php?page=csv2wp-settings">' . esc_html( __( 'Settings', 'csv2wp' ) ) . '</a>';
                }
                $menu .= ' | <a href="' . admin_url() . 'admin.php?page=csv2wp-support">' . esc_html( __( 'Support', 'csv2wp' ) ) . '</a>';
                
                return $menu;
                
            }
            
            /**
             * Create admin pages
             */
            public function csv2wp_add_admin_pages() {
                require( 'includes/csv2wp-dashboard.php' );
                add_menu_page( 'CSV Importer', 'CSV to WP', 'manage_options', 'csv2wp-dashboard', 'csv2wp_dashboard_page', 'dashicons-grid-view' );
                
                require( 'includes/csv2wp-preview.php' ); // content for the settings page
                add_submenu_page( 'csv2wp-dashboard', 'Preview', 'Preview', 'manage_options', 'csv2wp-preview', 'csv2wp_preview_page' );
                
                // require( 'includes/csv2wp-mapping.php' ); // content for the settings page
                if ( function_exists( 'csv2wp_mapping_page' ) ) {
                    add_submenu_page( 'csv2wp-dashboard', 'Mapping', 'Mapping', 'manage_options', 'csv2wp-mapping', 'csv2wp_mapping_page' );
                }
                
                // require( 'includes/csv2wp-settings.php' ); // content for the settings page
                if ( function_exists( 'csv2wp_settings_page' ) ) {
                    add_submenu_page( 'csv2wp-dashboard', 'Settings', 'Settings', 'manage_options', 'csv2wp-settings', 'csv2wp_settings_page' );
                }
                
                require( 'includes/csv2wp-support.php' ); // content for the settings page
                add_submenu_page( 'csv2wp-dashboard', 'Support', 'Support', 'manage_options', 'csv2wp-support', 'csv2wp_support_page' );
            }
            
            /**
             * Enqueue CSS
             */
            public function csv2wp_enqueue_scripts() {
                wp_register_style( 'csv2wp', plugins_url( 'assets/css/style.css', __FILE__ ), false, '1.0.0' );
                wp_enqueue_style( 'csv2wp' );
    
                $plugin_dir = plugin_dir_url( __FILE__ );
                wp_register_script( 'sdp', "{$plugin_dir}assets/js/csv2wp.js", array( 'jquery' ), '' );
                wp_enqueue_script( 'sdp' );
    
            }
            
        }
        
    endif; // class_exists check
    
    /**
     * The main function responsible for returning the one true CSV2WP instance to functions everywhere.
     *
     * @return \CSV2WP
     */
    $csv_importer_plugin = new CSV2WP();
    $csv_importer_plugin->initialize();
