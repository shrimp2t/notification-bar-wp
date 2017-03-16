<?php
/*
Plugin Name: Notification Bar WP
Plugin URI: http://www.famethemes.com
Description:  Custom notification bar for your wordpress site.
Version: 1.0.4
Author: shrimp2t, famethemes
Author URI: http://www.famethemes.com
*/

class  Notification_Bar_WP {

    function __construct() {
        add_action( 'customize_register', array( $this, 'customize_register' ) );
        if ( ! is_admin() ) {
            add_action( 'wp_footer', array( $this, 'display' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
        }
    }

    function scripts(){
        $url = plugins_url( '', __FILE__ );
        wp_enqueue_script( 'notification_bar_wp', $url.'/js.js', array( 'jquery' ), true );
        wp_enqueue_style( 'notification_bar_wp', $url.'/css.css' );
        $css = '';
        $bg = get_theme_mod( 'nbw_bg' );
        $color = get_theme_mod( 'nbw_color' );
        if ( $bg ) {
            $css .=' #notification_bar_wp { background-color: #'.$bg.'; } ';
        }

        if ( $color ) {
            $css .=' #notification_bar_wp, #notification_bar_wp a { color: #'.$color.'; } .nbw-countdown { border-color: #'.$color.'; } ';
        }

        wp_add_inline_style( 'notification_bar_wp', $css );
        // nbw_cookie
        wp_localize_script( 'notification_bar_wp', 'notification_bar_wp', array(
            'c' => abs( floatval( get_theme_mod( 'nbw_cookie', 24 ) ) )
        ) );
    }

    public function timezone_string() {
        $current_offset = get_option( 'gmt_offset' );
        $tzstring       = get_option( 'timezone_string' );

        if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists
            if ( 0 == $current_offset ) {
                $tzstring = 'UTC+0';
            } elseif ( $current_offset < 0 ) {
                $tzstring = 'UTC' . $current_offset;
            } else {
                $tzstring = 'UTC+' . $current_offset;
            }
        }

        return $tzstring;
    }

    function customize_register( $wp_customize ){

        $wp_customize->add_section(
            'notification_bar_wp',
            array(
                'title' => esc_html__('Notification Bar', 'nbw_bg'),
            )
        );

        $wp_customize->add_setting( 'nbw_text', array(
            'priority' 			     => 6,
            'default' 			     => '',
            'sanitize_callback'		 => 'wp_kses_post',
        ) );

        $wp_customize->add_control(
            'nbw_text',
            array(
                'label' 		=> esc_html__( 'Text', 'notification_bar_wp' ),
                'description'   => esc_html__( ' Arbitrary text or HTML.', 'notification_bar_wp' ),
                'section' 		=> 'notification_bar_wp',
                'type' 		    => 'textarea',
            )
        );

        $wp_customize->add_setting( 'nbw_expires', array(
            'priority' 			     => '',
            'default' 			     => '',
            'sanitize_callback'		 => 'sanitize_text_field',
        ) );

        $wp_customize->add_control(
            'nbw_expires',
            array(
                'label' 		=> esc_html__( 'Expires', 'notification_bar_wp' ),
                'description' 	=> sprintf( esc_html__( 'Enter date format: YYYY-MM-DD hh:mm:ss. Time Zone: %1$s', 'notification_bar_wp' ), $this->timezone_string() ),
                'section' 		=> 'notification_bar_wp',
                'type' 		    => 'text',
            )
        );

        $wp_customize->add_setting( 'nbw_expires_text', array(
            'priority' 			     => '',
            'default' 			     => esc_html__( 'Sales ends in', 'notification_bar_wp' ),
            'sanitize_callback'		 => 'sanitize_text_field',
        ) );

        $wp_customize->add_control(
            'nbw_expires_text',
            array(
                'label' 		=> esc_html__( 'Expires Text', 'notification_bar_wp' ),
                'section' 		=> 'notification_bar_wp',
                'type' 		    => 'text',
            )
        );

        $wp_customize->add_setting( 'nbw_icon', array(
            'priority' 			     => '',
            'default' 			     => 'ti-announcement',
            'sanitize_callback'		 => 'wp_kses_post',
        ) );

        $wp_customize->add_control(
            'nbw_icon',
            array(
                'label' 		=> esc_html__( 'Icon', 'notification_bar_wp' ),
                'description' 	=> esc_html__( 'Enter icon class name or paste HTML here.', 'notification_bar_wp' ),
                'section' 		=> 'notification_bar_wp',
                'type' 		    => 'text',
            )
        );

        $wp_customize->add_setting( 'nbw_bg', array(
            'priority' 			     => 6,
            'default' 			     => '#ffffff',
            'sanitize_callback'		 => 'sanitize_hex_color_no_hash',
            'sanitize_js_callback'   => 'maybe_hash_hex_color'
        ) );

        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'nbw_bg', array(
            'label' 		=> esc_html__( 'Background color', 'notification_bar_wp' ),
            'section' 		=> 'notification_bar_wp',
        ) ) );

