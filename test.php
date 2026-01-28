<?php
/**
 * Dokan Vendor Referral Dashboard Template
 * * This template follows the official Dokan wrapper pattern to prevent 
 * sidebar overlapping and ensure native styling.
 */

$user_id      = get_current_user_id();
$referral_url = home_url( '/my-account/?action=register&ref=' . $user_id );
$total_earned = get_user_meta( $user_id, 'affiliate_total_earnings', true ) ?: 0;

$referred_vendors = get_users( array(
    'meta_key'   => 'referred_by_vendor',
    'meta_value' => $user_id,
) );
?>

<div class="dokan-dashboard-wrap">

    <?php 
    /**
     * @hooked get_dashboard_side_navigation - 10
     */
    do_action( 'dokan_dashboard_content_before' ); 
    ?>

    <div class="dokan-dashboard-content dokan-referrals-content">
        
        <?php do_action( 'dokan_dashboard_content_inside_before' ); ?>

        <article class="dokan-referrals-area">
            
            <header class="dokan-dashboard-header">
                <h1 class="entry-title"><?php _e( 'Vendor Referrals', 'dokan-lite' ); ?></h1>
            </header>

            <div class="entry-content">
                
                <?php
                $user_id = get_current_user_id();
                global $wpdb;

                // 1. TODAY'S EARNINGS CALCULATION
                $today_start = date('Y-m-d 00:00:00');
                $today_end   = date('Y-m-d 23:59:59');

                $today_earnings = $wpdb->get_var( $wpdb->prepare(
                    "SELECT SUM(pm_amt.meta_value) 
                    FROM {$wpdb->posts} p
                    JOIN {$wpdb->postmeta} pm_recip ON p.ID = pm_recip.post_id
                    JOIN {$wpdb->postmeta} pm_amt ON p.ID = pm_amt.post_id
                    WHERE pm_recip.meta_key = '_referral_commission_recipient'
                    AND pm_recip.meta_value = %d
                    AND pm_amt.meta_key = '_referral_commission_amount'
                    AND p.post_date BETWEEN %s AND %s",
                    $user_id, $today_start, $today_end
                ) ) ?: 0;

                // 2. LIFETIME EARNINGS
                $lifetime_earnings = $wpdb->get_var( $wpdb->prepare(
                    "SELECT SUM(pm.meta_value) FROM {$wpdb->postmeta} pm
                    JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
                    WHERE pm.meta_key = '_referral_commission_amount'
                    AND pm2.meta_key = '_referral_commission_recipient'
                    AND pm2.meta_value = %d", $user_id
                ) ) ?: 0;

                // 3. NETWORK COUNT
                $referred_vendors = get_users( array( 'meta_key' => 'referred_by_vendor', 'meta_value' => $user_id ) );

                // 4. COMBINED BALANCE
                $dokan_balance = (float) dokan_get_seller_balance( $user_id, false );
                $total_balance = $dokan_balance + (float) $lifetime_earnings;
                ?>

                <div class="bk-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
                    
                    <div class="bk-stat-card" style="background: #fff; border-radius: 16px; padding: 24px; border: 1px solid #f0f0f0; box-shadow: 0 4px 20px rgba(0,0,0,0.04); position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -10px; right: -10px; width: 60px; height: 60px; background: rgba(37, 211, 102, 0.1); border-radius: 50%;"></div>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 700; margin-bottom: 8px;"><?php _e( "Today's Pay", "dokan-lite" ); ?></div>
                        <div style="font-size: 28px; font-weight: 800; color: #25D366;"><?php echo wc_price( $today_earnings ); ?></div>
                        <div style="margin-top: 10px; display: flex; align-items: center; font-size: 11px; color: #aaa;">
                            <i class="fas fa-bolt" style="margin-right: 5px;"></i> <?php _e( "Real-time tracking", "dokan-lite" ); ?>
                        </div>
                    </div>

                    <div class="bk-stat-card" style="background: #fff; border-radius: 16px; padding: 24px; border: 1px solid #f0f0f0; box-shadow: 0 4px 20px rgba(0,0,0,0.04);">
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 700; margin-bottom: 8px;"><?php _e( "Lifetime Pay", "dokan-lite" ); ?></div>
                        <div style="font-size: 28px; font-weight: 800; color: #333;"><?php echo wc_price( $lifetime_earnings ); ?></div>
                        <div style="margin-top: 10px; font-size: 11px; color: #2ecc71; font-weight: 600;">
                            <i class="fas fa-chart-line"></i> <?php _e( "Cumulative Total", "dokan-lite" ); ?>
                        </div>
                    </div>

                    <div class="bk-stat-card" style="background: #fff; border-radius: 16px; padding: 24px; border: 1px solid #f0f0f0; box-shadow: 0 4px 20px rgba(0,0,0,0.04);">
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 700; margin-bottom: 8px;"><?php _e( "Referrals", "dokan-lite" ); ?></div>
                        <div style="font-size: 28px; font-weight: 800; color: #f35d30;"><?php echo count( $referred_vendors ); ?></div>
                        <div style="margin-top: 10px; font-size: 11px; color: #aaa;">
                            <i class="fas fa-users"></i> <?php _e( "Active Vendors", "dokan-lite" ); ?>
                        </div>
                    </div>

                    <div class="bk-stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 24px; color: #fff; box-shadow: 0 10px 20px rgba(118, 75, 162, 0.2);">
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.7); font-weight: 700; margin-bottom: 8px;"><?php _e( "Wallet Balance", "dokan-lite" ); ?></div>
                        <div style="font-size: 28px; font-weight: 800; color: #fff;"><?php echo wc_price( $total_balance ); ?></div>
                        <div style="margin-top: 10px; font-size: 11px; color: rgba(255,255,255,0.8);">
                            <i class="fas fa-wallet"></i> <?php _e( "Ready for withdrawal", "dokan-lite" ); ?>
                        </div>
                    </div>

                </div>


                <div id="bk-toast" style="position: fixed; top: 100px; left: 50%; background: #333; color: #fff; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.2); transform: translate(-50%, -150px); transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 9999999; display: flex; align-items: center; gap: 10px; white-space: nowrap; border: 1px solid rgba(255,255,255,0.1);">
                    <i class="fas fa-check-circle" style="color: #25D366;"></i>
                    <span id="toast-msg">Link Copied!</span>
                </div>

                <div class="bk-sharing-hub" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; margin-bottom: 40px;">

                    <div class="bk-card" style="background: #fff; border-radius: 16px; padding: 25px; border: 1px solid #f0f0f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                        <h4 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 700; color: #333;">
                            <i class="fas fa-link" style="color: #764ba2; margin-right: 8px;"></i> <?php _e( 'Link Generator', 'dokan-lite' ); ?>
                        </h4>
                        
                        <div style="display: flex; gap: 15px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                            <span id="tab-gen" onclick="switchLinkType('general')" style="font-size: 12px; font-weight: 700; cursor: pointer; color: #764ba2; border-bottom: 2px solid #764ba2; padding-bottom: 5px; transition: 0.3s;"><?php _e( 'General Referral', 'dokan-lite' ); ?></span>
                            <span id="tab-prod" onclick="switchLinkType('product')" style="font-size: 12px; font-weight: 700; cursor: pointer; color: #aaa; padding-bottom: 5px; transition: 0.3s;"><?php _e( 'Product Promo', 'dokan-lite' ); ?></span>
                        </div>

                        <div id="general-link-area">
                            <p style="font-size: 12px; color: #888; margin-bottom: 10px;"><?php _e( 'Invite new vendors to join the platform.', 'dokan-lite' ); ?></p>
                            <div style="background: #f8f9fa; border: 1px solid #eee; border-radius: 10px; padding: 8px 12px; display: flex; align-items: center;">
                                <input type="text" id="active-ref-link" value="<?php echo esc_url( $referral_url ); ?>" readonly style="flex: 1; border: none; background: transparent; font-size: 13px; font-weight: 600; color: #444; outline: none;">
                                <button onclick="copyDynamicLink(this)" style="background: #333; color: #fff; border: none; padding: 8px 15px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; transition: 0.2s;"><?php _e( 'Copy', 'dokan-lite' ); ?></button>
                            </div>
                        </div>

                        <div id="product-link-area" style="display: none;">
                            <p style="font-size: 12px; color: #888; margin-bottom: 10px;"><?php _e( 'Paste a product URL from our shop.', 'dokan-lite' ); ?></p>
                            <input type="text" id="product-input" placeholder="https://<?php echo $_SERVER['HTTP_HOST']; ?>/product/..." style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #eee; font-size: 13px; margin-bottom: 10px; background: #fcfcfc; outline-color: #764ba2;">
                            <button id="btn-generate" onclick="generateProductLink()" style="width: 100%; background: #764ba2; color: #fff; border: none; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.3s;">
                                <?php _e( 'Generate & Copy Link', 'dokan-lite' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="bk-card" style="background: #fff; border-radius: 16px; padding: 25px; border: 1px solid #f0f0f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: center;">
                        <h4 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: #333;">
                            <i class="fas fa-paper-plane" style="color: #667eea; margin-right: 8px;"></i> <?php _e( 'Promote Active Link', 'dokan-lite' ); ?>
                        </h4>
                        <p style="font-size: 13px; color: #777; margin-bottom: 20px;">
                            <?php _e( 'Share your generated link directly to your social circles.', 'dokan-lite' ); ?>
                        </p>

                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <a href="javascript:void(0)" onclick="socialShare('wa')" style="width: 45px; height: 45px; border-radius: 12px; background: #25D366; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: 0.2s;"><i class="fab fa-whatsapp"></i></a>
                            <a href="javascript:void(0)" onclick="socialShare('fb')" style="width: 45px; height: 45px; border-radius: 12px; background: #1877F2; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: 0.2s;"><i class="fab fa-facebook-f"></i></a>
                            <a href="javascript:void(0)" onclick="socialShare('tw')" style="width: 45px; height: 45px; border-radius: 12px; background: #000; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: 0.2s;"><i class="fab fa-x-twitter"></i></a>
                            <a href="javascript:void(0)" onclick="socialShare('em')" style="width: 45px; height: 45px; border-radius: 12px; background: #ea4335; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: 0.2s;"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>

                <?php
                // Calculate Earnings for the last 7 days for the Trend Chart
                $days_data = [];
                for ( $i = 6; $i >= 0; $i-- ) {
                    $date_start = date( 'Y-m-d 00:00:00', strtotime( "-$i days" ) );
                    $date_end   = date( 'Y-m-d 23:59:59', strtotime( "-$i days" ) );
                    $label      = date( 'D', strtotime( "-$i days" ) );

                    $day_sum = $wpdb->get_var( $wpdb->prepare(
                        "SELECT SUM(pm_amt.meta_value) 
                        FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm_recip ON p.ID = pm_recip.post_id
                        JOIN {$wpdb->postmeta} pm_amt ON p.ID = pm_amt.post_id
                        WHERE pm_recip.meta_key = '_referral_commission_recipient'
                        AND pm_recip.meta_value = %d
                        AND pm_amt.meta_key = '_referral_commission_amount'
                        AND p.post_date BETWEEN %s AND %s",
                        $user_id, $date_start, $date_end
                    ) ) ?: 0;

                    $days_data[$label] = $day_sum;
                }

                $max_val = max( array_values( $days_data ) ) ?: 10; // Prevent division by zero
                ?>

                <div class="bk-trend-container" style="background: #fff; border-radius: 16px; padding: 25px; border: 1px solid #f0f0f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 40px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #333;">
                            <i class="fas fa-chart-area" style="color: #667eea; margin-right: 8px;"></i> <?php _e( 'Weekly Performance', 'dokan-lite' ); ?>
                        </h4>
                        <span style="font-size: 12px; color: #999; background: #f8f9fa; padding: 4px 12px; border-radius: 20px;">
                            <?php _e( 'Last 7 Days', 'dokan-lite' ); ?>
                        </span>
                    </div>

                    <div style="display: flex; align-items: flex-end; justify-content: space-between; height: 150px; padding: 0 10px; gap: 10px;">
                        <?php foreach ( $days_data as $day => $amount ) : 
                            $height_percent = ( $amount / $max_val ) * 100;
                            // Ensure a minimum height so 0 looks like a tiny dot
                            $display_height = max( $height_percent, 5 ); 
                        ?>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%;">
                                <div style="flex-grow: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center; position: relative;" class="bar-parent">
                                    <div style="position: absolute; bottom: calc(<?php echo $display_height; ?>% + 10px); background: #333; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 10px; opacity: 0; transition: opacity 0.2s;" class="bk-tooltip">
                                        <?php echo wc_price( $amount ); ?>
                                    </div>
                                    
                                    <div style="height: <?php echo $display_height; ?>%; width: 100%; max-width: 40px; background: <?php echo $amount > 0 ? 'linear-gradient(to top, #667eea, #764ba2)' : '#f0f0f0'; ?>; border-radius: 6px 6px 4px 4px; transition: height 0.5s ease-out;"></div>
                                </div>
                                <span style="margin-top: 10px; font-size: 11px; font-weight: 600; color: #aaa; text-transform: uppercase;"><?php echo $day; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <style>
                .bar-parent:hover .bk-tooltip { opacity: 1 !important; }
                .bk-stat-card:hover { transform: translateY(-3px); transition: all 0.3s ease; }
                </style>

                <div class="bk-saas-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; margin-top: 20px; align-items: start;">

                    <div class="bk-saas-card" style="background: #fff; border-radius: 16px; border: 1px solid #f0f0f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden; display: flex; flex-direction: column;">
                        <div style="padding: 20px 25px; border-bottom: 1px solid #f8f9fa; display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #333;">
                                <i class="fas fa-list-ul" style="color: #667eea; margin-right: 8px;"></i> <?php _e( 'Earnings Activity', 'dokan-lite' ); ?>
                            </h4>
                            <span style="font-size: 11px; color: #999; font-weight: 600; text-transform: uppercase;"><?php _e( 'Recent', 'dokan-lite' ); ?></span>
                        </div>
                        
                        <div style="overflow-x: auto; max-width: 100%;">
                            <table style="width: 100%; border-collapse: collapse; min-width: 380px;">
                                <thead>
                                    <tr style="background: #fafbfc;">
                                        <th style="text-align: left; padding: 12px 25px; font-size: 11px; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Details', 'dokan-lite' ); ?></th>
                                        <th style="text-align: right; padding: 12px 25px; font-size: 11px; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Amount', 'dokan-lite' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $results = $wpdb->get_results( $wpdb->prepare(
                                        "SELECT p.ID as order_id, pm_amt.meta_value as commission_earned
                                        FROM {$wpdb->posts} p
                                        JOIN {$wpdb->postmeta} pm_recip ON p.ID = pm_recip.post_id
                                        JOIN {$wpdb->postmeta} pm_amt ON p.ID = pm_amt.post_id
                                        WHERE pm_recip.meta_key = '_referral_commission_recipient'
                                        AND pm_recip.meta_value = %d
                                        AND pm_amt.meta_key = '_referral_commission_amount'
                                        ORDER BY p.post_date DESC LIMIT 5", $user_id
                                    ) );

                                    if ( $results ) :
                                        foreach ( $results as $res ) :
                                            $order = wc_get_order( $res->order_id );
                                    ?>
                                        <tr style="border-bottom: 1px solid #f8f9fa;">
                                            <td style="padding: 15px 25px;">
                                                <div style="font-size: 13px; font-weight: 700; color: #444;">#<?php echo $res->order_id; ?></div>
                                                <div style="font-size: 11px; color: #999;"><?php echo date_i18n( 'M j, Y', strtotime( $order->get_date_created() ) ); ?></div>
                                            </td>
                                            <td style="padding: 15px 25px; text-align: right;">
                                                <div style="font-size: 14px; font-weight: 800; color: #2ecc71;"><?php echo wc_price( $res->commission_earned ); ?></div>
                                                <div style="font-size: 9px; color: #bbb; text-transform: uppercase;"><?php echo $order->get_status(); ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; else : ?>
                                        <tr><td colspan="2" style="padding: 40px; text-align: center; font-size: 13px; color: #ccc;"><?php _e( 'No activity found.', 'dokan-lite' ); ?></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bk-saas-card" style="background: #fff; border-radius: 16px; border: 1px solid #f0f0f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden;">
                        <div style="padding: 20px 25px; border-bottom: 1px solid #f8f9fa; display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #333;">
                                <i class="fas fa-users" style="color: #f35d30; margin-right: 8px;"></i> <?php _e( 'My Network', 'dokan-lite' ); ?>
                            </h4>
                            <span style="font-size: 11px; background: #fff4f1; color: #f35d30; padding: 2px 8px; border-radius: 10px; font-weight: 700;"><?php echo count($referred_vendors); ?></span>
                        </div>

                        <div style="overflow-x: auto; max-width: 100%;">
                            <table style="width: 100%; border-collapse: collapse; min-width: 380px;">
                                <thead>
                                    <tr style="background: #fafbfc;">
                                        <th style="text-align: left; padding: 12px 25px; font-size: 11px; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Vendor Store', 'dokan-lite' ); ?></th>
                                        <th style="text-align: right; padding: 12px 25px; font-size: 11px; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Status', 'dokan-lite' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ( $referred_vendors ) : 
                                        foreach ( $referred_vendors as $vendor ) : 
                                        $store_info = dokan_get_store_info( $vendor->ID );
                                        $is_active = dokan_is_seller_enabled( $vendor->ID );
                                    ?>
                                        <tr style="border-bottom: 1px solid #f8f9fa;">
                                            <td style="padding: 15px 25px; display: flex; align-items: center;">
                                                <div style="width: 32px; height: 32px; background: #f0f2f5; color: #667eea; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; margin-right: 12px;">
                                                    <?php echo strtoupper( substr( $store_info['store_name'], 0, 1 ) ); ?>
                                                </div>
                                                <div>
                                                    <div style="font-size: 13px; font-weight: 700; color: #444;"><?php echo esc_html( $store_info['store_name'] ); ?></div>
                                                    <div style="font-size: 11px; color: #bbb;"><?php _e( 'Joined', 'dokan-lite' ); ?> <?php echo date_i18n( 'M Y', strtotime( $vendor->user_registered ) ); ?></div>
                                                </div>
                                            </td>
                                            <td style="padding: 15px 25px; text-align: right;">
                                                <?php if ( $is_active ) : ?>
                                                    <span style="font-size: 9px; font-weight: 800; color: #2ecc71; background: #e8faf0; padding: 4px 10px; border-radius: 20px;"><?php _e( 'ACTIVE', 'dokan-lite' ); ?></span>
                                                <?php else : ?>
                                                    <span style="font-size: 9px; font-weight: 800; color: #f35d30; background: #fff1ef; padding: 4px 10px; border-radius: 20px;"><?php _e( 'PENDING', 'dokan-lite' ); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; else : ?>
                                        <tr><td colspan="2" style="padding: 40px; text-align: center; font-size: 13px; color: #ccc;"><?php _e( 'Network is empty.', 'dokan-lite' ); ?></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="bk-guide-section" style="margin-top: 50px; margin-bottom: 60px;">
                    <h3 style="font-size: 18px; margin-bottom: 25px; font-weight: 700; color: #333; display: flex; align-items: center;">
                        <span style="background: #764ba2; color: #fff; width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-right: 12px;">?</span>
                        <?php _e( 'How to Maximize Your Earnings', 'dokan-lite' ); ?>
                    </h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
                        
                        <div style="background: #000; border-radius: 16px; overflow: hidden; position: relative; aspect-ratio: 16/9; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                            <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(45deg, #1a1a1a, #333);">
                                <i class="fas fa-play-circle" style="font-size: 60px; color: rgba(255,255,255,0.8); cursor: pointer; transition: 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"></i>
                                <p style="color: #fff; font-size: 13px; margin-top: 15px; font-weight: 600;"><?php _e( 'Watch the Program Overview', 'dokan-lite' ); ?></p>
                            </div>
                            </div>

                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            
                            <div style="display: flex; align-items: flex-start; gap: 15px; background: #fff; padding: 15px; border-radius: 12px; border: 1px solid #f0f0f0;">
                                <div style="min-width: 32px; height: 32px; background: #f3ebff; color: #764ba2; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">1</div>
                                <div>
                                    <h5 style="margin: 0 0 4px 0; font-size: 14px; color: #333; font-weight: 700;"><?php _e( 'Pick a Top Product', 'dokan-lite' ); ?></h5>
                                    <p style="margin: 0; font-size: 12px; color: #777; line-height: 1.5;"><?php _e( 'Browse our shop and find products you love. High-rated items convert 3x better.', 'dokan-lite' ); ?></p>
                                </div>
                            </div>

                            <div style="display: flex; align-items: flex-start; gap: 15px; background: #fff; padding: 15px; border-radius: 12px; border: 1px solid #f0f0f0;">
                                <div style="min-width: 32px; height: 32px; background: #f3ebff; color: #764ba2; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">2</div>
                                <div>
                                    <h5 style="margin: 0 0 4px 0; font-size: 14px; color: #333; font-weight: 700;"><?php _e( 'Generate & Share', 'dokan-lite' ); ?></h5>
                                    <p style="margin: 0; font-size: 12px; color: #777; line-height: 1.5;"><?php _e( 'Paste the link into the generator above. Share it on WhatsApp, Facebook, or your blog.', 'dokan-lite' ); ?></p>
                                </div>
                            </div>

                            <div style="display: flex; align-items: flex-start; gap: 15px; background: #fff; padding: 15px; border-radius: 12px; border: 1px solid #f0f0f0;">
                                <div style="min-width: 32px; height: 32px; background: #e8faf0; color: #2ecc71; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">3</div>
                                <div>
                                    <h5 style="margin: 0 0 4px 0; font-size: 14px; color: #333; font-weight: 700;"><?php _e( 'Earn Commissions', 'dokan-lite' ); ?></h5>
                                    <p style="margin: 0; font-size: 12px; color: #777; line-height: 1.5;"><?php _e( 'When someone buys within 30 days of clicking your link, you earn a 5% cut.', 'dokan-lite' ); ?></p>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div> 
        </article>

        <?php do_action( 'dokan_dashboard_content_inside_after' ); ?>

    </div><?php do_action( 'dokan_dashboard_content_after' ); ?>

