<?php
/*
Plugin Name: IP2Map
Plugin URI: http://www.ip2map.com
Description: Shows last 100 visitors location on map.
Version: 1.0.16
Author: IP2Location
Author URI: http://www.ip2map.com
*/

$ip2map = new IP2Map();

add_action('widgets_init', [$ip2map, 'register']);

class IP2Map
{
	public function activate()
	{
		if (!function_exists('register_sidebar_widget')) {
			return;
		}

		$options = ['title' => 'IP2Map'];

		if (!get_option('IP2Map')) {
			add_option('IP2Map', $options);
		} else {
			update_option('IP2Map', $options);
		}

		add_action('admin_enqueue_scripts', [&$this, 'plugin_enqueues']);
		add_action('wp_ajax_ip2map_submit_feedback', [&$this, 'submit_feedback']);
		add_action('admin_footer_text', [&$this, 'admin_footer_text']);
	}

	public function deactivate()
	{
		delete_option('IP2Map');
	}

	public function control()
	{
		$options = get_option('IP2Map');

		if (!is_array($options)) {
			$options = ['title' => 'IP2Map'];
		}

		if ($_POST['ip2map-title']) {
			$data['title'] = strip_tags(stripslashes($_POST['ip2map-title']));
			update_option('IP2Map', $data);
		}

		echo '<p style="text-align:right;"><label for="ip2map-title">' . __('Title:') . '</label> <input style="width: 200px;" id="ip2map-title" name="ip2map-title" type="text" value="' . htmlspecialchars($options['title'], ENT_QUOTES) . '" /></p>';
	}

	public function widget($args)
	{
		$options = get_option('IP2Map');
		echo $args['before_widget'] . $args['before_title'] . $options['title'] . $args['after_title'];
		echo '<a href="https://www.ip2map.com" target="_blank"><img src="https://www.ip2map.com/ip2map.gif" border="0" width="100" height="50" /></a>';
		echo $args['after_widget'];
	}

	public function register()
	{
		wp_register_sidebar_widget('IP2Map_Widget', 'IP2Map', [$this, 'widget']);
		wp_register_widget_control('IP2Map_Control', 'IP2Map', [$this, 'control']);
	}

	public function plugin_enqueues($hook)
	{
		if ($hook == 'plugins.php') {
			// Add in required libraries for feedback modal
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_style('wp-jquery-ui-dialog');

			wp_enqueue_script('ip2map_admin_script', plugins_url('/assets/js/feedback.js', __FILE__), ['jquery'], null, true);
		}
	}

	public function admin_footer_text($footer_text)
	{
		$plugin_name = substr(basename(__FILE__), 0, strpos(basename(__FILE__), '.'));
		$current_screen = get_current_screen();

		if (($current_screen && strpos($current_screen->id, $plugin_name) !== false)) {
			$footer_text .= sprintf(
				__('Enjoyed %1$s? Please leave us a %2$s rating. A huge thanks in advance!', $plugin_name),
				'<strong>' . __('IP2Map', $plugin_name) . '</strong>',
				'<a href="https://wordpress.org/support/plugin/' . $plugin_name . '/reviews/?filter=5/#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			);
		}

		if ($current_screen->id == 'plugins') {
			return $footer_text . '
			<div id="ip2map-feedback-modal" class="hidden" style="max-width:800px">
				<span id="ip2map-feedback-response"></span>
				<p>
					<strong>Would you mind sharing with us the reason to deactivate the plugin?</strong>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2map-feedback" value="1"> I no longer need the plugin
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2map-feedback" value="2"> I couldn\'t get the plugin to work
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2map-feedback" value="3"> The plugin doesn\'t meet my requirements
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2map-feedback" value="4"> Other concerns
						<br><br>
						<textarea id="ip2map-feedback-other" style="display:none;width:100%"></textarea>
					</label>
				</p>
				<p>
					<div style="float:left">
						<input type="button" id="ip2map-submit-feedback-button" class="button button-danger" value="Submit & Deactivate" />
					</div>
					<div style="float:right">
						<a href="#">Skip & Deactivate</a>
					</div>
				</p>
			</div>';
		}

		return $footer_text;
	}

	public function submit_feedback()
	{
		$feedback = (isset($_POST['feedback'])) ? $_POST['feedback'] : '';
		$others = (isset($_POST['others'])) ? $_POST['others'] : '';

		$options = [
			1 => 'I no longer need the plugin',
			2 => 'I couldn\'t get the plugin to work',
			3 => 'The plugin doesn\'t meet my requirements',
			4 => 'Other concerns' . (($others) ? (' - ' . $others) : ''),
		];

		if (isset($options[$feedback])) {
			if (!class_exists('WP_Http')) {
				include_once ABSPATH . WPINC . '/class-http.php';
			}

			$request = new WP_Http();
			$response = $request->request('https://www.ip2location.com/wp-plugin-feedback?' . http_build_query([
				'name'    => 'ip2map',
				'message' => $options[$feedback],
			]), ['timeout' => 5]);
		}
	}
}
