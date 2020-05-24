<div id="fgm2wc-help-instructions">

<h1>FG Magento to WooCommerce Instructions</h1>
<img src="https://ps.w.org/fg-magento-to-woocommerce/assets/fg-magento-to-woocommerce.png" alt="FG Magento to WooCommerce screenshot" />

<h2>Step 0:</h2>
Before using the plugin, you must:
<ul>
<li>Define the WordPress permalinks on <a href="<?php echo admin_url('options-permalink.php'); ?>" target="_blank">the permalink screen</a><br />
"Post name" is a good choice.</li>
<li>Define the media sizes on <a href="<?php echo admin_url('options-media.php'); ?>" target="_blank">the media settings screen</a><br />
The plugin will copy your Magento images to the WordPress media library and will resize them to all the sizes defined here.</li>
</ul>

<h2>Step 1:</h2>
<h3>Empty the WordPress content</h3>
<p>This action is not mandatory the first time you run the import. But it is required if you have already ran an import and if you want to restart it from scratch. It will delete all the WordPress content (posts, pages, attachments, categories, tags, navigation menus, products, custom post types).</p>

<h2>Step 2:</h2>
<h3>Test the connection</h3>
<p>After having filled in the database parameters, you can test the connection to the Magento database. It will tell you how much data the plugin has found in the Magento database.</p>

<h2>Step 3:</h2>
<h3>Run the import</h3>
<p>After having chosen the different import options (see the options help tab), you click on this button to run the import. It can take a long time depending on the number of products and images in Magento.</p>
<p>If the screen becomes blank, let it turn until it finishes. Once the process is finished, it will display the import results.</p>
<p>If the process stops before having imported all the content, you can run it again and it will resume where it left off. This may happen if you have a timeout on your server or if the memory becomes low. In this case, ensure that the automatic removal checkbox is not checked.</p>

<h2>Automatic import from cron <span class="fgm2wc_premium_feature">(Premium feature)</span></h2>
<p>If you want to update automatically the existing products and orders, and import the new data that may have changed in the Magento database, you can do it with a cron command.</p>
<ul>
	<li>First you need to set up correctly all the settings in the import screen. It is advised to run the first import manually to be sure that the settings are correct.</li>
	<li>Then define your crontab like:<br />
		<code>
			0 0 * * * php /path/to/wp/wp-content/plugins/fg-magento-to-woocommerce-premium/cron_import.php >>/dev/null
		</code><br />
		This will run the import once a day at 0:00.<br />
		You can of course change the frequency if you want.
	</li>
</ul>

<?php do_action('fgm2wc_help_instructions'); ?>

</div>