</div>
<script>
    const vendorRefId = "<?php echo get_current_user_id(); ?>";
    const siteDomain = "<?php echo $_SERVER['HTTP_HOST']; ?>";
    let activeLink = document.getElementById('active-ref-link').value;

    function showToast(message, isError = false) {
        const toast = document.getElementById('bk-toast');
        const msgEl = document.getElementById('toast-msg');
        
        // Update Content
        msgEl.innerText = message;
        toast.style.background = isError ? "#ff4d4f" : "#1a1a1a";
        
        const icon = toast.querySelector('i');
        icon.className = isError ? "fas fa-exclamation-circle" : "fas fa-check-circle";
        icon.style.color = isError ? "#fff" : "#25D366";
        
        // Animate In: Keeping the X centering while moving Y
        toast.style.transform = "translate(-50%, 0)";
        
        // Animate Out
        setTimeout(() => { 
            toast.style.transform = "translate(-50%, -150px)"; 
        }, 3000);
    }

    function switchLinkType(type) {
        const genArea = document.getElementById('general-link-area');
        const prodArea = document.getElementById('product-link-area');
        const tabGen = document.getElementById('tab-gen');
        const tabProd = document.getElementById('tab-prod');

        if(type === 'product') {
            genArea.style.display = 'none'; prodArea.style.display = 'block';
            tabProd.style.color = '#764ba2'; tabProd.style.borderBottom = '2px solid #764ba2';
            tabGen.style.color = '#aaa'; tabGen.style.borderBottom = 'none';
        } else {
            genArea.style.display = 'block'; prodArea.style.display = 'none';
            tabGen.style.color = '#764ba2'; tabGen.style.borderBottom = '2px solid #764ba2';
            tabProd.style.color = '#aaa'; tabProd.style.borderBottom = 'none';
            activeLink = document.getElementById('active-ref-link').value;
        }
    }

    function generateProductLink() {
        const inputField = document.getElementById('product-input');
        const rawUrl = inputField.value.trim();
        
        if(!rawUrl) { showToast("Please paste a URL", true); return; }

        // VALIDATOR: Check if URL belongs to your site
        try {
            const urlObj = new URL(rawUrl);
            if (urlObj.hostname !== siteDomain) {
                showToast("Use links from " + siteDomain + " only", true);
                return;
            }
        } catch (e) {
            showToast("Invalid URL format", true);
            return;
        }

        const separator = rawUrl.includes('?') ? '&' : '?';
        activeLink = rawUrl + separator + 'ref=' + vendorRefId;
        
        copyToClipboard(activeLink);
        showToast("Promo Link Generated & Copied!");
        inputField.value = ""; // Clear for next use
    }

    function copyDynamicLink(btn) {
        copyToClipboard(activeLink);
        const originalText = btn.innerText;
        btn.innerText = "Saved!";
        btn.style.background = "#25D366";
        showToast("General Link Copied!");
        setTimeout(() => { btn.innerText = originalText; btn.style.background = "#333"; }, 2000);
    }

    function copyToClipboard(text) {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }

    function socialShare(platform) {
        const text = encodeURIComponent("Check this out!");
        const finalLink = encodeURIComponent(activeLink);
        let url = "";

        switch(platform) {
            case 'wa': url = `https://api.whatsapp.com/send?text=${text}%20${finalLink}`; break;
            case 'fb': url = `https://www.facebook.com/sharer/sharer.php?u=${finalLink}`; break;
            case 'tw': url = `https://twitter.com/intent/tweet?url=${finalLink}&text=${text}`; break;
            case 'em': url = `mailto:?subject=Recommendation&body=${text}%20${finalLink}`; break;
        }
        window.open(url, '_blank');
    }
</script>