<?php
/**
 * Plugin Name: Widget Options Migrator
 * Plugin URI: https://widget-options.com/
 * Description: Migrate Display Widgets instances to Widget Options
 * Version: 1.0
 * Author: Phpbits Creative Studio
 * Author URI: https://phpbits.net/
 * Text Domain: widget-options
 * Domain Path: languages
 *
 * @category Widgets
 * @author Jeffrey Carandang
 * @version 1.0
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'WP_Widget_Options_Migrator' ) ) :

/**
 * Main WP_Widget_Options_Migrator Class.
 *
 * @since 1.0
 */
class WP_Widget_Options_Migrator {

    function __construct(){
        add_action( 'admin_menu', array( &$this, 'options_page' ), 10 );
        add_action( 'wp_ajax_widgetopts_migrator', array( &$this, 'ajax_migration' ) );
    }

    function options_page() {
		add_management_page(
			__( 'Widget Options Migrator', 'widget-options' ),
			__( 'Widget Options Migrator', 'widget-options' ),
			'manage_options',
			'widgetopts_migrator_settings',
            array( &$this, 'settings_page' )
		);
	}

    function settings_page(){ ?>
        <div class="wrap">
			<h1>
				<?php _e( 'Widget Options Migrator', 'widget-options' ); ?>
			</h1>
			<p><?php _e( 'Migrate widget\'s saved instances from Display Widgets plugin to Widget Options easily. Just click the button below and wait until it finishes the migration. If in any case you have already installed Widget Options and saved the widget, it will be skipped. ', 'widget-options' ); ?></p>
			<h3><?php _e( 'List of not included on the migration', 'widget-options' ); ?></h3>
			<p><?php _e( 'You will have to manually add the appropriate conditional code to Display Widget Logic option for the restriction below.', 'widget-options' ); ?></p>
			<ul>
				<li>- <?php _e( 'WPML languages selection', 'widget-options' ); ?></li>
			</ul>
			<br /><br />
			<button class="button button-primary button-large widgetopts-migrator" style="font-size: 16px; padding: 10px 20px 40px;"><?php _e( 'Process Migration', 'widget-options' ); ?></button>
			<span class="spinner" style="float: none; margin-top: 14px;"></span>
			<div class="widgetopts-migrator-success" style="display: none; margin-top: 10px;background: #fff; border-left: 4px solid #46b450; padding: 1px 12px; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
				<p><?php _e( '<p>Migration Complete. Please check the values by going to <strong>Appearance > <a href="'. admin_url( 'widgets.php' ) .'">Widgets</a></strong>.</p>', 'widget-options' ); ?></p>
			</div>

			<script type="text/javascript">
				jQuery( document ).ready(function(){
					jQuery( document ).on( 'click', '.widgetopts-migrator',function(e){
						if( !jQuery( this ).hasClass('disabled') ){
							jQuery( '.spinner' ).css({ 'visibility' : 'visible' });
							jQuery( this ).attr( 'disabled', 'disabled' );
							jQuery( this ).text( "<?php _e( 'Migrating...... please do not close the browser', 'widget-options' ); ?>" );

							jQuery.ajax({
								 type : 'post',
								 dataType : 'json',
								 url : '<?php echo admin_url( 'admin-ajax.php' );?>',
								 data : { action: "widgetopts_migrator", nonce: '<?php echo wp_create_nonce( 'widgetopts_migrator_nonce' ); ?>' },
								 success: function(response) {
								    if( response.type == 'success' ){
										jQuery( '.widgetopts-migrator' ).text( '<?php _e( 'Done', 'widget-options' ); ?>' );
										jQuery( '.widgetopts-migrator-success' ).fadeIn( 'fast', function(){
											jQuery( '.spinner' ).css({ 'visibility' : 'hidden' });
										} );
									}
								 }
							})

							jQuery( this ).addClass('disabled');
						}

						e.preventDefault();
						return false;
					} );
				});
			</script>

		</div>
    <?php }

