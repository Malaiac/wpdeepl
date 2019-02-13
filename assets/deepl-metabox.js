jQuery(document).ready(function() {

	// disable for gutenberg, waiting for a decent API
	if(wp && wp.data && wp.data.select( "core/editor" )) {
		var warning = jQuery('#deepl_metabox .hidden_warning').html();
		jQuery('#deepl_metabox div.inside').html(warning);

	}

	console.log("allo");
	console.dir(DeepLStrings.extended_fields);



jQuery( "#deepl_translate" ).on( "click", function() {

	var is_gutenberg = jQuery('.wp-block').length;
	if(is_gutenberg) {
		var warning = jQuery('#deepl_metabox .hidden_warning').html();
		jQuery('#deepl_metabox div.inside').html(warning);
		return false;
	}

	jQuery( '#deepl_spinner' ).css( 'visibility', 'visible' );

	var should_we_replace = jQuery( 'input[name="deepl_replace"]:checked' ).val();
	var target_lang = jQuery( '#deepl_target_lang' ).val();
	var text_bits = {};

	if(is_gutenberg) {
		text_bits['post_title'] = jQuery('.editor-post-title textarea').val();
		text_bits['post_excerpt'] = jQuery('.editor-post-excerpt textarea').val();
		/*jQuery('.editor-block-list__layout p.wp-block-paragraph').each(function(index) {
			text_bits['post_content_' + index] = jQuery(this).html();
		});*/

		jQuery('.edit-post-visual-editor .editor-rich-text p.wp-block-paragraph').each(function(block_index) {
			text_bits['post_content_' + block_index] = jQuery(this).html();
		});
		jQuery('.edit-post-visual-editor .editor-rich-text :header').each(function(block_index) {
			text_bits['post_conttit_' + block_index] = jQuery(this).html();
		});

	}
	else {
		text_bits['post_title'] = jQuery( 'input[name="post_title"]' ).val();
		text_bits['post_excerpt'] = jQuery( 'textarea[name="excerpt"]' ).val();
		text_bits['post_content'] = jQuery( '#content_ifr' ).contents().find( '#tinymce' ).html();
	}

	if(DeepLStrings.extended_fields) {
		var fields = DeepLStrings.extended_fields.split(',');
		fields.forEach(function(field) {
			var value = jQuery('*[name="acf[' + field + ']"]').val();
			//console.log(field);			console.log(value);
			text_bits['acf_' + field] = value;
		});
	}


//	JSON.stringify(text_bits);

	var data = {
	 	action: 'deepl_translate',
	 	post_id: jQuery( 'input[name="post_ID"]' ).val(),
	 	to_translate: text_bits,
	 	source_lang: jQuery( '#deepl_source_lang' ).val(),
	 	target_lang: target_lang,
	 	nonce: jQuery( '#deepl_nonce' ).val(),
	 	};

	 console.log( "AJAX parameters call" );
	 console.dir( data );
	 // stop
	 //jQuery( '#deepl_spinner' ).css( 'visibility', 'hidden' );	 return false;

	 jQuery.post( ajaxurl, data, function( responses ) {
	  	console.log( "Responses from API" ); console.dir( responses );
	  	if(responses.data.request.cached) {
	  		console.log("Résultats en cache");
	  	}
	  	else {
	  		console.log("Nouvelle requête");
	  		console.log("Exécutée en " + responses.data.request.time + " millisecondes");
	  	}
	 	$.each( responses.data.translations, function( index, value ) {
	 		//console.log( "index = " + index ); 			 		console.log(" text = " + value +  " VERSUS " + value.replace( /\\( . )/mg, "$1" ));
	 		if( index == 'post_title' ) {
	 			if( should_we_replace == 'replace' ) {
	 				var new_value = value.replace( /\\( . )/mg, "$1" )
	 			}
	 			else {
	 				var new_value = text_bits['post_title'] + '<lang="' + target_lang + '">' + value + '</lang>';
	 			}
	 			if(is_gutenberg) {
	 				jQuery('.editor-post-title textarea').val(new_value);
	 			}
	 			else {
	 				jQuery( 'input[name="post_title"]' ).val(new_value);
	 			}
	 		}
	 		else if( index == 'post_excerpt' ) {
	 			if( should_we_replace == 'replace' ) {
	 				var new_value = value.replace( /\\( . )/mg, "$1" )
	 			}
	 			else {
	 				var new_value = text_bits['post_excerpt'] + '<lang="' + target_lang + '">' + value + '</lang>';
	 			}
	 			if(is_gutenberg) {
	 				jQuery('.editor-post-excerpt textarea').val(new_value);
	 			}
	 			else {
	 				jQuery( 'textarea[name="excerpt"]' ).val(new_value);
	 			}
	 		}
	 		else if(index == 'post_content' && !is_gutenberg) {
	 			if( should_we_replace == 'replace' ) {
	 				jQuery( '#content_ifr' ).contents().find( '#tinymce' ).html( value.replace( /\\( . )/mg, "$1" ));
	 			}
	 			else {
	 				jQuery( '#content_ifr' ).contents().find( '#tinymce' ).html( 
	 					jQuery( '#content_ifr' ).contents().find( '#tinymce' ).html()
	 					+ '<lang="' + target_lang + '">' + value + '</lang>' 
	 					);
	 			}

	 		}
	 		else if( index.substr(0,3) == 'acf') {
	 			var field_name = index.substr(4);
	 			console.log("field " + field_name + " = " + value);
	 			if(value) {
	 				jQuery('*[name="acf[' + field_name + ']"]').val(value);
	 			}			
	 		}
	 		else if( is_gutenberg) {
	 			var content_index = index.substr(13);
	 			var content_type = (index.substr(0,12) == 'post_content') ? 'post_content' : 'post_content_titles';
	 			console.log("index = " + index + " type = " + content_type + " content index = " + content_index);
	 			//console.log("content index = " + content_index);
	 			
	 			if( content_type == 'post_content' ) {
	 				var blocks = jQuery('.edit-post-visual-editor .editor-rich-text p.wp-block-paragraph');
	 			}
	 			else if( content_type === 'post_content_titles') {
	 				var blocks = jQuery('.edit-post-visual-editor .editor-rich-text :header');
	 			}

	 			var block = blocks[content_index];
	 			var existing = jQuery(block).html();
	 			console.log("Replacing " + existing.length + " with " + value.length + " cars");
				if( should_we_replace == 'replace') {
						jQuery(block).html(value.replace( /\\( . )/mg, "$1" ))
					}
					else {
						jQuery(block).html( existing + value.replace( /\\( . )/mg, "$1" ) );
					}

		/*
				jQuery(blocks).each(function(index) {
					if(index == content_index) {
						if( should_we_replace == 'replace') {
							jQuery(this).html(value.replace( /\\( . )/mg, "$1" ))
						}
						else {
							jQuery(this).html( jQuery(this).html() + value.replace( /\\( . )/mg, "$1" ) );
						}

					}
				});	 			*/

	 		} 		
	 		else {
	 			console.log( "No action for " + index );
	 		}
	 	jQuery( '#deepl_spinner' ).css( 'visibility', 'hidden' );
	 	} );
	 });
	 });

});