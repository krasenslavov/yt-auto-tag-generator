<?php
/**
 * Plugin Name: YT Auto Tag Generator
 * Plugin URI: https://github.com/krasenslavov/yt-auto-tag-generator
 * Description: Automatically suggests and adds tags based on post content analysis. Extracts frequent keywords and provides preview before saving.
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Krasen Slavov
 * Author URI: https://krasenslavov.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yt-auto-tag-generator
 * Domain Path: /languages
 *
 * @package YT_Auto_Tag_Generator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'YT_ATG_VERSION', '1.0.0' );

/**
 * Plugin base name.
 */
define( 'YT_ATG_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin directory path.
 */
define( 'YT_ATG_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'YT_ATG_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class for Auto Tag Generator.
 *
 * @since 1.0.0
 */
class YT_Auto_Tag_Generator {

	/**
	 * Single instance of the class.
	 *
	 * @var YT_Auto_Tag_Generator|null
	 */
	private static $instance = null;

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Common stop words to exclude.
	 *
	 * @var array
	 */
	private $stop_words = array();

	/**
	 * Get single instance of the class.
	 *
	 * @return YT_Auto_Tag_Generator
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->options    = get_option( 'yt_atg_options', $this->get_default_options() );
		$this->stop_words = $this->get_stop_words();
		$this->init_hooks();
	}

	/**
	 * Get default plugin options.
	 *
	 * @return array
	 */
	private function get_default_options() {
		return array(
			'auto_generate'      => false,
			'require_preview'    => true,
			'max_tags'           => 5,
			'min_word_length'    => 4,
			'min_word_frequency' => 2,
			'post_types'         => array( 'post' ),
			'analyze_title'      => true,
			'analyze_content'    => true,
			'analyze_excerpt'    => false,
			'case_sensitive'     => false,
			'append_tags'        => true,
		);
	}

	/**
	 * Get stop words (common words to exclude).
	 *
	 * @return array
	 */
	private function get_stop_words() {
		return array(
			'the',
			'be',
			'to',
			'of',
			'and',
			'a',
			'in',
			'that',
			'have',
			'i',
			'it',
			'for',
			'not',
			'on',
			'with',
			'he',
			'as',
			'you',
			'do',
			'at',
			'this',
			'but',
			'his',
			'by',
			'from',
			'they',
			'we',
			'say',
			'her',
			'she',
			'or',
			'an',
			'will',
			'my',
			'one',
			'all',
			'would',
			'there',
			'their',
			'what',
			'so',
			'up',
			'out',
			'if',
			'about',
			'who',
			'get',
			'which',
			'go',
			'me',
			'when',
			'make',
			'can',
			'like',
			'time',
			'no',
			'just',
			'him',
			'know',
			'take',
			'people',
			'into',
			'year',
			'your',
			'good',
			'some',
			'could',
			'them',
			'see',
			'other',
			'than',
			'then',
			'now',
			'look',
			'only',
			'come',
			'its',
			'over',
			'think',
			'also',
			'back',
			'after',
			'use',
			'two',
			'how',
			'our',
			'work',
			'first',
			'well',
			'way',
			'even',
			'new',
			'want',
			'because',
			'any',
			'these',
			'give',
			'day',
			'most',
			'us',
		);
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Load plugin text domain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_filter( 'plugin_action_links_' . YT_ATG_BASENAME, array( $this, 'add_action_links' ) );

			// Meta box for tag preview.
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

			// Save post hook.
			add_action( 'save_post', array( $this, 'auto_generate_tags' ), 10, 3 );

			// AJAX handlers.
			add_action( 'wp_ajax_yt_atg_generate_preview', array( $this, 'ajax_generate_preview' ) );
			add_action( 'wp_ajax_yt_atg_apply_tags', array( $this, 'ajax_apply_tags' ) );
		}
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'yt-auto-tag-generator',
			false,
			dirname( YT_ATG_BASENAME ) . '/languages'
		);
	}

	/**
	 * Add plugin admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Auto Tag Generator Settings', 'yt-auto-tag-generator' ),
			__( 'Auto Tag Generator', 'yt-auto-tag-generator' ),
			'manage_options',
			'yt-auto-tag-generator',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'yt_atg_options_group',
			'yt_atg_options',
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'yt_atg_main_section',
			__( 'Generation Settings', 'yt-auto-tag-generator' ),
			array( $this, 'render_section_info' ),
			'yt-auto-tag-generator'
		);

		add_settings_field(
			'auto_generate',
			__( 'Auto Generate', 'yt-auto-tag-generator' ),
			array( $this, 'render_auto_generate_field' ),
			'yt-auto-tag-generator',
			'yt_atg_main_section'
		);

		add_settings_field(
			'max_tags',
			__( 'Maximum Tags', 'yt-auto-tag-generator' ),
			array( $this, 'render_max_tags_field' ),
			'yt-auto-tag-generator',
			'yt_atg_main_section'
		);

		add_settings_field(
			'min_word_length',
			__( 'Minimum Word Length', 'yt-auto-tag-generator' ),
			array( $this, 'render_min_word_length_field' ),
			'yt-auto-tag-generator',
			'yt_atg_main_section'
		);

		add_settings_field(
			'content_sources',
			__( 'Analyze Content From', 'yt-auto-tag-generator' ),
			array( $this, 'render_content_sources_field' ),
			'yt-auto-tag-generator',
			'yt_atg_main_section'
		);

		add_settings_field(
			'post_types',
			__( 'Post Types', 'yt-auto-tag-generator' ),
			array( $this, 'render_post_types_field' ),
			'yt-auto-tag-generator',
			'yt_atg_main_section'
		);
	}

	/**
	 * Sanitize plugin options.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_options( $input ) {
		$sanitized = array();

		$sanitized['auto_generate']   = isset( $input['auto_generate'] ) ? (bool) $input['auto_generate'] : false;
		$sanitized['require_preview'] = isset( $input['require_preview'] ) ? (bool) $input['require_preview'] : true;

		$sanitized['max_tags'] = isset( $input['max_tags'] )
			? absint( $input['max_tags'] )
			: 5;
		$sanitized['max_tags'] = max( 1, min( 20, $sanitized['max_tags'] ) );

		$sanitized['min_word_length'] = isset( $input['min_word_length'] )
			? absint( $input['min_word_length'] )
			: 4;
		$sanitized['min_word_length'] = max( 2, min( 10, $sanitized['min_word_length'] ) );

		$sanitized['min_word_frequency'] = isset( $input['min_word_frequency'] )
			? absint( $input['min_word_frequency'] )
			: 2;

		$sanitized['analyze_title']   = isset( $input['analyze_title'] ) ? (bool) $input['analyze_title'] : true;
		$sanitized['analyze_content'] = isset( $input['analyze_content'] ) ? (bool) $input['analyze_content'] : true;
		$sanitized['analyze_excerpt'] = isset( $input['analyze_excerpt'] ) ? (bool) $input['analyze_excerpt'] : false;

		$sanitized['case_sensitive'] = isset( $input['case_sensitive'] ) ? (bool) $input['case_sensitive'] : false;
		$sanitized['append_tags']    = isset( $input['append_tags'] ) ? (bool) $input['append_tags'] : true;

		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			$sanitized['post_types'] = array_map( 'sanitize_key', $input['post_types'] );
		} else {
			$sanitized['post_types'] = array( 'post' );
		}

		return $sanitized;
	}

	/**
	 * Render settings section information.
	 *
	 * @return void
	 */
	public function render_section_info() {
		echo '<p>' . esc_html__( 'Configure how tags are automatically generated from post content.', 'yt-auto-tag-generator' ) . '</p>';
	}

	/**
	 * Render auto generate field.
	 *
	 * @return void
	 */
	public function render_auto_generate_field() {
		$auto_generate   = isset( $this->options['auto_generate'] ) ? $this->options['auto_generate'] : false;
		$require_preview = isset( $this->options['require_preview'] ) ? $this->options['require_preview'] : true;
		?>
		<label style="display: block; margin-bottom: 10px;">
			<input type="checkbox"
				name="yt_atg_options[auto_generate]"
				value="1"
				<?php checked( $auto_generate, true ); ?> />
			<?php esc_html_e( 'Automatically generate tags when saving posts', 'yt-auto-tag-generator' ); ?>
		</label>
		<label style="display: block;">
			<input type="checkbox"
				name="yt_atg_options[require_preview]"
				value="1"
				<?php checked( $require_preview, true ); ?> />
			<?php esc_html_e( 'Show preview before applying (recommended)', 'yt-auto-tag-generator' ); ?>
		</label>
		<?php
	}

	/**
	 * Render max tags field.
	 *
	 * @return void
	 */
	public function render_max_tags_field() {
		$value = isset( $this->options['max_tags'] ) ? $this->options['max_tags'] : 5;
		?>
		<input type="number"
			name="yt_atg_options[max_tags]"
			value="<?php echo esc_attr( $value ); ?>"
			min="1"
			max="20"
			class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Maximum number of tags to generate (1-20).', 'yt-auto-tag-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render minimum word length field.
	 *
	 * @return void
	 */
	public function render_min_word_length_field() {
		$word_length = isset( $this->options['min_word_length'] ) ? $this->options['min_word_length'] : 4;
		$frequency   = isset( $this->options['min_word_frequency'] ) ? $this->options['min_word_frequency'] : 2;
		?>
		<label style="display: block; margin-bottom: 10px;">
			<?php esc_html_e( 'Minimum word length:', 'yt-auto-tag-generator' ); ?>
			<input type="number"
				name="yt_atg_options[min_word_length]"
				value="<?php echo esc_attr( $word_length ); ?>"
				min="2"
				max="10"
				class="small-text" />
		</label>
		<label style="display: block;">
			<?php esc_html_e( 'Minimum word frequency:', 'yt-auto-tag-generator' ); ?>
			<input type="number"
				name="yt_atg_options[min_word_frequency]"
				value="<?php echo esc_attr( $frequency ); ?>"
				min="1"
				max="10"
				class="small-text" />
		</label>
		<p class="description">
			<?php esc_html_e( 'Words must be this long and appear this many times to be considered.', 'yt-auto-tag-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render content sources field.
	 *
	 * @return void
	 */
	public function render_content_sources_field() {
		$title   = isset( $this->options['analyze_title'] ) ? $this->options['analyze_title'] : true;
		$content = isset( $this->options['analyze_content'] ) ? $this->options['analyze_content'] : true;
		$excerpt = isset( $this->options['analyze_excerpt'] ) ? $this->options['analyze_excerpt'] : false;
		?>
		<label style="display: block; margin-bottom: 5px;">
			<input type="checkbox"
				name="yt_atg_options[analyze_title]"
				value="1"
				<?php checked( $title, true ); ?> />
			<?php esc_html_e( 'Post title (weight: 3x)', 'yt-auto-tag-generator' ); ?>
		</label>
		<label style="display: block; margin-bottom: 5px;">
			<input type="checkbox"
				name="yt_atg_options[analyze_content]"
				value="1"
				<?php checked( $content, true ); ?> />
			<?php esc_html_e( 'Post content (weight: 1x)', 'yt-auto-tag-generator' ); ?>
		</label>
		<label style="display: block;">
			<input type="checkbox"
				name="yt_atg_options[analyze_excerpt]"
				value="1"
				<?php checked( $excerpt, true ); ?> />
			<?php esc_html_e( 'Post excerpt (weight: 2x)', 'yt-auto-tag-generator' ); ?>
		</label>
		<?php
	}

	/**
	 * Render post types field.
	 *
	 * @return void
	 */
	public function render_post_types_field() {
		$selected   = isset( $this->options['post_types'] ) ? $this->options['post_types'] : array( 'post' );
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $post_types as $post_type ) {
			$checked = in_array( $post_type->name, $selected, true );
			?>
			<label style="display: block; margin-bottom: 5px;">
				<input type="checkbox"
					name="yt_atg_options[post_types][]"
					value="<?php echo esc_attr( $post_type->name ); ?>"
					<?php checked( $checked, true ); ?> />
				<?php echo esc_html( $post_type->label ); ?>
			</label>
			<?php
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Select which post types should have auto-generated tags.', 'yt-auto-tag-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'settings_page_yt-auto-tag-generator' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'yt-atg-admin',
			YT_ATG_URL . 'assets/css/yt-auto-tag-generator.css',
			array(),
			YT_ATG_VERSION
		);

		wp_enqueue_script(
			'yt-atg-admin',
			YT_ATG_URL . 'assets/js/yt-auto-tag-generator.js',
			array( 'jquery' ),
			YT_ATG_VERSION,
			true
		);

		wp_localize_script(
			'yt-atg-admin',
			'ytAtgData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'yt_atg_nonce' ),
				'strings' => array(
					'generating' => __( 'Generating tags...', 'yt-auto-tag-generator' ),
					'applying'   => __( 'Applying tags...', 'yt-auto-tag-generator' ),
					'error'      => __( 'An error occurred. Please try again.', 'yt-auto-tag-generator' ),
					'noTags'     => __( 'No suitable tags found. Try writing more content.', 'yt-auto-tag-generator' ),
				),
			)
		);
	}

	/**
	 * Add meta box for tag preview.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		$post_types = $this->options['post_types'];

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'yt_atg_meta_box',
				__( 'Auto Tag Generator', 'yt-auto-tag-generator' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box content.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'yt_atg_meta_box', 'yt_atg_meta_box_nonce' );
		?>
		<div class="yt-atg-meta-box">
			<p class="yt-atg-description">
				<?php esc_html_e( 'Generate tags automatically based on your post content.', 'yt-auto-tag-generator' ); ?>
			</p>

			<button type="button" id="yt-atg-generate" class="button button-primary button-large">
				<?php esc_html_e( 'Generate Tags', 'yt-auto-tag-generator' ); ?>
			</button>

			<div id="yt-atg-preview" class="yt-atg-preview" style="display: none;">
				<h4><?php esc_html_e( 'Suggested Tags:', 'yt-auto-tag-generator' ); ?></h4>
				<div id="yt-atg-tags-list" class="yt-atg-tags-list"></div>

				<div class="yt-atg-actions">
					<button type="button" id="yt-atg-apply" class="button button-secondary">
						<?php esc_html_e( 'Apply Tags', 'yt-auto-tag-generator' ); ?>
					</button>
					<button type="button" id="yt-atg-cancel" class="button">
						<?php esc_html_e( 'Cancel', 'yt-auto-tag-generator' ); ?>
					</button>
				</div>
			</div>

			<div id="yt-atg-message" class="yt-atg-message"></div>
		</div>
		<?php
	}

	/**
	 * Auto-generate tags when saving post.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 * @return void
	 */
	public function auto_generate_tags( $post_id, $post, $update ) {
		// Skip if auto-generate is disabled.
		if ( ! $this->options['auto_generate'] ) {
			return;
		}

		// Skip if preview is required.
		if ( $this->options['require_preview'] ) {
			return;
		}

		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check post type.
		if ( ! in_array( $post->post_type, $this->options['post_types'], true ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Generate and apply tags.
		$tags = $this->generate_tags( $post_id );

		if ( ! empty( $tags ) ) {
			$this->apply_tags_to_post( $post_id, $tags );
		}
	}

	/**
	 * Generate tags from post content.
	 *
	 * @param int $post_id Post ID.
	 * @return array Generated tags.
	 */
	public function generate_tags( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array();
		}

		$text = '';

		// Analyze title (with higher weight).
		if ( $this->options['analyze_title'] ) {
			$text .= str_repeat( ' ' . $post->post_title, 3 );
		}

		// Analyze content.
		if ( $this->options['analyze_content'] ) {
			$text .= ' ' . wp_strip_all_tags( $post->post_content );
		}

		// Analyze excerpt (with medium weight).
		if ( $this->options['analyze_excerpt'] && ! empty( $post->post_excerpt ) ) {
			$text .= str_repeat( ' ' . $post->post_excerpt, 2 );
		}

		// Extract keywords.
		$keywords = $this->extract_keywords( $text );

		// Get top N keywords.
		$tags = array_slice( $keywords, 0, $this->options['max_tags'] );

		return array_keys( $tags );
	}

	/**
	 * Extract keywords from text.
	 *
	 * @param string $text Text to analyze.
	 * @return array Keywords with frequency counts.
	 */
	private function extract_keywords( $text ) {
		// Remove HTML tags and normalize whitespace.
		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '/\s+/', ' ', $text );

		// Convert to lowercase if not case-sensitive.
		if ( ! $this->options['case_sensitive'] ) {
			$text = strtolower( $text );
		}

		// Split into words.
		$words = preg_split( '/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

		// Count word frequency.
		$word_freq = array();

		foreach ( $words as $word ) {
			// Skip short words.
			if ( mb_strlen( $word ) < $this->options['min_word_length'] ) {
				continue;
			}

			// Skip stop words.
			if ( in_array( strtolower( $word ), $this->stop_words, true ) ) {
				continue;
			}

			// Skip numbers.
			if ( is_numeric( $word ) ) {
				continue;
			}

			// Count frequency.
			if ( ! isset( $word_freq[ $word ] ) ) {
				$word_freq[ $word ] = 0;
			}
			$word_freq[ $word ]++;
		}

		// Filter by minimum frequency.
		$word_freq = array_filter(
			$word_freq,
			function( $count ) {
				return $count >= $this->options['min_word_frequency'];
			}
		);

		// Sort by frequency (descending).
		arsort( $word_freq );

		return $word_freq;
	}

	/**
	 * Apply tags to post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $tags    Tags to apply.
	 * @return void
	 */
	private function apply_tags_to_post( $post_id, $tags ) {
		if ( $this->options['append_tags'] ) {
			// Append to existing tags.
			$existing_tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
			$tags          = array_unique( array_merge( $existing_tags, $tags ) );
		}

		wp_set_post_tags( $post_id, $tags, false );
	}

	/**
	 * AJAX handler to generate tag preview.
	 *
	 * @return void
	 */
	public function ajax_generate_preview() {
		check_ajax_referer( 'yt_atg_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'yt-auto-tag-generator' ) ) );
		}

		$tags = $this->generate_tags( $post_id );

		if ( empty( $tags ) ) {
			wp_send_json_error( array( 'message' => __( 'No suitable tags found. Try writing more content.', 'yt-auto-tag-generator' ) ) );
		}

		wp_send_json_success( array( 'tags' => $tags ) );
	}

	/**
	 * AJAX handler to apply tags.
	 *
	 * @return void
	 */
	public function ajax_apply_tags() {
		check_ajax_referer( 'yt_atg_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$tags    = isset( $_POST['tags'] ) && is_array( $_POST['tags'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['tags'] ) )
			: array();

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'yt-auto-tag-generator' ) ) );
		}

		if ( empty( $tags ) ) {
			wp_send_json_error( array( 'message' => __( 'No tags provided.', 'yt-auto-tag-generator' ) ) );
		}

		$this->apply_tags_to_post( $post_id, $tags );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: Number of tags */
					_n( '%d tag applied successfully.', '%d tags applied successfully.', count( $tags ), 'yt-auto-tag-generator' ),
					count( $tags )
				),
			)
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'yt-auto-tag-generator' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'yt_atg_options_group' );
				do_settings_sections( 'yt-auto-tag-generator' );
				submit_button();
				?>
			</form>

			<div class="yt-atg-info-box">
				<h2><?php esc_html_e( 'How It Works', 'yt-auto-tag-generator' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'The plugin analyzes your post content (title, body, excerpt)', 'yt-auto-tag-generator' ); ?></li>
					<li><?php esc_html_e( 'Extracts frequent keywords that meet minimum length and frequency criteria', 'yt-auto-tag-generator' ); ?></li>
					<li><?php esc_html_e( 'Filters out common stop words (the, is, and, etc.)', 'yt-auto-tag-generator' ); ?></li>
					<li><?php esc_html_e( 'Ranks keywords by frequency and relevance', 'yt-auto-tag-generator' ); ?></li>
					<li><?php esc_html_e( 'Suggests the top N keywords as tags', 'yt-auto-tag-generator' ); ?></li>
				</ol>

				<h3><?php esc_html_e( 'Tips for Better Results', 'yt-auto-tag-generator' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Write detailed, keyword-rich content', 'yt-auto-tag-generator' ); ?></li>
					<li><?php esc_html_e( 'Use specific terms rather than generic words', 'yt-auto-tag-generator' ); ?></li>
					<li><?php esc_html_e( 'Include important keywords in your title', 'yt-auto-tag-generator' ); ?></li>
					<li><?php esc_html_e( 'Review and refine generated tags before publishing', 'yt-auto-tag-generator' ); ?></li>
				</ul>
			</div>

			<style>
				.yt-atg-info-box {
					background: #fff;
					border: 1px solid #ccd0d4;
					padding: 20px;
					margin-top: 20px;
				}
				.yt-atg-info-box h2,
				.yt-atg-info-box h3 {
					margin-top: 0;
				}
				.yt-atg-info-box ol,
				.yt-atg-info-box ul {
					margin-left: 20px;
				}
			</style>
		</div>
		<?php
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=yt-auto-tag-generator' ) ),
			esc_html__( 'Settings', 'yt-auto-tag-generator' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public static function activate() {
		$default_options = array(
			'auto_generate'      => false,
			'require_preview'    => true,
			'max_tags'           => 5,
			'min_word_length'    => 4,
			'min_word_frequency' => 2,
			'post_types'         => array( 'post' ),
			'analyze_title'      => true,
			'analyze_content'    => true,
			'analyze_excerpt'    => false,
			'case_sensitive'     => false,
			'append_tags'        => true,
		);

		if ( ! get_option( 'yt_atg_options' ) ) {
			add_option( 'yt_atg_options', $default_options );
		}
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Cleanup if needed.
	}
}

/**
 * Plugin uninstall hook.
 *
 * @return void
 */
function yt_atg_uninstall() {
	delete_option( 'yt_atg_options' );
	wp_cache_flush();
}

// Register activation hook.
register_activation_hook( __FILE__, array( 'YT_Auto_Tag_Generator', 'activate' ) );

// Register deactivation hook.
register_deactivation_hook( __FILE__, array( 'YT_Auto_Tag_Generator', 'deactivate' ) );

// Register uninstall hook.
register_uninstall_hook( __FILE__, 'yt_atg_uninstall' );

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'YT_Auto_Tag_Generator', 'get_instance' ) );