	function ajax_migration(){
		if ( !wp_verify_nonce( $_REQUEST['nonce'], 'widgetopts_migrator_nonce') ) {
	      exit("No naughty business please");
	   }

	   global $wp_registered_widgets;
	   $checked = array();

	   foreach ( $wp_registered_widgets as $widget ) {
		   $id_base = is_array( $widget['callback'] ) ? $widget['callback'][0]->id_base : '';
		   $opts = array();
		   if( !empty( $id_base ) ){
			   $instance = get_option( 'widget_' . $id_base );

			   if ( isset( $instance['_multiwidget'] ) && $instance['_multiwidget'] ) {
				   $number = $widget['params'][0]['number'];
				   if ( ! isset( $instance[ $number ] ) ) {
					   continue;
				   }

				   $instances = ( isset( $instance[ $number ] ) ) ? $instance[ $number ] : array();
				//    print_r( $instances ); echo '<br /><br />';
				   if( !empty( $instances ) ){
					   $k = 'extended_widget_opts-'. $id_base .'-'. $number;
					   if ( isset( $checked[ $k ] ) ) {
						   continue;
					   }
					   $checked[ $k ] = '1';
					   if( !isset( $instance[ $number ][ $k ] ) ){
						   $opts = array( 'id_base' => $id_base .'-'. $number );

						   //set visibility
						   $opts['visibility'] = array(
							   'misc' => array(),
							   'pages' => array(),
							   'types' => array(),
							   'categories' => array(),
							   'taxonomies' => array(),
							   'tax_terms' => array(),
							);

							$opts['class'] = array( 'logic' => '' );

						   if( isset( $instances[ 'dw_include' ] ) && '1' == $instances[ 'dw_include' ] ){
							   $opts['visibility']['options'] = 'show';
						   }else{
							   $opts['visibility']['options'] = 'hide';
						   }
						   // print_r( $instances ); echo '<br /><br />';
						   foreach ( $instances as $key => $value ) {
							   if ( strpos( $key, 'page-' ) !== false ){
								   switch ( $key ) {
									   case 'page-front':
										   $opts['visibility']['misc']['home'] = '1';
										   break;

									   case 'page-home':
										   $opts['visibility']['misc']['blog'] = '1';
										   break;

									   case 'page-archive':
										   $opts['visibility']['misc']['archives'] = '1';
										   break;

									   case 'page-single':
										   $opts['visibility']['types']['post'] = '1';
										   break;

									   case 'page-404':
										   $opts['visibility']['misc']['404'] = '1';
										   break;

									   case 'page-search':
										   $opts['visibility']['misc']['search'] = '1';
										   break;


									   default:
										   $opts['visibility']['pages'][ str_replace( 'page-', '', $key ) ] = '1';
										   break;
								   }
								   // echo $key . '<br />';
							   }elseif ( strpos( $key, 'type-' ) !== false ){
								   $opts['visibility']['types'][ str_replace( 'type-', '', $key ) ] = '1';
							   }elseif ( strpos( $key, 'cat-' ) !== false ){
								   if( $key == 'cat-all' ){
									   $opts['visibility']['categories'][ 'all_categories' ] = '1';
								   }else{
									   $opts['visibility']['categories'][ str_replace( 'cat-', '', $key ) ] = '1';
								   }
							   }elseif ( strpos( $key, 'tax-' ) !== false ){
								   $opts['visibility']['taxonomies'][ str_replace( 'tax-', '', $key ) ] = '1';
							   }elseif ( $key == 'other_ids' && !empty( $value ) ){

								   if( !empty( $opts['class']['logic'] ) ){
									   $opts['class']['logic'] .= ' && ';
								   }

								   if( isset( $instances[ 'dw_include' ] ) && '1' == $instances[ 'dw_include' ] ){
									   $opts['class']['logic'] .= "is_single( array(". $value .") )";
								   }else{
									   $opts['class']['logic'] .= "!is_single( array(". $value .") )";
								   }

							   }elseif ( $key == 'dw_logged' && !empty( $value ) ){
								   if( !empty( $opts['class']['logic'] ) ){
									   $opts['class']['logic'] .= ' && ';
								   }
								   if( $value == 'in' ){
									   $opts['class']['logic'] .= 'is_user_logged_in()';
								   }elseif( $value == 'out' ){
									   $opts['class']['logic'] .= '!is_user_logged_in()';
								   }
							   }
						   }

						   if( !empty( $opts['class']['logic'] ) ){
							   $opts['class']['logic'] = addslashes( $opts['class']['logic'] );
						   }

						   //add widget options value to dedication widget number
						   $instance[ $number ][ $k ] = $opts;
						   // print_r( $opts );
					   }
				   }
			   }

			   //update option
			   update_option( 'widget_' . $id_base, $instance );
		   }
	   }
	   $result = array( 'type' => 'success' );
	   $result = json_encode($result);
	   echo $result;
	   die();
	}
}

new WP_Widget_Options_Migrator();

endif;
