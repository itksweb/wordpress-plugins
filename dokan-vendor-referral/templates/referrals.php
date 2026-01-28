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

                <div class="bk-grid bk-grid-4 stats-hub">    
                    <div class="bk-card">
                        <div class="bk-stat-label"><?php _e( "Today's Pay", "dokan-lite" ); ?></div>
                        <div class="bk-stat-value" style="color: #25D366;"><?php echo wc_price( $today_earnings ); ?></div>
                        <div class="bk-stat-footer">
                            <i class="fas fa-bolt"></i> <?php _e( "Real-time tracking", "dokan-lite" ); ?>
                        </div>
                    </div>

                    <div class="bk-card">
                        <div class="bk-stat-label"><?php _e( "Lifetime Pay", "dokan-lite" ); ?></div>
                        <div class="bk-stat-value" style="color: #333;"><?php echo wc_price( $lifetime_earnings ); ?></div>
                        <div class="bk-stat-footer">
                            <i class="fas fa-chart-line" style="color: #2ecc71;"></i> <?php _e( "Cumulative Total", "dokan-lite" ); ?>
                        </div>
                    </div>

                    <div class="bk-card">
                        <div class="bk-stat-label"><?php _e( "Referrals", "dokan-lite" ); ?></div>
                        <div class="bk-stat-value" style="color: #f35d30;"><?php echo count( $referred_vendors ); ?></div>
                        <div class="bk-stat-footer">
                            <i class="fas fa-users"></i> <?php _e( "Active Vendors", "dokan-lite" ); ?>
                        </div>
                    </div>

                    <div class="bk-card bk-card-gradient">
                        <div class="bk-stat-label" style="color: rgba(255,255,255,0.7);"><?php _e( "Wallet Balance", "dokan-lite" ); ?></div>
                        <div class="bk-stat-value"><?php echo wc_price( $total_balance ); ?></div>
                        <div class="bk-stat-footer" style="color: rgba(255,255,255,0.8);">
                            <i class="fas fa-wallet"></i> <?php _e( "Ready for withdraw", "dokan-lite" ); ?>
                        </div>
                    </div>

                </div>

                <div id="bk-toast">
                    <i class="fas fa-check-circle" style="color: #25D366;"></i>
                    <span id="toast-msg">Link Copied!</span>
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

                <div class="bk-grid bk-grid-2">
                    <div class="bk-card performance" style="margin-bottom: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h4 class="bk-card-title" style="margin:0;">
                                <i class="fas fa-chart-area" style="color: #667eea;"></i> <?php _e( 'Performance', 'dokan-lite' ); ?>
                            </h4>
                            <span style="font-size: 10px; color: #999; background: #f8f9fa; padding: 2px 10px; border-radius: 20px;">7 Days</span>
                        </div>

                        <div style="display: flex; align-items: flex-end; justify-content: space-between; height: 120px; gap: 8px;">
                            <?php foreach ( $days_data as $day => $amount ) : 
                                $height_percent = ( $amount / $max_val ) * 100;
                                $display_height = max( $height_percent, 5 ); 
                            ?>
                                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%;">
                                    <div style="flex-grow: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center; position: relative;" class="bar-parent">
                                        <div class="bk-tooltip" style="position: absolute; bottom: calc(<?php echo $display_height; ?>% + 5px); background: #333; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 10px; opacity: 0; transition: 0.2s; pointer-events: none; z-index: 10;"><?php echo wc_price( $amount ); ?></div>
                                        <div style="height: <?php echo $display_height; ?>%; width: 100%; max-width: 30px; background: <?php echo $amount > 0 ? 'linear-gradient(to top, #667eea, #764ba2)' : '#f0f0f0'; ?>; border-radius: 4px;"></div>
                                    </div>
                                    <span style="margin-top: 8px; font-size: 10px; font-weight: 700; color: #bbb;"><?php echo $day; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="bk-card link-gen" style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h4 class="bk-card-title">
                                <i class="fas fa-link" style="color: #764ba2;"></i> <?php _e( 'Link Generator', 'dokan-lite' ); ?>
                            </h4>
                            
                            <div class="bk-tabs">
                                <span id="tab-gen" onclick="switchLinkType('general')" class="bk-tab-item active"><?php _e( 'General', 'dokan-lite' ); ?></span>
                                <span id="tab-prod" onclick="switchLinkType('product')" class="bk-tab-item"><?php _e( 'Product', 'dokan-lite' ); ?></span>
                            </div>

                            <div id="general-link-area">
                                <div class="bk-input-group">
                                    <input type="text" id="active-ref-link" class="bk-input" value="<?php echo esc_url( $referral_url ); ?>" readonly>
                                    <button onclick="copyDynamicLink(this)" class="bk-btn bk-btn-dark"><?php _e( 'Copy', 'dokan-lite' ); ?></button>
                                </div>
                            </div>

                            <div id="product-link-area" style="display: none;">
                                <input type="text" id="product-input" class="bk-product-input" placeholder="Paste product URL...">
                                <button id="btn-generate" onclick="generateProductLink()" class="bk-btn bk-btn-primary">
                                    <?php _e( 'Generate & Copy', 'dokan-lite' ); ?>
                                </button>
                            </div>
                        </div>

                        <div class="bk-social-grid-compact">
                            <span style="font-size: 11px; color: #aaa; font-weight: 700; text-transform: uppercase; margin-right: 5px; align-self: center;"><?php _e( 'Share:', 'dokan-lite' ); ?></span>
                            <a href="javascript:void(0)" onclick="socialShare('wa')" class="bk-social-btn-sm bk-social-wa"><i class="fab fa-whatsapp"></i></a>
                            <a href="javascript:void(0)" onclick="socialShare('fb')" class="bk-social-btn-sm bk-social-fb"><i class="fab fa-facebook-f"></i></a>
                            <a href="javascript:void(0)" onclick="socialShare('tw')" class="bk-social-btn-sm bk-social-tw"><i class="fab fa-x-twitter"></i></a>
                            <a href="javascript:void(0)" onclick="socialShare('em')" class="bk-social-btn-sm bk-social-em"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>

                <div class="bk-grid bk-grid-2">

                    <div class="bk-card bk-saas-card" style="padding:0;">
                        <div class="bk-card-header">
                            <h4 class="bk-card-title" style="margin-bottom:0;">
                                <i class="fas fa-list-ul" style="color: #667eea;"></i> <?php _e( 'Earnings Activity', 'dokan-lite' ); ?>
                            </h4>
                            <span class="bk-header-label"><?php _e( 'Recent', 'dokan-lite' ); ?></span>
                        </div>
                        
                        <div class="bk-table-container">
                            <table class="bk-table">
                                <thead>
                                    <tr>
                                        <th><?php _e( 'Details', 'dokan-lite' ); ?></th>
                                        <th style="text-align: right;"><?php _e( 'Amount', 'dokan-lite' ); ?></th>
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
                                        <tr>
                                            <td>
                                                <div style="font-size: 13px; font-weight: 700; color: #444;">#<?php echo $res->order_id; ?></div>
                                                <div style="font-size: 11px; color: #999;"><?php echo date_i18n( 'M j, Y', strtotime( $order->get_date_created() ) ); ?></div>
                                            </td>
                                            <td style="text-align: right;">
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

                    <div class="bk-card bk-saas-card" style="padding:0;">
                        <div class="bk-card-header">
                            <h4 class="bk-card-title" style="margin-bottom:0;">
                                <i class="fas fa-users" style="color: #f35d30;"></i> <?php _e( 'My Network', 'dokan-lite' ); ?>
                            </h4>
                            <span class="bk-status-pill bk-status-active" style="background:#fff4f1; color:#f35d30;"><?php echo count($referred_vendors); ?></span>
                        </div>

                        <div class="bk-table-container">
                            <table class="bk-table">
                                <thead>
                                    <tr>
                                        <th><?php _e( 'Vendor Store', 'dokan-lite' ); ?></th>
                                        <th style="text-align: right;"><?php _e( 'Status', 'dokan-lite' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ( $referred_vendors ) : 
                                        foreach ( $referred_vendors as $vendor ) : 
                                        $store_info = dokan_get_store_info( $vendor->ID );
                                        $is_active = dokan_is_seller_enabled( $vendor->ID );
                                    ?>
                                        <tr>
                                            <td style="display: flex; align-items: center; border:none;">
                                                <div class="bk-vendor-avatar">
                                                    <?php echo strtoupper( substr( $store_info['store_name'], 0, 1 ) ); ?>
                                                </div>
                                                <div>
                                                    <div style="font-size: 13px; font-weight: 700; color: #444;"><?php echo esc_html( $store_info['store_name'] ); ?></div>
                                                    <div style="font-size: 11px; color: #bbb;"><?php _e( 'Joined', 'dokan-lite' ); ?> <?php echo date_i18n( 'M Y', strtotime( $vendor->user_registered ) ); ?></div>
                                                </div>
                                            </td>
                                            <td style="text-align: right;">
                                                <?php if ( $is_active ) : ?>
                                                    <span class="bk-status-pill bk-status-active"><?php _e( 'ACTIVE', 'dokan-lite' ); ?></span>
                                                <?php else : ?>
                                                    <span class="bk-status-pill bk-status-pending"><?php _e( 'PENDING', 'dokan-lite' ); ?></span>
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



