<?php



function deepl_show_usage() {
	?>
		<h3><?php _e( 'Usage', 'wpdeepl' ); ?></h3>
		<?php
		$DeepLApiUsage = new DeepLApiUsage();
		$usage = $DeepLApiUsage->request();

		if( $usage && is_array( $usage ) && array_key_exists( 'character_count', $usage ) && array_key_exists( 'character_limit', $usage )) :
			$ratio = round( 100 * ( $usage['character_count'] / $usage['character_limit'] ), 3 );
			$left_chars = $usage['character_limit'] - $usage['character_count'];

		?>
		<div class="progress-bar blue">
			<span style="width: <?php echo round( (100 - $ratio ), 0 ); ?>%"><b><?php printf( __( '%s characters remaining', 'wpdeepl' ), number_format( $left_chars )); ?></b></span>
			<div class="progress-text"><?php
			printf( __( '%s / %s characters translated', 'wpdeepl' ), number_format_i18n( $usage['character_count'] ), number_format_i18n( $usage['character_limit'] ) );
			 echo " - " . $ratio; ?> %</div>
			 <small class="request_time"><?php printf( __( 'Request done in: %f milliseconds', 'wpdeepl' ), $DeepLApiUsage->getRequestTime( true )) ?></small>
		</div>
		<?php
		endif;
}