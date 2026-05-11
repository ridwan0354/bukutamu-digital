<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eveent_Settings {

	private $plugin;

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}


	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'eveent-modules' ) === false ) {
			return;
		}

	
		wp_enqueue_style( 
			'eveent-modules-css', 
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/ev-admin-modules.css', 
			[], 
			EWF_VERSION 
		);
		
		wp_enqueue_style( 
        'font-awesome', 
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', 
        [], 
        '5.15.4' 
    );

	
		wp_enqueue_script( 
			'eveent-modules-js', 
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/ev-admin-modules.js', 
			[], 
			EWF_VERSION, 
			true
		);
	}

	public function register_settings() {
		register_setting( 'eveent_modules_group', 'eveent_active_modules' );
	}

	public function render_settings_page() {
		$widgets = [
			'barcode'   => [
				'title' => 'EV Card QR Code',
				'desc'  => 'Sistem Check-in tamu digital menggunakan scan QR Code.',
				'icon'  => 'eicon-apps'
			],
			'rsvp'      => [
				'title' => 'EV RSVP',
				'desc'  => 'Formulir ucapan dan konfirmasi kehadiran tamu undangan.',
				'icon'  => 'dashicons-testimonial'
			],
			
			'comment'   => [
                'title' => 'EV Comment',
                'desc'  => 'Formulir khusus untuk ucapan tanpa fitur konfirmasi kehadiran.',
                'icon'  => 'fas fa-comment-dots'
            ],
    
			'card_atm'  => [
				'title' => 'EV ATM Card',
				'desc'  => 'Tampilkan informasi nomor rek. bank / e-wallet.',
				'icon'  => 'fas fa-credit-card'
			],
			'card_gift' => [
				'title' => 'EV Gift Card',
				'desc'  => 'Informasi alamat untuk pengiriman kado fisik.',
				'icon'  => 'fas fa-gift'
			],
			'audio'     => [
				'title' => 'EV Audio',
				'desc'  => 'Pemutar musik latar belakang otomatis.',
				'icon'  => 'dashicons-format-audio'
			],
			
			'video'     => [
				'title' => 'EV Video',
				'desc'  => 'Pemutar video.',
				'icon'  => 'dashicons-format-video'
			],
			
			'privacy'   => [
				'title' => 'EV Privacy',
				'desc'  => 'Kunci undangan dengan fitur keamanan kata sandi.',
				'icon'  => 'eicon-lock'
			],
			'guestbook_lite' => [
				'title' => 'EV Guestbook Lite',
				'desc'  => 'Versi ringan dari fitur buku tamu digital.',
				'icon'  => 'dashicons-book'
			],
		];

		$active_modules = get_option( 'eveent_active_modules', [] );
		$is_fresh_install = empty( $active_modules ) && !is_array(get_option('eveent_active_modules'));
		?>

		<div class="wrap">
			<h1 class="wp-heading-inline">Kelola Module</h1>
			<p class="description">Aktifkan atau nonaktifkan widget sesuai kebutuhan untuk mengoptimalkan Undangan Digital Anda.</p>
			
			<form action="options.php" method="post">
				<?php settings_fields( 'eveent_modules_group' ); ?>
				
				<div class="ev-modules-grid">
					<?php foreach ( $widgets as $key => $data ) : 
						$checked = ($is_fresh_install && $key !== 'guestbook_lite') || isset( $active_modules[$key] );
					?>
						<div class="ev-module-card <?php echo $checked ? 'active' : ''; ?>">
							<div class="ev-card-header">
								<div class="ev-card-icon">
									<span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>"></span>
								</div>
								<h3 class="ev-card-title"><?php echo esc_html( $data['title'] ); ?></h3>
							</div>
							
							<div class="ev-card-desc">
								<?php echo esc_html( $data['desc'] ); ?>
							</div>

							<div class="ev-card-footer">
								<span class="ev-status-text">
									<?php echo $checked ? 'Aktif' : 'Nonaktif'; ?>
								</span>
								<label class="ev-switch">
									<input type="checkbox" name="eveent_active_modules[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $checked ); ?>>
									<span class="ev-slider"></span>
								</label>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php submit_button( 'Simpan Perubahan', 'primary large' ); ?>
			</form>
		</div>
		<?php
	}
}