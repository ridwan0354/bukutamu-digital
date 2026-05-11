<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;

class Eveent_EV_Card_Gift_Widget extends Widget_Base {

    public function get_name() {
        return 'ev-gift-card';
    }

    public function get_title() {
        return esc_html__( 'EV Gift Card', 'eveent-widgets' );
    }

    public function get_icon() {
        return 'fas fa-gift';
    }

    public function get_categories() {
        return [ 'eveent-widgets' ];
    }
    
    public function get_keywords() {
        return [ 'gift', 'card', 'ucapan', 'hadiah', 'alamat', 'copy', 'ewf' ];
    }

    public function get_style_depends() {
        return [ 'ewf-card-gift-style' ];
    }

    protected function _register_controls() {

        $this->start_controls_section('section_gift_card_content', [ 
            'label' => esc_html__( 'Gift Card Content', 'eveent-widgets' ) 
        ]);

        $this->add_control('card_title', [ 
            'label' => esc_html__( 'Card Title', 'eveent-widgets' ), 
            'type' => Controls_Manager::TEXT, 
            'default' => esc_html__( 'Kirim Hadiah Untuk', 'eveent-widgets' ), 
            'dynamic' => ['active' => true] 
        ]);

        $this->add_control('recipient_name', [ 
            'label' => esc_html__( 'Recipient Name', 'eveent-widgets' ), 
            'type' => Controls_Manager::TEXT, 
            'default' => esc_html__( 'Fulan & Fulanah', 'eveent-widgets' ), 
            'dynamic' => ['active' => true] 
        ]);

        $this->add_control('recipient_address', [ 
            'label' => esc_html__( 'Recipient Address (Content to Copy)', 'eveent-widgets' ), 
            'type' => Controls_Manager::TEXTAREA, 
            'rows' => 4,
            'default' => esc_html__( "Jl. Kebahagiaan No. 123\nKel. Suka Cita, Kec. Sejahtera\nKota Damai, 12345", 'eveent-widgets' ), 
            'dynamic' => ['active' => true] 
        ]);

        $this->end_controls_section();
        
        $this->start_controls_section('section_card_decoration', [
            'label' => esc_html__( 'Card Decoration', 'eveent-widgets' ),
        ]);

        $this->add_control('show_ribbon', [
            'label' => esc_html__( 'Show Ribbon & Icon', 'eveent-widgets' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => esc_html__( 'Show', 'eveent-widgets' ),
            'label_off' => esc_html__( 'Hide', 'eveent-widgets' ),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);
        
        $this->add_control('ribbon_icon', [
            'label' => esc_html__( 'Ribbon Icon', 'eveent-widgets' ),
            'type' => Controls_Manager::ICONS,
            'default' => [
                'value' => 'fas fa-gift',
                'library' => 'fa-solid',
            ],
            'condition' => [ 'show_ribbon' => 'yes' ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_copy_button', [ 'label' => esc_html__( 'Copy Button', 'eveent-widgets' ) ]);
        $this->add_control('show_copy_button', [ 'label' => esc_html__( 'Show Copy Button', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Show', 'label_off' => 'Hide', 'return_value' => 'yes', 'default' => 'yes' ]);
        $this->add_control('copy_button_text', [ 'label' => esc_html__( 'Button Text', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Salin Alamat', 'condition' => ['show_copy_button' => 'yes'], 'dynamic' => ['active' => true] ]);
        $this->add_control('copy_button_icon', [ 'label' => esc_html__('Button Icon', 'eveent-widgets'), 'type' => Controls_Manager::ICONS, 'default' => [ 'value' => 'far fa-copy', 'library' => 'fa-regular' ], 'condition' => ['show_copy_button' => 'yes'] ]);
        $this->add_control('copy_button_icon_align', [ 'label' => esc_html__('Icon Position', 'eveent-widgets'), 'type' => Controls_Manager::SELECT, 'default' => 'before', 'options' => [ 'before' => 'Before', 'after' => 'After' ], 'condition' => ['copy_button_icon[value]!' => ''] ]);
        $this->add_control('copy_button_icon_indent', [ 'label' => esc_html__('Icon Spacing', 'eveent-widgets'), 'type' => Controls_Manager::SLIDER, 'range' => [ 'px' => [ 'max' => 50 ] ], 'default' => ['size' => 5], 'selectors' => [ '{{WRAPPER}} .ewf-gift-card-btn .ewf-button-icon-after' => 'margin-left: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .ewf-gift-card-btn .ewf-button-icon-before' => 'margin-right: {{SIZE}}{{UNIT}};' ], 'condition' => ['copy_button_icon[value]!' => ''] ]);
        $this->end_controls_section();

        $this->start_controls_section('section_success_alert', [ 'label' => esc_html__( 'Notif Confirm', 'eveent-widgets' ) ]);
        $this->add_control('show_sweetalert', [ 'label' => esc_html__('Use Pop-up Notification', 'eveent-widgets'), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__('Yes', 'eveent-widgets'), 'label_off' => esc_html__('No', 'eveent-widgets'), 'return_value' => 'yes', 'default' => 'yes' ]);
        $this->add_control('copy_button_success_text', [ 'label' => esc_html__( 'Success Text (If Pop-up is Off)', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Tersalin!', 'condition' => ['show_sweetalert!' => 'yes'] ]);
        $this->add_control('sweetalert_title', [ 'label' => esc_html__( 'Title', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Alamat Berhasil Disalin', 'condition' => ['show_sweetalert' => 'yes'], 'dynamic' => ['active' => true] ]);
        $this->add_control('sweetalert_text', [ 'label' => esc_html__(  'Message', 'eveent-widgets' ), 'type' => Controls_Manager::TEXTAREA, 'default' => 'Anda sekarang bisa menempelkan alamat di aplikasi pengiriman.', 'condition' => ['show_sweetalert' => 'yes'], 'dynamic' => ['active' => true] ]);
        $this->end_controls_section();

        $this->start_controls_section('section_card_style', [ 'label' => esc_html__( 'Card Style', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE ]);

        $this->add_responsive_control('card_width', [
            'label' => esc_html__( 'Width', 'eveent-widgets' ),
            'type' => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%', 'vw' ],
            'range' => [
                'px' => [ 'min' => 200, 'max' => 1200 ],
                '%' => [ 'min' => 10, 'max' => 100 ],
                'vw' => [ 'min' => 10, 'max' => 100 ],
            ],
            
            'default' => [
                'unit' => 'px',
                'size' => 450,
            ],
            
            'devices' => [ 'desktop', 'tablet', 'mobile' ],
            'mobile_default' => [ 
                'unit' => '%',
                'size' => 100, 
            ],
            'selectors' => [
                
                
                '{{WRAPPER}}' => 'max-width: {{SIZE}}{{UNIT}}; width: 100%;',
                '{{WRAPPER}} .ewf-gift-card' => 'max-width: {{SIZE}}{{UNIT}}; width: 100%;',
               
              
            ],
        ]);
        
    
        
        $this->add_control('card_skin', [
            'label' => esc_html__( 'Card Skin', 'eveent-widgets' ),
            'type' => Controls_Manager::SELECT,
            'default' => 'none',
            'options' => [
                'none' => esc_html__( 'None (Custom Style)', 'eveent-widgets' ),
                'skin-gold' => esc_html__( 'Classic Gold', 'eveent-widgets' ),
                'skin-dark' => esc_html__( 'Modern Dark', 'eveent-widgets' ),
                'skin-light' => esc_html__( 'Light Minimalist', 'eveent-widgets' ),
            ],
            'prefix_class' => 'ewf-gift-card--',
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [ 'name' => 'card_background', 'selector' => '{{WRAPPER}} .ewf-gift-card', 'types' => [ 'classic', 'gradient', 'video' ] ]);
        $this->add_responsive_control('card_padding', [ 'label' => esc_html__( 'Padding', 'eveent-widgets' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%' ], 'default' => ['top'=>30,'right'=>35,'bottom'=>30,'left'=>35,'unit'=>'px'], 'selectors' => [ '{{WRAPPER}} .ewf-gift-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]);
        $this->add_group_control(Group_Control_Border::get_type(), [ 'name' => 'card_border', 'selector' => '{{WRAPPER}} .ewf-gift-card' ]);
        $this->add_control('card_border_radius', [ 'label' => esc_html__( 'Border Radius', 'eveent-widgets' ), 'type' => Controls_Manager::DIMENSIONS, 'default' => [ 'top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8, 'unit' => 'px' ], 'selectors' => [ '{{WRAPPER}} .ewf-gift-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]);
        
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name' => 'card_box_shadow',
            'selector' => '{{WRAPPER}} .ewf-gift-card',
        ]);
        
        $this->add_control('heading_card_effects', [ 'label' => esc_html__( 'Effects', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        
        $this->add_control('emboss_effect', [
            'label' => esc_html__( 'Emboss Effect on Text', 'eveent-widgets' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'ewf-card-emboss',
            'default' => '',
            'prefix_class' => '',
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_decoration_style', [ 'label' => esc_html__( 'Decoration Style', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE ]);
        $this->add_control('ribbon_color', [ 'label' => esc_html__( 'Ribbon Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-gift-card.has-ribbon::before' => 'border-right-color: {{VALUE}};' ], 'condition' => [ 'show_ribbon' => 'yes' ] ]);
        $this->add_control('ribbon_icon_color', [ 'label' => esc_html__( 'Ribbon Icon Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-ribbon-icon i' => 'color: {{VALUE}};', '{{WRAPPER}} .ewf-ribbon-icon svg' => 'fill: {{VALUE}};' ], 'condition' => [ 'show_ribbon' => 'yes' ] ]);
        $this->add_responsive_control('ribbon_icon_size', [ 'label' => esc_html__( 'Ribbon Icon Size', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 10, 'max' => 50]], 'selectors' => [ '{{WRAPPER}} .ewf-ribbon-icon' => 'font-size: {{SIZE}}{{UNIT}};' ], 'condition' => [ 'show_ribbon' => 'yes' ] ]);
        $this->add_control('heading_separator_style', [ 'label' => esc_html__( 'Separator', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        $this->add_control('separator_color', [ 'label' => esc_html__( 'Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-separator' => 'background-color: {{VALUE}};' ]]);
        $this->add_responsive_control('separator_width', [ 'label' => esc_html__( 'Width', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', '%'], 'selectors' => [ '{{WRAPPER}} .ewf-separator' => 'width: {{SIZE}}{{UNIT}};' ]]);
        $this->add_responsive_control('separator_height', [ 'label' => esc_html__( 'Height', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 1, 'max' => 10]], 'selectors' => [ '{{WRAPPER}} .ewf-separator' => 'height: {{SIZE}}{{UNIT}};' ]]);
        $this->end_controls_section();

        $this->start_controls_section('section_text_style', [ 'label' => esc_html__( 'Text Style', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE ]);
        $this->add_control('heading_card_title_style', [ 'label' => esc_html__( 'Card Title', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING ]);
        $this->add_control('card_title_color', [ 'label' => esc_html__( 'Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-gift-card-title' => 'color: {{VALUE}};' ]]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'card_title_typography', 'selector' => '{{WRAPPER}} .ewf-gift-card-title' ]);
        $this->add_control('heading_recipient_name_style', [ 'label' => esc_html__( 'Recipient Name', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        $this->add_control('recipient_name_color', [ 'label' => esc_html__( 'Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-recipient-name' => 'color: {{VALUE}};' ]]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'recipient_name_typography', 'selector' => '{{WRAPPER}} .ewf-recipient-name' ]);
        $this->add_control('heading_recipient_address_style', [ 'label' => esc_html__( 'Recipient Address', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        $this->add_control('recipient_address_color', [ 'label' => esc_html__( 'Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-recipient-address' => 'color: {{VALUE}};' ]]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'recipient_address_typography', 'selector' => '{{WRAPPER}} .ewf-recipient-address' ]);
        $this->end_controls_section();

        $this->start_controls_section('section_copy_button_style', [ 'label' => esc_html__( 'Copy Button Style', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => ['show_copy_button' => 'yes'] ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'copy_button_typography', 'selector' => '{{WRAPPER}} .ewf-gift-card-btn' ]);
        $this->add_control('copy_button_bg_color', [ 'label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ewf-gift-card-btn' => 'background-color: {{VALUE}}']]);
        $this->add_control('copy_button_text_color', [ 'label' => 'Text & Icon Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ewf-gift-card-btn' => 'color: {{VALUE}}', '{{WRAPPER}} .ewf-gift-card-btn svg' => 'fill: {{VALUE}}' ]]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'copy_button_border', 'selector' => '{{WRAPPER}} .ewf-gift-card-btn']);
        $this->add_control('copy_button_border_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .ewf-gift-card-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]);
        $this->add_control('copy_button_padding', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => [ '{{WRAPPER}} .ewf-gift-card-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]);
        $this->end_controls_section();
        
        $this->start_controls_section('section_alert_style', [
            'label' => esc_html__( 'Notif Confirm Style', 'eveent-widgets' ),
            'tab' => Controls_Manager::TAB_STYLE,
            'condition' => [
                'show_sweetalert' => 'yes',
            ],
        ]);

        $this->add_control('heading_alert_title_style', [
            'label' => esc_html__( 'Title', 'eveent-widgets' ),
            'type' => Controls_Manager::HEADING,
        ]);

        $this->add_control('sweetalert_title_color', [
            'label' => esc_html__( 'Color', 'eveent-widgets' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '.ewf-sa-popup-{{ID}} .swal2-title' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'sweetalert_title_typography','selector' => '.ewf-sa-popup-{{ID}} .swal2-title','fields_options' => ['font_size' => ['selectors' => [ '{{SELECTOR}}' => 'font-size: {{SIZE}}{{UNIT}} !important;'] ] ] ]);
        
        $this->add_control('heading_alert_text_style', [
            'label' => esc_html__( 'Message', 'eveent-widgets' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        
        $this->add_control('sweetalert_text_color', ['label' => esc_html__( 'Color', 'eveent-widgets' ),'type' => Controls_Manager::COLOR,'selectors' => ['.ewf-sa-popup-{{ID}} .swal2-html-container' => 'color:{{VALUE}};', ],]);
        
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'sweetalert_text_typography', 'selector' => '.ewf-sa-popup-{{ID}} .swal2-html-container','fields_options' => ['font_size' => ['selectors' => ['{{SELECTOR}}' => 'font-size: {{SIZE}}{{UNIT}} !important;' ] ] ] ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $widget_id = $this->get_id();
        $address_to_copy = $this->parse_text_editor($settings['recipient_address']);

        $this->add_render_attribute('card', 'class', [
            'ewf-gift-card',
            $settings['emboss_effect']
        ]);
        
        $this->add_render_attribute('copy-button', 'class', 'ewf-gift-card-btn');
        $this->add_render_attribute('copy-button', 'href', '#');
        $this->add_render_attribute('copy-button', 'role', 'button');
        $this->add_render_attribute('copy-button', 'data-copy-content', esc_attr($address_to_copy));
        $this->add_render_attribute('copy-button', 'data-show-alert', $settings['show_sweetalert']);
        
        if ('yes' !== $settings['show_sweetalert']) {
            $this->add_render_attribute('copy-button', 'data-success-text', $settings['copy_button_success_text']);
        }
        
        if ('yes' === $settings['show_sweetalert']) {
            $this->add_render_attribute('copy-button', 'data-sa-title', $settings['sweetalert_title']);
            $this->add_render_attribute('copy-button', 'data-sa-text', $settings['sweetalert_text']);
            $this->add_render_attribute('copy-button', 'data-sa-popup-class', 'ewf-sa-popup-' . $widget_id);
        }

        if ( 'yes' === $settings['show_ribbon'] ) {
             $this->add_render_attribute('card', 'class', 'has-ribbon');
        }
        ?>
        <div <?php echo $this->get_render_attribute_string('card'); ?>>
            
            <?php if ( 'yes' === $settings['show_ribbon'] && ! empty( $settings['ribbon_icon']['value'] ) ) : ?>
                <div class="ewf-ribbon-icon">
                    <?php Icons_Manager::render_icon( $settings['ribbon_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($settings['card_title'])) : ?>
                <div class="ewf-gift-card-title"><?php echo esc_html($this->parse_text_editor($settings['card_title'])); ?></div>
            <?php endif; ?>

            <?php if (!empty($settings['recipient_name'])) : ?>
                <div class="ewf-recipient-name"><?php echo esc_html($this->parse_text_editor($settings['recipient_name'])); ?></div>
            <?php endif; ?>

            <div class="ewf-separator"></div>

            <?php if (!empty($settings['recipient_address'])) : ?>
                <div class="ewf-recipient-address"><?php echo wp_kses_post($address_to_copy); ?></div>
            <?php endif; ?>

            <?php if ('yes' === $settings['show_copy_button']) : ?>
                <a <?php echo $this->get_render_attribute_string('copy-button'); ?>>
                    <span class="ewf-button-text-wrapper">
                        <?php if (!empty($settings['copy_button_icon']['value']) && $settings['copy_button_icon_align'] === 'before') : ?>
                            <span class="ewf-button-icon-before"><?php Icons_Manager::render_icon($settings['copy_button_icon'], ['aria-hidden' => 'true']); ?></span>
                        <?php endif; ?>
                        <span class="ewf-button-text"><?php echo esc_html($settings['copy_button_text']); ?></span>
                        <?php if (!empty($settings['copy_button_icon']['value']) && $settings['copy_button_icon_align'] === 'after') : ?>
                            <span class="ewf-button-icon-after"><?php Icons_Manager::render_icon($settings['copy_button_icon'], ['aria-hidden' => 'true']); ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endif; ?>

        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const widget_<?php echo $widget_id; ?> = document.querySelector('[data-id="<?php echo $widget_id; ?>"]');
            if (!widget_<?php echo $widget_id; ?>) return;

            const copyButton_<?php echo $widget_id; ?> = widget_<?php echo $widget_id; ?>.querySelector('.ewf-gift-card-btn');
            
            if (copyButton_<?php echo $widget_id; ?>) {
                copyButton_<?php echo $widget_id; ?>.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const contentToCopy = this.dataset.copyContent;
                    
                    navigator.clipboard.writeText(contentToCopy).then(() => {
                        const showAlert = this.dataset.showAlert === 'yes';
                        
                        if (showAlert && typeof Swal !== 'undefined') {
                            const popupClass = this.dataset.saPopupClass || '';

                            Swal.fire({
                                title: this.dataset.saTitle || 'Copied!',
                                text: this.dataset.saText || '',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    popup: popupClass
                                }
                            });
                        } else {
                            const buttonText = this.querySelector('.ewf-button-text');
                            if (!buttonText) return;
                            const originalText = buttonText.innerHTML;
                            const successText = this.dataset.successText || 'Copied!';
                            
                            buttonText.innerHTML = successText;
                            this.style.pointerEvents = 'none';

                            setTimeout(() => {
                                buttonText.innerHTML = originalText;
                                this.style.pointerEvents = 'auto';
                            }, 2000);
                        }
                    }).catch(err => console.error('Failed to copy address: ', err));
                });
            }
        });
        </script>
        <?php
    }
}