        $wp_customize->add_setting( 'nbw_color', array(
            'priority' 			     => 6,
            'default' 			     => '#ffffff',
            'sanitize_callback'		 => 'sanitize_hex_color_no_hash',
            'sanitize_js_callback'   => 'maybe_hash_hex_color'
        ) );

        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'nbw_color', array(
            'label' 		=> esc_html__( 'Text color', 'notification_bar_wp' ),
            'section' 		=> 'notification_bar_wp',
        ) ) );

        $wp_customize->add_setting( 'nbw_cookie', array(
            'priority' 			     => '',
            'default' 			     => '24',
            'sanitize_callback'		 => 'sanitize_text_field',
        ) );

        $wp_customize->add_control(
            'nbw_cookie',
            array(
                'label' 		=> esc_html__( 'Cookie expires', 'notification_bar_wp' ),
                'description' 	=> esc_html__( 'Enter number of hours, leave empty or 0 to always show.', 'notification_bar_wp' ),
                'section' 		=> 'notification_bar_wp',
                'type' 		    => 'text',
            )
        );

    }

    function display(){
        $text = get_theme_mod( 'nbw_text' );
        $expires = get_theme_mod( 'nbw_expires' );
        $expires = str_replace( '/', '-', $expires );
        if ( $expires && $text ) {
            $expires = strtotime( $expires );
            $now = current_time('timestamp');

            if ( $now < $expires ) {

                $expires_text = get_theme_mod('nbw_expires_text', esc_html__('Sales ends in', 'notification_bar_wp'));

                $icon = get_theme_mod( 'nbw_icon', 'ti-announcement' );
                $icon = trim( $icon );
                // if not is HTML code
                if ( strpos( $icon, '<' ) === false ) {
                    $icon = '<span class="'.esc_attr( $icon ).'"></span>';
                } else {
                    $icon = balanceTags( $icon , true );
                }

                ?>
                <div id="notification_bar_wp" class="" data-datetime="<?php echo esc_attr( date( 'Y/m/d H:i:s', $expires ) ); ?>">
                    <div id="nbw-content">
                        <div class="nbw-icon"><?php echo $icon; ?></div>
                        <div class="nbw-text">
                            <div class="nbw-countdown">
                                <span class="nbw-countdown-tex"><?php echo $expires_text; ?></span>
                                <span id="nbw-countdown-time"></span>
                            </div>
                            <div class="nbw-msg"><?php echo $text; ?></div>
                        </div>
                        <a href="#" class="nbw-close"><span class="ti-close"></span><span class="screen-reader-text"><?php esc_html_e( 'Close', 'notification_bar_wp' ); ?></span></a>
                    </div>
                </div>
                <?php
            }
        }
    }

}

new Notification_Bar_WP();

