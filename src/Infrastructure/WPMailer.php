<?php
namespace Sults\Writen\Infrastructure;

use Sults\Writen\Contracts\MailerInterface;
use Sults\Writen\Infrastructure\AssetPathResolver;

class WPMailer implements MailerInterface {

	private static array $css_vars = array();
	private AssetPathResolver $asset_resolver;

	public function __construct( AssetPathResolver $asset_resolver ) {
		$this->asset_resolver = $asset_resolver;
	}

	public function send( int $user_id, string $subject, string $message, array $options = array() ): bool {
		$user_info = get_userdata( $user_id );
		if ( ! $user_info ) {
			return false;
		}

		$to             = $user_info->user_email;
		$brand_color    = isset( $options['color'] ) ? $options['color'] : $this->get_css_var( 'color-verdigris-500', '#00acac' );
		$bg_color       = $this->get_css_var( 'color-verdigris-100', '#f6fcfc' );
		$text_color     = $this->get_css_var( 'color-neutral-900', '#202527' );
		$white          = $this->get_css_var( 'color-neutral-100', '#ffffff' );
		$muted_text     = $this->get_css_var( 'color-neutral-500', '#7e8d95' );
		$color_bg_outer = '#EEF0F1';

		$logo_url = $this->asset_resolver->get_image_url( 'sults-white-logo.png' );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$button_html = '';
		if ( ! empty( $options['link'] ) ) {
			$btn_label   = ! empty( $options['link_label'] ) ? $options['link_label'] : 'Acessar Artigo';
			$button_html = sprintf(
				'<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin-top: 30px; margin-bottom: 10px; width: 100%%;">
                    <tr>
                        <td align="center">
                             <a href="%s" style="background-color: %s; color: %s; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; display: inline-block;">%s</a>
                        </td>
                    </tr>
                </table>',
				esc_url( $options['link'] ),
				esc_attr( $brand_color ),
				esc_attr( $white ),
				esc_html( $btn_label )
			);
		}

		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo esc_html( $subject ); ?></title>
		</head>
		<body style="margin: 0; padding: 0; background-color: <?php echo esc_attr( $color_bg_outer ); ?>; font-family: sans-serif;">
			<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: <?php echo esc_attr( $color_bg_outer ); ?>;">
				<tr>
					<td align="center" style="padding: 40px 15px;">

						<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: <?php echo esc_attr( $white ); ?>; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
							
							<tr>
								<td height="6" style="background-color: #0A171F; padding: 20px 40px; font-size: 0; line-height: 0;"><img src="<?php echo esc_url( $logo_url ); ?>" alt="SULTS" height="35" style="display: block; height: 35px; border: 0; justify-self: center;"></td>
							</tr>

							<tr>
								<td style="padding: 40px;">
									<h1 style="color: <?php echo esc_attr( $brand_color ); ?>; margin: 0 0 25px 0; font-size: 24px; font-weight: 700;"><?php echo esc_html( $subject ); ?></h1>
									
									<div style="color: <?php echo esc_attr( $text_color ); ?>; font-size: 16px; line-height: 1.6;">
										<?php echo wp_kses_post( $message ); ?>
									</div>

									<?php echo wp_kses_post( $button_html ); ?>
								</td>
							</tr>

							<tr>
								<td style="padding: 20px 40px; background-color: #fafbfc; border-top: 1px solid <?php echo esc_attr( $color_bg_outer ); ?>;">
									<p style="margin: 0; font-size: 12px; color: <?php echo esc_attr( $muted_text ); ?>; text-align: center;">
										Este é um e-mail automático do sistema <strong>SULTS Writen</strong>, não responda.
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		$body = ob_get_clean();

		return wp_mail( $to, $subject, $body, $headers );
	}

	private function get_css_var( string $var_name, string $fallback ): string {
		if ( empty( self::$css_vars ) ) {
			$this->load_css_variables(); }
		$clean_name = str_replace( 'var(--', '', str_replace( ')', '', $var_name ) );
		$clean_name = ltrim( $clean_name, '-' );
		return isset( self::$css_vars[ $clean_name ] ) ? self::$css_vars[ $clean_name ] : $fallback;
	}

	private function load_css_variables(): void {
		$css_path = plugin_dir_path( dirname( __DIR__ ) . '/sultswriten.php' ) . 'src/assets/css/variables.css';
		if ( ! file_exists( $css_path ) ) {
			return; }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$css_content = file_get_contents( $css_path );
		if ( preg_match_all( '/--([a-zA-Z0-9-]+)\s*:\s*(#[a-fA-F0-9]{3,6})/', $css_content, $matches ) ) {
			foreach ( $matches[1] as $index => $name ) {
				self::$css_vars[ $name ] = $matches[2][ $index ]; }
		}
	}
}