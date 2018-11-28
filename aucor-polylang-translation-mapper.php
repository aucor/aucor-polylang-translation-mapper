<?php
/*
Plugin Name: Polylang Translation Mapper
Plugin URI: https://github.com/aucor/aucor-polylang-translation-mapper/
Version: 1.2
Author: Aucor Oy
Author URI: https://www.aucor.fi/
Description: Connects translations of posts with same master ID
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: aucor-polylang-translation-mapper
*/

defined( 'ABSPATH' ) or die( 'Get out of here stalker!' );

class AucorTranslationMapper {

	private $updated;
	private $ignored;
	private $admin_notice;

	private $plugin_name;
	private $plugin_slug;
	private $post_type;    // edit me below
	private $meta_key;     // edit me below
	private $taxonomy;

	private $query_type;

	/**
	 * Constructor
	 */

	public function __construct() {

		$this->plugin_name = 'Aucor: Polylang Translation Mapper';
		$this->plugin_slug = 'aucor-polylang-translation-mapper';

		$this->post_type = 'post'; // post type
		$this->meta_key = 'master_id'; // "master id" that all translations share
		$this->taxonomy = 'post_translations'; // taxonomy that has a term which all translations share

		$this->query_type = 'meta_query'; // choose which method to use to map posts. "meta_query" or "tax_query".

		$this->updated = 0;
		$this->ignored = 0;

		if (defined('DOING_AJAX') && DOING_AJAX) {
			return; // don't mess up ajax calls
		}

		add_action('admin_init', array(&$this, 'iterate'));
		add_action('admin_notices', array(&$this, 'admin_notice'));

	}

	/**
	 * Iterate
	 */

	function iterate() {

		$used_master_ids = array();

		if( isset($_GET[ $this->plugin_slug ]) ) {

			$paged = ($_GET[ $this->plugin_slug . '-p' ]) ? $_GET[ $this->plugin_slug . '-p' ] : 1; // Paged because running too many posts at a time causes memory overflow

			$args = array(
				'post_type' => $this->post_type,
				'posts_per_page' => 50, // Make me less if PHP can't handle it
				'paged' => $paged,
				'orderby' => 'title',
				'order' => 'DESC',
				'lang' => '',
				'post_status' => 'any'
			);

			$args[$this->query_type] = array(
				'tax_query' => array(
					array(
						'taxonomy' => $this->taxonomy,
						'operator' => 'EXISTS'
					)
				),
				'meta_query' => array(
					array(
						'key' => $this->meta_key,
						'compare' => 'EXISTS'
					),
				)
			)[$this->query_type];

			$query = new WP_Query( $args );
			$max_num_pages = $query->max_num_pages;

			while ( $query->have_posts() ) : $query->the_post();

				$master_id = array(
				                'meta_query' => get_post_meta( $query->post->ID, $this->meta_key, true ),
				                'tax_query' => get_the_terms( $query->post->ID, $this->taxonomy )[0]->term_id
				            )[$this->query_type];
				$translations = pll_get_post_translations( $query->post->ID );

				if(in_array($master_id, $used_master_ids)) {
					continue; // already linked, skip
				}

				$args_sub = array(
					'post_type' => $this->post_type,
					'posts_per_page' => -1,
					'no_found_rows' => true,
					'lang' => '',
					'post_status' => 'any'
				);

				$args_sub[$this->query_type] = array(
					'tax_query' => array(
						array(
							'taxonomy' => $this->taxonomy,
							'terms' => $master_id
						),
					),
					'meta_query' => array(
						array(
							'key' => $this->meta_key,
							'compare' => '=',
							'value' => $master_id
						),
					)
				)[$this->query_type];

				$sub_query = new WP_Query( $args_sub );
				while ( $sub_query->have_posts() ) : $sub_query->the_post();

					$translations[ pll_get_post_language( $sub_query->post->ID ) ] = $sub_query->post->ID;

				endwhile;
				wp_reset_query();

				if(!empty($translations)) {
					pll_save_post_translations($translations);
					$this->updated++;
				}

				array_push($used_master_ids, $master_id); // Doesn't carry to the next page but it's only small optimization

			endwhile;
			wp_reset_query();

			$this->admin_notice = 'In this step: ' . $this->updated . ' translation connections made';

			$next_page = ($paged < $max_num_pages) ? $paged + 1 : null;

			if(!empty($next_page)) {
				$full_url = "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
				$full_url = str_replace($this->plugin_slug . '-p=' . $paged, $this->plugin_slug . '-p=' . $next_page, $full_url);
				$this->admin_notice .= '<a class="button" style="margin:-.15rem 0 .25rem .25rem" href="' . $full_url . '">Continue (step ' . $next_page . '/' . $max_num_pages . ')</a>';
			} else {
				$this->admin_notice .= '<br /><br />Done! You should deactivate and remove me now!';
			}

		} else {

			$full_url = "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$full_url .= (strpos($full_url, '?') === false) ? '?' : '&';
			$full_url .= $this->plugin_slug . '=start&' . $this->plugin_slug . '-p=1';

			$this->admin_notice = '<p>Post type: <b>' . $this->post_type . '</b><br />';
			$this->admin_notice .= ($this->query_type === 'meta_query') ? 'Master meta key: <b>' . $this->meta_key . '</b></p>' : 'Translation taxonomy: <b>' . $this->taxonomy . '</b></p>';
			$this->admin_notice .= '<p>Take a backup of your database. There is no return.</p>';
			$this->admin_notice .= '<a class="button" href="' . $full_url . '">Start connecting translations</a>';

		}
	}

	/**
	 * Admin message displayer
	 */

	function admin_notice() {
			?>
			<div class="updated">
				<p><b><?php echo $this->plugin_name; ?></b><br />
				<?php echo $this->admin_notice; ?>
				</p>
			</div>
		<?php
	}

}

add_action('plugins_loaded', function() {
	global $aucor_translation_mapper;
	$aucor_translation_mapper = new AucorTranslationMapper();
});

