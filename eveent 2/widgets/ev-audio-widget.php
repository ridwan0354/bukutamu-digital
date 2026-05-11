<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;

class Eveent_Audio_Player extends \Elementor\Widget_Base {

	public function get_name() { return 'ev-audio-player'; }
	public function get_title() { return __( 'EV Audio', 'eveent-widgets' ); }
	public function get_icon() { return 'eicon-headphones'; }
	public function get_categories() { return [ 'eveent-widgets' ]; }
	public function get_script_depends() { return [ 'ev-audio-handler' ]; }
	public function get_style_depends() { return [ 'ev-audio-style' ]; }

	protected function register_controls() {

		$this->start_controls_section(
			'section_audio_config',
			[ 'label' => __( 'Audio Source', 'eveent-widgets' ) ]
		);
		
		$this->add_control(
			'cover_image',
			[
				'label'   => __( 'Cover Image', 'eveent-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => [ 'active' => true ],
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);

		$this->add_control(
			'file_url',
			[
				'label'      => __( 'Media Library (MP3)', 'eveent-widgets' ),
				'type'       => Controls_Manager::MEDIA,
				'media_type' => 'audio',
				'dynamic'    => [ 'active' => true ],
			]
		);

		$this->add_control(
			'external_url',
			[
				'label'       => __( 'External URL / YouTube', 'eveent-widgets' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://example.com/song.mp3 OR https://youtu.be/...',
				'show_external' => false,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->start_controls_tabs( 'tabs_audio_options' );

		$this->start_controls_tab(
			'tab_audio_timing',
			[ 'label' => __( 'Timing', 'eveent-widgets' ) ]
		);

		$this->add_control(
			'start_time',
			[
				'label'   => __( 'Start Time (sec)', 'eveent-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'dynamic' => [ 'active' => true ], 
				'default' => 0,
			]
		);

		$this->add_control(
			'end_time',
			[
				'label'       => __( 'End Time (sec)', 'eveent-widgets' ),
				'type'        => Controls_Manager::NUMBER,
				'description' => '0 = Play until finish.',
				'dynamic'     => [ 'active' => true ], 
				'default'     => 0,
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_audio_effect',
			[ 'label' => __( 'Effects', 'eveent-widgets' ) ]
		);

		$this->add_control(
			'fade_in',
			[
				'label'   => __( 'Fade In Effect', 'eveent-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'yes',
				'options' => [
					'yes' => __( 'Yes', 'eveent-widgets' ),
					'no'  => __( 'No', 'eveent-widgets' ),
				],
				'dynamic' => [ 'active' => true ], 
			]
		);

		$this->add_control(
			'fade_duration',
			[
				'label'     => __( 'Fade Duration (ms)', 'eveent-widgets' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 2000,
				'dynamic'   => [ 'active' => true ], 
				'condition' => [ 'fade_in' => 'yes' ],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_layout_config',
			[ 'label' => __( 'Layout & Position', 'eveent-widgets' ) ]
		);

		$this->add_control(
			'display_mode',
			[
				'label'   => __( 'Display Mode', 'eveent-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'floating',
				'options' => [
					'inline'   => __( 'Inline (Normal)', 'eveent-widgets' ),
					'floating' => __( 'Floating (Sticky)', 'eveent-widgets' ),
				],
			]
		);

		$this->add_control(
			'float_position',
			[
				'label'       => __( 'Alignment', 'eveent-widgets' ),
				'type'        => Controls_Manager::CHOOSE,
				'options'     => [
					'top-left' => [ 'title' => 'Top Left', 'icon' => 'eicon-h-align-left' ],
					'top-right' => [ 'title' => 'Top Right', 'icon' => 'eicon-h-align-right' ],
					'bottom-left' => [ 'title' => 'Bottom Left', 'icon' => 'eicon-h-align-left' ],
					'bottom-right' => [ 'title' => 'Bottom Right', 'icon' => 'eicon-h-align-right' ],
				],
				'default'     => 'bottom-right',
				'toggle'      => false,
				'condition'   => [ 'display_mode' => 'floating' ],
			]
		);
		
		$this->add_control(
			'hide_on_scroll',
			[
				'label'   => __( 'Hide on Scroll', 'eveent-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'eveent-widgets' ),
				'label_off' => __( 'No', 'eveent-widgets' ),
				'return_value' => 'yes',
				'default' => 'no',
				'condition' => [ 'display_mode' => 'floating' ],
			]
		);
		
		$this->add_responsive_control(
			'float_offset_x',
			[
				'label' => __( 'Horizontal Offset', 'eveent-widgets' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [ 'px' => [ 'max' => 100 ] ],
				'default' => [ 'unit' => 'px', 'size' => 20 ],
				'condition' => [ 'display_mode' => 'floating' ],
				'selectors' => [
					'{{WRAPPER}} .ev-player-wrapper' => '--ev-offset-x: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'float_offset_y',
			[
				'label' => __( 'Vertical Offset', 'eveent-widgets' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [ 'px' => [ 'max' => 100 ] ],
				'default' => [ 'unit' => 'px', 'size' => 20 ],
				'condition' => [ 'display_mode' => 'floating' ],
				'selectors' => [
					'{{WRAPPER}} .ev-player-wrapper' => '--ev-offset-y: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'z_index',
			[
				'label' => __( 'Z-Index', 'eveent-widgets' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 999,
				'selectors' => [ '{{WRAPPER}} .ev-player-wrapper' => 'z-index: {{VALUE}};' ],
				'condition' => [ 'display_mode' => 'floating' ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_disc',
			[ 'label' => __( 'Player Style', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);

		$this->add_responsive_control(
			'player_size',
			[
				'label' => __( 'Total Size (px)', 'eveent-widgets' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [ 'px' => [ 'min' => 30, 'max' => 150 ] ],
				'default' => [ 'unit' => 'px', 'size' => 60 ],
				'selectors' => [ '{{WRAPPER}} .ev-player-wrapper' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label' => __( 'Icon Size (px)', 'eveent-widgets' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [ 'px' => [ 'min' => 10, 'max' => 50 ] ],
				'default' => [ 'unit' => 'px', 'size' => 18 ],
				'selectors' => [ '{{WRAPPER}} .ev-toggle-icon' => 'font-size: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_control(
			'ring_active_color', 
			[
				'label'     => __( 'Ring Active Color', 'eveent-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2196F3',
				'selectors' => [ '{{WRAPPER}} .ev-spinner-ring' => 'border-top-color: {{VALUE}}; border-left-color: {{VALUE}};' ],
			]
		);
		
		$this->add_control(
			'ring_idle_color',
			[
				'label'     => __( 'Ring Inactive Color', 'eveent-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.1)',
				'selectors' => [ '{{WRAPPER}} .ev-spinner-ring' => 'border-right-color: {{VALUE}}; border-bottom-color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'ring_thickness', 
			[
				'label' => __( 'Ring Thickness', 'eveent-widgets' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [ 'px' => [ 'max' => 10 ] ],
				'default' => [ 'unit' => 'px', 'size' => 3 ],
				'selectors' => [ '{{WRAPPER}} .ev-spinner-ring' => 'border-width: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_control(
			'ring_speed',
			[
				'label' => __( 'Spin Speed (s)', 'eveent-widgets' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [ 'px' => [ 'min' => 0.5, 'max' => 10, 'step' => 0.1 ] ],
				'default' => [ 'unit' => 'px', 'size' => 1.5 ],
				'selectors' => [ '{{WRAPPER}} .ev-spinner-ring' => 'animation-duration: {{SIZE}}s;' ],
			]
		);
		
		$this->add_control(
			'image_gap', 
			[
				'label' => __( 'Gap Image to Ring', 'eveent-widgets' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [ 'px' => [ 'max' => 20 ] ],
				'default' => [ 'unit' => 'px', 'size' => 5 ],
				'selectors' => [ '{{WRAPPER}} .ev-static-image' => 'inset: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Icon Color', 'eveent-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [ '{{WRAPPER}} .ev-center-icon i' => 'color: {{VALUE}};' ],
			]
		);
		
		$this->add_control(
			'icon_bg_color',
			[
				'label'     => __( 'Icon Background', 'eveent-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.4)',
				'selectors' => [ '{{WRAPPER}} .ev-center-icon' => 'background-color: {{VALUE}};' ],
			]
		);


		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$final_mode = 'none';
		$media_url  = '';

		if ( ! empty( $settings['file_url']['url'] ) ) {
			$final_mode = 'file';
			$media_url  = $settings['file_url']['url'];
		} 
		elseif ( ! empty( $settings['external_url']['url'] ) ) {
			
			$raw_url = $settings['external_url']['url'];

			if ( preg_match( '/(youtube\.com|youtu\.be)/i', $raw_url ) ) {
				$final_mode = 'youtube';
				$media_url  = $raw_url;
			} else {
				$final_mode = 'file';
				$media_url  = $raw_url;
			}
		}

		$config = array(
			'mode'     => $final_mode,
			'link'     => $media_url,
			'start'    => $settings['start_time'],
			'end'      => $settings['end_time'],
			'fade'     => ('yes' === $settings['fade_in']),
			'fade_dur' => $settings['fade_duration'],
		);

		$wrapper_classes = 'ev-player-wrapper';
		if ( 'floating' === $settings['display_mode'] ) {
			$wrapper_classes .= ' ev-floating';
			$pos = $settings['float_position']; 
            $wrapper_classes .= ' ev-pos-' . $pos;
            $is_edit_mode = \Elementor\Plugin::instance()->editor->is_edit_mode();
            if ( 'yes' === $settings['hide_on_scroll'] && !$is_edit_mode ) {
                $wrapper_classes .= ' ev-hide-on-scroll';
            }
		}

		$this->add_render_attribute( 'wrapper', 'class', $wrapper_classes );
		$this->add_render_attribute( 'wrapper', 'data-ev-config', wp_json_encode( $config ) );
		$is_edit_mode = \Elementor\Plugin::instance()->editor->is_edit_mode();
		?>
		
		<?php if ( 'yes' === $settings['hide_on_scroll'] && !$is_edit_mode ) : ?>
		<style>
		.ev-player-wrapper.ev-hide-on-scroll.ev-is-scrolling {
		    transform: none !important;
		    opacity: 0 !important;
		    pointer-events: none !important;
		}
		</style>
		<?php endif; ?>

		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			
			<div class="ev-spinner-ring"></div>

			<div class="ev-static-image">
				<?php echo Group_Control_Image_Size::get_attachment_image_html( $settings, 'thumbnail', 'cover_image' ); ?>
			</div>
			
			<div class="ev-center-icon">
				<i class="fas fa-play ev-toggle-icon"></i> 
			</div>

<div class="ev-media-source" style="display:none;">
    <?php if ( 'youtube' === $final_mode ) : ?>
        <div class="ev-yt-frame"></div>
    <?php elseif ( 'file' === $final_mode ) : ?>
        <audio id="song" class="ev-html5-tag" preload="metadata">
            <source src="<?php echo esc_url( $media_url ); ?>">
        </audio>
    <?php endif; ?>
</div>
		</div>
		<?php
	}
}