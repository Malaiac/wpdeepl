<?php


function deepl_install_plugin() {
	update_option( 'deepl_plugin_installed', 0 );
	update_option( 'wpdeepl_metabox_post_types', array( 'post', 'page' ));
}