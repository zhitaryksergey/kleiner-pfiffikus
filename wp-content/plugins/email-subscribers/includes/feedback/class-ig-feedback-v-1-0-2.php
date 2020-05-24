<?php
/**
 * IG Feedback
 *
 * The IG Feedback class adds functionality to get quick interactive feedback from users.
 * There are different types of feedabck widget like Stars, Emoji, Thubms Up/ Down, Number etc.
 *
 * @class       IG_Feedback_V_1_0_2
 * @package     feedback
 * @copyright   Copyright (c) 2019, Icegram
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @author      Icegram
 * @since       1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IG_Feedback_V_1_0_2' ) ) {
	/**
	 * Icegram Deactivation Survey.
	 *
	 * This prompts the user for more details when they deactivate the plugin.
	 *
	 * @version    1.0
	 * @package    Icegram
	 * @author     Malay Ladu
	 * @license    GPL-2.0+
	 * @copyright  Copyright (c) 2019
	 */
	class IG_Feedback_V_1_0_2 {

		/**
		 * The API URL where we will send feedback data.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $api_url = 'https://api.icegram.com/store/feedback/'; // Production

		/**
		 * Name for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $name;

		/**
		 * Unique slug for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $plugin;

		/**
		 * Unique slug for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $ajax_action;

		/**
		 * Plugin Abbreviation
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $plugin_abbr;

		/**
		 * Enable/Disable Dev Mode
		 * @var bool
		 */
		public $is_dev_mode = true;

		/**
		 * Set feedback event
		 *
		 * @var string
		 */
		public $event_prefix;

		/**
		 *
		 */
		public $footer = '<span class="ig-powered-by">Made With&nbsp;💜&nbsp;by&nbsp;<a href="https://www.icegram.com/" target="_blank">Icegram</a></span>';

		/**
		 * Primary class constructor.
		 *
		 * @param string $name Plugin name.
		 * @param string $plugin Plugin slug.
		 *
		 * @since 1.0.0
		 */
		public function __construct( $name = '', $plugin = '', $plugin_abbr = 'ig_fb', $event_prefix = 'igfb.', $is_dev_mode = false ) {

			$this->name         = $name;
			$this->plugin       = $plugin;
			$this->plugin_abbr  = $plugin_abbr;
			$this->event_prefix = $event_prefix;
			$this->ajax_action  = $this->plugin_abbr . '_submit-feedback';
			$this->is_dev_mode  = $is_dev_mode;

			// Don't run deactivation survey on dev sites.
			if ( ! $this->can_show_feedback_widget() ) {
				return;
			}

			add_action( 'wp_ajax_' . $this->ajax_action, array( $this, 'submit_feedback' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		public function render_deactivate_feedback() {
			add_action( 'admin_print_scripts', array( $this, 'js' ), 20 );
			add_action( 'admin_print_scripts', array( $this, 'css' ) );
			add_action( 'admin_footer', array( $this, 'modal' ) );
		}

		/**
		 * Load Javascripts
		 *
		 * @since 1.0.1
		 */
		public function enqueue_scripts() {
			wp_enqueue_script( 'sweetalert', plugin_dir_url( __FILE__ ) . 'assets/js/sweetalert2.js', array( 'jquery' ) );
		}

		/**
		 * Load Styles
		 *
		 * @since 1.0.1
		 */
		public function enqueue_styles() {
			wp_register_style( 'sweetalert', plugin_dir_url( __FILE__ ) . 'assets/css/sweetalert2.css' );
			wp_enqueue_style( 'sweetalert' );

			wp_register_style( 'animate', plugin_dir_url( __FILE__ ) . 'assets/css/animate.min.css' );
			wp_enqueue_style( 'animate' );

			wp_register_style( 'ig-feedback-star-rating', plugin_dir_url( __FILE__ ) . 'assets/css/star-rating.css' );
			wp_enqueue_style( 'ig-feedback-star-rating' );

			wp_register_style( 'ig-feedback-emoji', plugin_dir_url( __FILE__ ) . 'assets/css/emoji.css' );
			wp_enqueue_style( 'ig-feedback-emoji' );
		}

		public function render_widget( $params = array() ) {

			$default_params = array(
				'event'             => 'feedback',
				'title'             => 'How do you rate ' . $this->plugin,
				'position'          => 'top-end',
				'width'             => 300,
				'set_transient'     => true,
				'allowOutsideClick' => false,
				'allowEscapeKey'    => true,
				'showCloseButton'   => true,
				'confirmButtonText' => __( 'Ok', 'email-subscribers' ),
				'backdrop'          => true,
				'delay'             => 3 // In Seconds
			);

			$params = wp_parse_args( $params, $default_params );

			$title = $params['title'];
			$slug  = sanitize_title( $title );
			$event = $this->event_prefix . $params['event'];
			$html  = ! empty( $params['html'] ) ? $params['html'] : '';

			?>

            <script>

				function doSend(rating, details) {

					var data = {
						action: '<?php echo $this->ajax_action; ?>',
						feedback: {
							type: '<?php echo $params['type']; ?>',
							slug: '<?php echo $slug; ?>',
							title: '<?php echo esc_js( $title ); ?>',
							value: rating,
							details: details
						},

						event: '<?php echo $event; ?>',

						// Add additional information
						misc: {
							plugin: '<?php echo $this->plugin; ?>',
							plugin_abbr: '<?php echo $this->plugin_abbr; ?>',
							is_dev_mode: '<?php echo $this->is_dev_mode; ?>',
							set_transient: '<?php echo $params['set_transient']; ?>'
							//system_info: enable_system_info
						}
					};

					return jQuery.post(ajaxurl, data);
				}

				function showWidget(delay) {
					setTimeout(function () {

						Swal.mixin({
							footer: '<?php echo $this->footer; ?>',
							position: '<?php echo $params['position']; ?>',
							width: <?php echo $params['width']; ?>,
							animation: false,
							focusConfirm: false,
							allowEscapeKey: '<?php echo $params['allowEscapeKey']; ?>',
							showCloseButton: '<?php echo $params['showCloseButton']; ?>',
							allowOutsideClick: '<?php echo $params['allowOutsideClick']; ?>',
							showLoaderOnConfirm: true,
							confirmButtonText: '<?php echo $params['confirmButtonText']; ?>',
							backdrop: '<?php echo (int) $params['backdrop']; ?>'
						}).queue([
							{
								title: '<p class="ig-feedback-title"><?php echo esc_js( $params['title'] ); ?></p>',
								html: '<?php echo $html; ?>',
								customClass: {
									popup: 'animated fadeInUpBig'
								},
								onOpen: () => {
									var clicked = false;
									var selectedReaction = '';
									jQuery('.ig-emoji').hover(function () {
										reaction = jQuery(this).attr('data-reaction');
										jQuery('#emoji-info').text(reaction);
									}, function () {
										if (!clicked) {
											jQuery('#emoji-info').text('');
										} else {
											jQuery('#emoji-info').text(selectedReaction);
										}
									});

									jQuery('.ig-emoji').on('click', function () {
										clicked = true;
										jQuery('.ig-emoji').removeClass('active');
										jQuery(this).addClass('active');
										selectedReaction = jQuery(this).attr('data-reaction');
										jQuery('#emoji-info').text(reaction);
									});
								},
								preConfirm: () => {

									var rating = jQuery("input[name='rating']:checked").val();
									var details = '';

									if (rating === undefined) {
										Swal.showValidationMessage('Please give your input');
										return;
									}

									return doSend(rating, details);
								}
							},

						]).then(response => {

							if (response.hasOwnProperty('value')) {

								Swal.fire({
									type: 'success',
									width: <?php echo $params['width']; ?>,
									title: "Thank You!",
									showConfirmButton: false,
									position: '<?php echo $params['position']; ?>',
									timer: 1500,
									animation: false
								});

							}
						});

					}, delay * 1000);
				}

				var delay = <?php echo $params['delay']; ?>;
				showWidget(delay);


            </script>
			<?php
		}

		/**
		 * Render star feedback widget
		 *
		 * @param array $params
		 *
		 * @since 1.0.1
		 */
		public function render_stars( $params = array() ) {

			ob_start();

			?>

            <div class="rating">
                <!--elements are in reversed order, to allow "previous sibling selectors" in CSS-->
                <input class="ratings" type="radio" name="rating" value="5" id="5"><label for="5">☆</label>
                <input class="ratings" type="radio" name="rating" value="4" id="4"><label for="4">☆</label>
                <input class="ratings" type="radio" name="rating" value="3" id="3"><label for="3">☆</label>
                <input class="ratings" type="radio" name="rating" value="2" id="2"><label for="2">☆</label>
                <input class="ratings" type="radio" name="rating" value="1" id="1"><label for="1">☆</label>
            </div>

			<?php

			$html = str_replace( array( "\r", "\n" ), '', trim( ob_get_clean() ) );

			$params['html'] = $html;

			$this->render_widget( $params );
		}

		/**
		 * Render Emoji Widget
		 *
		 * @param array $params
		 *
		 * @since 1.0.1
		 */
		public function render_emoji( $params = array() ) {

			ob_start();

			?>

            <div class="emoji">
                <!--elements are in reversed order, to allow "previous sibling selectors" in CSS-->
                <input class="emojis" type="radio" name="rating" value="love" id="5"/><label for="5" class="ig-emoji" data-reaction="Love">😍</label>
                <input class="emojis" type="radio" name="rating" value="smile" id="4"/><label for="4" class="ig-emoji" data-reaction="Smile">😊</label>
                <input class="emojis" type="radio" name="rating" value="neutral" id="3"/><label for="3" class="ig-emoji" data-reaction="Neutral">😐</label>
                <input class="emojis" type="radio" name="rating" value="sad" id="1"/><label for="2" class="ig-emoji" data-reaction="Sad">😠</label>
                <input class="emojis" type="radio" name="rating" value="angry" id="1"/><label for="1" class="ig-emoji" data-reaction="Angry">😡</label>
            </div>
            <div id="emoji-info"></div>

			<?php

			$html = str_replace( array( "\r", "\n" ), '', trim( ob_get_clean() ) );

			$params['html'] = $html;

			$this->render_widget( $params );

		}

		/**
		 * Get Feedback API url
		 *
		 * @param $is_dev_mode
		 *
		 * @return string
		 *
		 * @since 1.0.1
		 */
		public function get_api_url( $is_dev_mode ) {

			if ( $is_dev_mode ) {
				$this->api_url = 'http://192.168.0.130:9094/store/feedback/';
			}

			return $this->api_url;
		}

		/**
		 * Deactivation Survey javascript.
		 *
		 * @since 1.0.0
		 */
		public function js() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}

			$title = 'Why are you deactivating Email Subscribers?';
			$slug  = sanitize_title( $title );
			$event = $this->event_prefix . 'plugin.deactivation';

			?>
            <script type="text/javascript">
				jQuery(function ($) {
					var $deactivateLink = $('#the-list').find('[data-slug="<?php echo $this->plugin; ?>"] span.deactivate a'),
						$overlay = $('#ig-deactivate-survey-<?php echo $this->plugin; ?>'),
						$form = $overlay.find('form'),
						formOpen = false;
					// Plugin listing table deactivate link.
					$deactivateLink.on('click', function (event) {
						event.preventDefault();
						$overlay.css('display', 'table');
						formOpen = true;
						$form.find('.ig-deactivate-survey-option:first-of-type input[type=radio]').focus();
					});
					// Survey radio option selected.
					$form.on('change', 'input[type=radio]', function (event) {
						event.preventDefault();
						$form.find('input[type=text], .error').hide();
						$form.find('.ig-deactivate-survey-option').removeClass('selected');
						$(this).closest('.ig-deactivate-survey-option').addClass('selected').find('input[type=text]').show();
					});
					// Survey Skip & Deactivate.
					$form.on('click', '.ig-deactivate-survey-deactivate', function (event) {
						event.preventDefault();
						location.href = $deactivateLink.attr('href');
					});
					// Survey submit.
					$form.submit(function (event) {
						event.preventDefault();
						if (!$form.find('input[type=radio]:checked').val()) {
							$form.find('.ig-deactivate-survey-footer').prepend('<span class="error"><?php echo esc_js( __( 'Please select an option', 'email-subscribers' ) ); ?></span>');
							return;
						}

						var data = {
							action: '<?php echo $this->ajax_action; ?>',
							feedback: {
								type: 'radio',
								title: '<?php echo $title; ?>',
								slug: '<?php echo $slug; ?>',
								value: $form.find('.selected input[type=radio]').attr('data-option-slug'),
								details: $form.find('.selected input[type=text]').val()
							},

							event: '<?php echo $event; ?>',

							// Add additional information
							misc: {
								plugin: '<?php echo $this->plugin; ?>',
								plugin_abbr: '<?php echo $this->plugin_abbr; ?>',
								is_dev_mode: '<?php echo $this->is_dev_mode; ?>',
								set_cookie: ''
							}
						};

						var submitSurvey = $.post(ajaxurl, data);
						submitSurvey.always(function () {
							location.href = $deactivateLink.attr('href');
						});
					});
					// Exit key closes survey when open.
					$(document).keyup(function (event) {
						if (27 === event.keyCode && formOpen) {
							$overlay.hide();
							formOpen = false;
							$deactivateLink.focus();
						}
					});
				});
            </script>
			<?php
		}

		/**
		 * Survey CSS.
		 *
		 * @since 1.0.0
		 */
		public function css() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}
			?>
            <style type="text/css">
                .ig-deactivate-survey-modal {
                    display: none;
                    table-layout: fixed;
                    position: fixed;
                    z-index: 9999;
                    width: 100%;
                    height: 100%;
                    text-align: center;
                    font-size: 14px;
                    top: 0;
                    left: 0;
                    background: rgba(0, 0, 0, 0.8);
                }

                .ig-deactivate-survey-wrap {
                    display: table-cell;
                    vertical-align: middle;
                }

                .ig-deactivate-survey {
                    background-color: #fff;
                    max-width: 550px;
                    margin: 0 auto;
                    padding: 30px;
                    text-align: left;
                }

                .ig-deactivate-survey .error {
                    display: block;
                    color: red;
                    margin: 0 0 10px 0;
                }

                .ig-deactivate-survey-title {
                    display: block;
                    font-size: 18px;
                    font-weight: 700;
                    text-transform: uppercase;
                    border-bottom: 1px solid #ddd;
                    padding: 0 0 18px 0;
                    margin: 0 0 18px 0;
                }

                .ig-deactivate-survey-title span {
                    color: #999;
                    margin-right: 10px;
                }

                .ig-deactivate-survey-desc {
                    display: block;
                    font-weight: 600;
                    margin: 0 0 18px 0;
                }

                .ig-deactivate-survey-option {
                    margin: 0 0 10px 0;
                }

                .ig-deactivate-survey-option-input {
                    margin-right: 10px !important;
                }

                .ig-deactivate-survey-option-details {
                    display: none;
                    width: 90%;
                    margin: 10px 0 0 30px;
                }

                .ig-deactivate-survey-footer {
                    margin-top: 18px;
                }

                .ig-deactivate-survey-deactivate {
                    float: right;
                    font-size: 13px;
                    color: #ccc;
                    text-decoration: none;
                    padding-top: 7px;
                }
            </style>
			<?php
		}

		/**
		 * Survey modal.
		 *
		 * @since 1.0.0
		 */
		public function modal() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}

			$options = array(
				1 => array(
					'title' => esc_html__( 'I no longer need the plugin', 'email-subscribers' ),
					'slug'  => 'i-no-longer-need-the-plugin'
				),
				2 => array(
					'title'   => esc_html__( 'I\'m switching to a different plugin', 'email-subscribers' ),
					'slug'    => 'i-am-switching-to-a-different-plugin',
					'details' => esc_html__( 'Please share which plugin', 'email-subscribers' ),
				),
				3 => array(
					'title' => esc_html__( 'I couldn\'t get the plugin to work', 'email-subscribers' ),
					'slug'  => 'i-could-not-get-the-plugin-to-work'
				),
				4 => array(
					'title' => esc_html__( 'It\'s a temporary deactivation', 'email-subscribers' ),
					'slug'  => 'it-is-a-temporary-deactivation'
				),
				5 => array(
					'title'   => esc_html__( 'Other', 'email-subscribers' ),
					'slug'    => 'other',
					'details' => esc_html__( 'Please share the reason', 'email-subscribers' ),
				),
			);
			?>
            <div class="ig-deactivate-survey-modal" id="ig-deactivate-survey-<?php echo $this->plugin; ?>">
                <div class="ig-deactivate-survey-wrap">
                    <form class="ig-deactivate-survey" method="post">
                        <span class="ig-deactivate-survey-title"><span class="dashicons dashicons-testimonial"></span><?php echo ' ' . esc_html__( 'Quick Feedback', 'email-subscribers' ); ?></span>
                        <span class="ig-deactivate-survey-desc"><?php echo sprintf( esc_html__( 'If you have a moment, please share why you are deactivating %s:', 'email-subscribers' ), $this->name ); ?></span>
                        <div class="ig-deactivate-survey-options">
							<?php foreach ( $options as $id => $option ) : ?>
                                <div class="ig-deactivate-survey-option">
                                    <label for="ig-deactivate-survey-option-<?php echo $this->plugin; ?>-<?php echo $id; ?>" class="ig-deactivate-survey-option-label">
                                        <input id="ig-deactivate-survey-option-<?php echo $this->plugin; ?>-<?php echo $id; ?>" class="ig-deactivate-survey-option-input" type="radio" name="code" value="<?php echo $id; ?>" data-option-slug="<?php echo $option['slug']; ?>"/>
                                        <span class="ig-deactivate-survey-option-reason"><?php echo $option['title']; ?></span>
                                    </label>
									<?php if ( ! empty( $option['details'] ) ) : ?>
                                        <input class="ig-deactivate-survey-option-details" type="text" placeholder="<?php echo $option['details']; ?>"/>
									<?php endif; ?>
                                </div>
							<?php endforeach; ?>
                        </div>
                        <div class="ig-deactivate-survey-footer">
                            <button type="submit" class="ig-deactivate-survey-submit button button-primary button-large"><?php echo sprintf( esc_html__( 'Submit %s Deactivate', 'email-subscribers' ), '&amp;' ); ?></button>
                            <a href="#" class="ig-deactivate-survey-deactivate"><?php echo sprintf( esc_html__( 'Skip %s Deactivate', 'email-subscribers' ), '&amp;' ); ?></a>
                        </div>
                    </form>
                </div>
            </div>
			<?php
		}

		/**
		 * Can we show feedback widget in this environment
		 *
		 * @return bool
		 */
		public function can_show_feedback_widget() {

			// Is development mode? Enable it.
			if ( $this->is_dev_mode ) {
				return true;
			}

			// Don't show on dev setup if dev mode is off.
			if ( $this->is_dev_url() ) {
				return false;
			}

			return true;
		}

		/**
		 * Checks if current admin screen is the plugins page.
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public function is_plugin_page() {

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
			if ( empty( $screen ) ) {
				return false;
			}

			return ( ! empty( $screen->id ) && in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) );
		}


		/**
		 * Checks if current site is a development one.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public function is_dev_url() {

			$url          = network_site_url( '/' );
			$is_local_url = false;

			// Trim it up
			$url = strtolower( trim( $url ) );

			// Need to get the host...so let's add the scheme so we can use parse_url
			if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
				$url = 'http://' . $url;
			}
			$url_parts = parse_url( $url );
			$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;
			if ( ! empty( $url ) && ! empty( $host ) ) {
				if ( false !== ip2long( $host ) ) {
					if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
						$is_local_url = true;
					}
				} elseif ( 'localhost' === $host ) {
					$is_local_url = true;
				}

				$tlds_to_check = array( '.dev', '.local', ':8888' );
				foreach ( $tlds_to_check as $tld ) {
					if ( false !== strpos( $host, $tld ) ) {
						$is_local_url = true;
						continue;
					}

				}
				if ( substr_count( $host, '.' ) > 1 ) {
					$subdomains_to_check = array( 'dev.', '*.staging.', 'beta.', 'test.' );
					foreach ( $subdomains_to_check as $subdomain ) {
						$subdomain = str_replace( '.', '(.)', $subdomain );
						$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );
						if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
							$is_local_url = true;
							continue;
						}
					}
				}
			}

			return $is_local_url;
		}

		/**
		 * Store plugin feedback data into option
		 *
		 * @param $plugin_abbr
		 * @param $event
		 * @param $data
		 *
		 * @since 1.0.1
		 */
		public function set_feedback_data( $plugin_abbr, $event, $data ) {

			$feedback_option = $plugin_abbr . '_feedback_data';

			$feedback_data = maybe_unserialize( get_option( $feedback_option, array() ) );

			$data['created_on'] = gmdate( 'Y-m-d H:i:s' );

			$feedback_data[ $event ][] = $data;

			update_option( $feedback_option, $feedback_data );

		}

		/**
		 * Get plugin feedback data
		 *
		 * @param $plugin_abbr
		 *
		 * @return mixed|void
		 *
		 * @since 1.0.1
		 */
		public function get_feedback_data( $plugin_abbr ) {

			$feedback_option = $plugin_abbr . '_feedback_data';

			return get_option( $feedback_option, array() );
		}

		/**
		 * Get event specific feedback data
		 *
		 * @param $plugin_abbr
		 * @param $event
		 *
		 * @return array|mixed
		 */
		public function get_event_feedback_data( $plugin_abbr, $event ) {

			$feedback_data = $this->get_feedback_data( $plugin_abbr );

			$event_feedback_data = ! empty( $feedback_data[ $event ] ) ? $feedback_data[ $event ] : array();

			return $event_feedback_data;
		}

		/**
		 * Set event into transient
		 *
		 * @param $event
		 * @param int $expiry in days
		 */
		public function set_event_transient( $event, $expiry = 45 ) {
			set_transient( $event, 1, time() + ( 86400 * $expiry ) );
		}

		/**
		 * Check whether event transient is set or not.
		 *
		 * @param $event
		 *
		 * @return bool
		 *
		 * @since 1.0.1
		 */
		public function is_event_transient_set( $event ) {
			return get_transient( $event );
		}

		/**
		 * Hook to ajax_action
		 *
		 * Send feedback to server
		 */
		function submit_feedback() {

			$data = ! empty( $_POST ) ? $_POST : array();

			$data['site'] = esc_url( home_url() );

			$plugin        = ! empty( $data['misc']['plugin'] ) ? $data['misc']['plugin'] : 'ig_feedback';
			$plugin_abbr   = ! empty( $data['misc']['plugin_abbr'] ) ? $data['misc']['plugin_abbr'] : 'ig_feedback';
			$is_dev_mode   = ! empty( $data['misc']['is_dev_mode'] ) ? $data['misc']['is_dev_mode'] : false;
			$set_transient = ! empty( $data['misc']['set_transient'] ) ? $data['misc']['set_transient'] : false;
			$system_info   = ! empty( $data['misc']['system_info'] ) ? $data['misc']['system_info'] : false;

			unset( $data['misc'] );

			$meta_info = array(
				'plugin'     => sanitize_key( $plugin ),
				'locale'     => get_locale(),
				'wp_version' => get_bloginfo( 'version' )
			);

			$additional_info = array();
			$additional_info = apply_filters( $plugin_abbr . '_additional_feedback_meta_info', $additional_info, $system_info ); // Get Additional meta information

			if ( is_array( $additional_info ) && count( $additional_info ) > 0 ) {
				$meta_info = $meta_info + $additional_info;
			}

			$data['meta'] = $meta_info;

			$data = wp_unslash( $data );

			$args = array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $data,
				'blocking'  => false
			);

			$this->set_feedback_data( $plugin_abbr, $data['event'], $data['feedback'] );

			// Set Cookie
			if ( $set_transient ) {
				$this->set_event_transient( $data['event'] );
			}

			$response = wp_remote_post( $this->get_api_url( $is_dev_mode ), $args );

			$result['status'] = 'success';
			if ( $response instanceof WP_Error ) {
				$error_message     = $response->get_error_message();
				$result['status']  = 'error';
				$result['message'] = $error_message;
			}

			die( json_encode( $result ) );
		}
	}
} // End if().