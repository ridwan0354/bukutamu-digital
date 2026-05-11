<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Utils;
use Elementor\Icons_Manager;

class Eveent_Video_Player extends \Elementor\Widget_Base {

	public function get_name() { return 'ev-video-card'; }
	public function get_title() { return __( 'EV Video Player', 'eveent-widgets' ); }
	public function get_icon() { return 'eicon-video-playlist'; }
	public function get_categories() { return [ 'eveent-widgets' ]; }
	
	public function get_script_depends() {
        wp_register_script(
            'ev-video-js-embed',
            plugins_url('../assets/js/ev-video.js', __FILE__), 
            ['jquery', 'elementor-frontend'],
            '1.0.8', 
            true
        );
		return [ 'ev-video-js-embed' ];
	}

	protected function register_controls() {

		$this->start_controls_section('section_video_content', [ 'label' => __( 'Video Source', 'eveent-widgets' ) ]);
        
        $this->add_control('video_url', [
            'label' => 'Video URL',
            'type' => Controls_Manager::TEXT,
            'dynamic' => ['active' => true],
            'placeholder' => 'Link YouTube, GDrive, or MP4 URL here',
        ]);

		$this->end_controls_section();

		$this->start_controls_section('section_video_settings', [ 'label' => __( 'Player Settings', 'eveent-widgets' ) ]);
		$this->add_control('show_image_overlay', ['label' => 'Cover Image', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
		$this->add_control('image_overlay', ['label' => 'Choose Image', 'type' => Controls_Manager::MEDIA, 'default' => ['url' => Utils::get_placeholder_image_src()], 'condition' => ['show_image_overlay' => 'yes'], 'dynamic' => ['active' => true]]);
		$this->add_control('mute', ['label' => 'Mute Video', 'type' => Controls_Manager::SWITCHER, 'separator' => 'before']);
		$this->add_control('loop', ['label' => 'Loop Video', 'type' => Controls_Manager::SWITCHER]);
		$this->end_controls_section();

		$this->start_controls_section('section_layout', [ 'label' => __( 'Layout & Icons', 'eveent-widgets' ) ]);

		$this->add_responsive_control('container_width', [
			'label' => __( 'Widget Width', 'eveent-widgets' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range' => [ 'px' => [ 'min' => 200, 'max' => 1200 ], '%' => [ 'min' => 10, 'max' => 100 ] ],
			'default' => [ 'unit' => '%', 'size' => 100 ],
			'selectors' => [
				'{{WRAPPER}}' => 'width: {{SIZE}}{{UNIT}}; max-width: 100%; margin: 5 auto;', 
                '{{WRAPPER}} .elementor-widget-container' => 'width: 100%;',
				'{{WRAPPER}} .ev-main-video-card' => 'width: 100%;',
			],
		]);

		$this->add_control('hr_icons', [ 'type' => Controls_Manager::DIVIDER ]);
		
		$this->add_control('play_icon', ['label' => 'Play Icon', 'type' => Controls_Manager::ICONS, 'default' => ['value' => 'fas fa-play', 'library' => 'fa-solid']]);
		$this->add_control('stop_icon', ['label' => 'Stop Icon', 'type' => Controls_Manager::ICONS, 'default' => ['value' => 'fas fa-stop', 'library' => 'fa-solid']]);
        $this->add_control('fullscreen_icon', ['label' => 'Fullscreen Icon', 'type' => Controls_Manager::ICONS, 'default' => ['value' => 'fas fa-expand', 'library' => 'fa-solid']]);
        
		$this->end_controls_section();

		$this->start_controls_section('section_card_style', [ 'label' => 'Card Style', 'tab' => Controls_Manager::TAB_STYLE ]);
		
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name' => 'card_background', 
            'types' => ['classic', 'gradient'], 
            'selector' => '{{WRAPPER}} .ev-main-video-card',
            'fields_options' => [ 'background' => [ 'default' => 'classic' ], 'color' => [ 'default' => '#ffffff' ] ]
        ]);
		
        $this->add_group_control(Group_Control_Border::get_type(), [ 'name' => 'card_border', 'selector' => '{{WRAPPER}} .ev-main-video-card' ]);
		
        $this->add_control('card_border_radius', [
            'label' => 'Border Radius', 
            'type' => Controls_Manager::DIMENSIONS, 
            'size_units' => ['px', '%'], 
            'selectors' => ['{{WRAPPER}} .ev-main-video-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
            'default' => [ 'top' => '24', 'right' => '24', 'bottom' => '24', 'left' => '24', 'unit' => 'px', 'isLinked' => true ], 
        ]);
		
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [ 
            'name' => 'card_box_shadow', 
            'selector' => '{{WRAPPER}} .ev-main-video-card',
            'fields_options' => [
					'box_shadow_type' => [ 'default' => 'yes' ], 
					'box_shadow' => [ 
                        'default' => [ 'horizontal' => 0, 'vertical' => 10, 'blur' => 40, 'spread' => -10, 'color' => 'rgba(0,0,0,0.08)' ] 
                    ]
			]
        ]);
		
        $this->add_responsive_control('card_padding', ['label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .ev-main-video-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
		$this->end_controls_section();

        $this->start_controls_section('section_video_ar', [ 'label' => 'Video Aspect Ratio', 'tab' => Controls_Manager::TAB_STYLE ]);
		$this->add_control('aspect_ratio', [
			'label' => 'Aspect Ratio', 'type' => Controls_Manager::SELECT,
			'options' => ['169'=>'16:9', '219'=>'21:9', '43'=>'4:3', '11'=>'1:1', '916'=>'9:16'],
			'default' => '169',
		]);
		$this->end_controls_section();

        $this->start_controls_section('section_footer_style', [ 'label' => 'Footer & Buttons', 'tab' => Controls_Manager::TAB_STYLE ]);
        
        $this->add_control('footer_align', [
            'label' => 'All Icons Alignment', 
            'type' => Controls_Manager::CHOOSE, 
            'options' => [
                'flex-start'=>['title'=>'Left','icon'=>'eicon-text-align-left'], 
                'center'=>['title'=>'Center','icon'=>'eicon-text-align-center'], 
                'flex-end'=>['title'=>'Right','icon'=>'eicon-text-align-right']
            ], 
            'default'=>'center', 
            'selectors'=>['{{WRAPPER}} .ev-card-footer'=>'justify-content: {{VALUE}};']
        ]);
        
        $this->add_control('footer_bg_color', [
            'label' => 'Footer BG', 'type' => Controls_Manager::COLOR, 
            'selectors' => ['{{WRAPPER}} .ev-card-footer' => 'background-color: {{VALUE}};'],
            'default' => '#ffffff'
        ]);
        
        $this->add_responsive_control('footer_padding', [
            'label' => 'Footer Padding', 'type' => Controls_Manager::DIMENSIONS, 
            'selectors' => ['{{WRAPPER}} .ev-card-footer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
            'default' => [ 'top' => '12', 'right' => '20', 'bottom' => '12', 'left' => '20', 'unit' => 'px', 'isLinked' => false ]
        ]);
        
        $this->add_control('heading_btns', ['label' => 'Buttons', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        
        $this->add_control('btn_color', ['label' => 'Color', 'type' => Controls_Manager::COLOR, 'default' => '#222222', 'selectors' => ['{{WRAPPER}} .ev-control-btn' => 'color: {{VALUE}}', '{{WRAPPER}} .ev-control-btn svg' => 'fill: {{VALUE}}']]);
        
        $this->add_control('btn_bg', ['label' => 'Background', 'type' => Controls_Manager::COLOR, 'default' => 'rgba(0,0,0,0.03)', 'selectors' => ['{{WRAPPER}} .ev-control-btn' => 'background-color: {{VALUE}}']]);
        
        $this->add_group_control(Group_Control_Border::get_type(), [ 'name' => 'btn_border', 'selector' => '{{WRAPPER}} .ev-control-btn' ]);
        
        $this->add_responsive_control('btn_size', ['label' => 'Size', 'type' => Controls_Manager::SLIDER, 'default'=>['size'=>38,'unit'=>'px'], 'selectors' => ['{{WRAPPER}} .ev-control-btn' => 'width: {{SIZE}}px; height: {{SIZE}}px;']]);
        $this->add_responsive_control('btn_icon_size', ['label' => 'Icon Size', 'type' => Controls_Manager::SLIDER, 'default'=>['size'=>12,'unit'=>'px'], 'selectors' => ['{{WRAPPER}} .ev-control-btn' => 'font-size: {{SIZE}}px;', '{{WRAPPER}} .ev-control-btn svg' => 'width: {{SIZE}}px; height: {{SIZE}}px;']]);
        $this->add_control('btn_radius', ['label' => 'Radius', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'default'=>['top'=>'50','right'=>'50','bottom'=>'50','left'=>'50','unit'=>'%','isLinked'=>true], 'selectors' => ['{{WRAPPER}} .ev-control-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        
        $this->add_responsive_control('btn_gap', ['label' => 'Gap', 'type' => Controls_Manager::SLIDER, 'default'=>['size'=>10,'unit'=>'px'], 'selectors' => ['{{WRAPPER}} .ev-card-footer' => 'gap: {{SIZE}}px;']]);
        
        $this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$id_suffix = uniqid();
        $main_id = 'ev-main-' . $id_suffix;
        $is_mute = ( 'yes' === $settings['mute'] ) ? 'yes' : 'no';

        $raw_url = trim($settings['video_url']);
        $src = ''; 
        $type = '';

        
        if (strpos($raw_url, 'youtube.com') !== false || strpos($raw_url, 'youtu.be') !== false) {
            $type = 'youtube';
            $src = str_replace(['watch?v=', 'youtu.be/'], ['embed/', 'youtube.com/embed/'], explode('&', $raw_url)[0]);
            $src .= '?enablejsapi=1&rel=0&modestbranding=1';
        } elseif (strpos($raw_url, 'drive.google.com') !== false) {
            
            $type = 'gdrive';
            
            $src = str_replace(['/view', '/edit'], '/preview', explode('?', $raw_url)[0]);
        } else {
            
            $type = 'hosted';
            $src = $raw_url;
        }

        $vid_html = '';
        if($type === 'hosted') {
             $vid_html = '<video class="ev-player-el" src="'.esc_url($src).'" controls playsinline></video>';
        } else {
             $vid_html = '<iframe class="ev-player-el" data-type="'.esc_attr($type).'" data-src="'.esc_url($src).'" src="about:blank" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        }

        $pads = ['169'=>'56.25%','219'=>'42.85%','43'=>'75%','11'=>'100%','916'=>'177.77%'];
        $pad = $pads[$settings['aspect_ratio']] ?? '56.25%';

		?>
        <style>
            .ev-main-video-card { 
                display: block; position: relative; width: 100%; max-width: 100%; overflow: hidden; margin: 5 auto; box-sizing: border-box;
                border-radius: 20px; 
                background: #fff;
                box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            }
            .ev-video-area { position: relative; width: 100%; height: 0; background: #000; }
            .ev-player-el, .ev-overlay-poster { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; border: none; }
            .ev-overlay-poster { z-index: 10; background-size: cover; background-position: center; transition: opacity 0.4s ease; }
            .ev-main-video-card.is-playing .ev-overlay-poster { opacity: 0; visibility: hidden; pointer-events: none; }
            
            .ev-card-footer { 
                display: flex; 
                align-items: center; 
                position: relative; 
                z-index: 15; width: 100%; box-sizing: border-box;
                padding: 12px 20px;
                background: #fff;
            }
            .ev-control-btn { 
                display: flex; justify-content: center; align-items: center; border: none; cursor: pointer; 
                transition: opacity 0.3s ease;
                width: 38px; height: 38px; padding: 0; 
                border-radius: 50%;
                background: rgba(0,0,0,0.03);
                color: #222;
                font-size: 12px;
                margin: 0; 
            }
            .ev-control-btn svg { width: 12px; height: 12px; }
            
            .ev-control-btn:hover {
                transform: none !important;
                opacity: 0.7; 
            }

            .ev-main-video-card:not(.is-playing) .ev-btn-stop, .ev-main-video-card.is-playing .ev-btn-play { opacity: 0.3; pointer-events: none; }
            .ev-main-video-card.is-playing .ev-btn-stop { opacity: 1; pointer-events: auto; }
            
            .ev-btn-fullscreen {
                display: none;
                transition: opacity 0.3s ease;
            }
            
            .ev-main-video-card.is-playing .ev-btn-fullscreen {
                display: flex; 
                animation: evFadeIn 0.3s forwards;
            }
            
            @keyframes evFadeIn {
                from { opacity: 0; transform: scale(0.9); }
                to { opacity: 1; transform: scale(1); }
            }
            
        </style>

		<div id="<?php echo $main_id; ?>" class="ev-main-video-card" data-mute="<?php echo $is_mute; ?>">
			<div class="ev-video-area" style="padding-bottom: <?php echo esc_attr($pad); ?>;">
				<?php if ( 'yes' === $settings['show_image_overlay'] ) : ?>
					<div class="ev-overlay-poster" style="background-image: url('<?php echo esc_url($settings['image_overlay']['url']); ?>');"></div>
				<?php endif; ?>
				<?php echo $vid_html; ?>
			</div>
			
            <div class="ev-card-footer">
                <button class="ev-control-btn ev-btn-play" title="Play">
                    <?php Icons_Manager::render_icon( $settings['play_icon'] ); ?>
                </button>
                <button class="ev-control-btn ev-btn-stop" title="Stop">
                    <?php Icons_Manager::render_icon( $settings['stop_icon'] ); ?>
                </button>
                <button class="ev-control-btn ev-btn-fullscreen" title="Fullscreen">
                    <?php Icons_Manager::render_icon( $settings['fullscreen_icon'] ); ?>
                </button>
			</div>
		</div>
        
        <script>
        jQuery(document).ready(function($){
            $('#<?php echo $main_id; ?> .ev-btn-fullscreen').on('click', function(e){
                e.preventDefault();
                var card = $(this).closest('.ev-main-video-card');
                var videoElement = card.find('.ev-player-el')[0];
                
                if (videoElement) {
                    if (videoElement.requestFullscreen) {
                        videoElement.requestFullscreen();
                    } else if (videoElement.mozRequestFullScreen) {
                        videoElement.mozRequestFullScreen();
                    } else if (videoElement.webkitRequestFullscreen) {
                        videoElement.webkitRequestFullscreen();
                    } else if (videoElement.msRequestFullscreen) {
                        videoElement.msRequestFullscreen();
                    }
                }
            });
        });
        </script>
		<?php
	}
}