public function configure_phpmailer( $phpmailer ) {
    $options = get_option( $this->option_name );

    // Safety check: ensure options exist
    if ( ! is_array( $options ) || empty( $options['gmail_address'] ) || empty( $options['gmail_password'] ) ) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.gmail.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = $options['gmail_address'];
    $phpmailer->Password   = $options['gmail_password'];
    $phpmailer->SMTPSecure = $options['encryption'] ?? 'tls';
    $phpmailer->Port       = intval( $options['port'] ?? 587 );

    // Get Default WP Email
    $default_wp_email = 'wordpress@' . preg_replace( '/^www\./', '', strtolower( $_SERVER['SERVER_NAME'] ?? '' ) );
    
    // Determine Current 'From'
    $current_from = $phpmailer->From ?: $default_wp_email;
    $current_from_name = $phpmailer->FromName ?: 'WordPress';

    // Check Force Override
    $force = ! empty( $options['force_override'] );

    // Override Email
    if ( $force || strtolower( $current_from ) === strtolower( $default_wp_email ) ) {
        if ( ! empty( $options['from_email'] ) ) {
            $phpmailer->From = $options['from_email'];
        } else {
            $phpmailer->From = $options['gmail_address'];
        }
    }

    // Override Name
    if ( $force || strtolower( $current_from_name ) === 'wordpress' ) {
        if ( ! empty( $options['from_name'] ) ) {
            $phpmailer->FromName = $options['from_name'];
        }
    }
}