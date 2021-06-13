function unEscapeHTMLTags( string ) {
	 var map = {
	    '&lt;' : '<',
	    '&gt;' : '>'
	  };
	  string = string.replace(/&[lg]t;/g, function(m) { return map[m]; });
	  return string;
}

function replace_translations(translations) {
	var is_gutenberg = jQuery('.wp-block').length;
	var is_classic_editor = jQuery('.wp-editor-area').length;
	jQuery.each( translations, function( index, value ) {
		var original_translation = value;
		if( index == 'post_title' ) {
			var new_value = value.replace( /\\( . )/mg, "$1" )
			if(is_gutenberg) {
				wp.data.dispatch( 'core/editor' ).editPost( { title: new_value } )
			}
			else {
				jQuery( 'input[name="post_title"]' ).val(new_value);
			}
		}
		else if( index == 'post_excerpt' ) {
			var new_value = value.replace( /\\( . )/mg, "$1" )
			if(is_gutenberg) {
				wp.data.dispatch( 'core/editor' ).editPost( { excerpt: new_value } )
			}
			else {
				jQuery( 'textarea[name="excerpt"]' ).val(new_value);
			}
		}
		else if(index == 'post_content') {
			var new_value = value.replace( /\\( . )/mg, "$1" )
			if( is_gutenberg ) {
				var fixed_translation = original_translation;
				fixed_translation = unEscapeHTMLTags( fixed_translation );
				wp.data.dispatch( 'core/block-editor' ).resetBlocks( wp.blocks.parse( fixed_translation ) );
				if( jQuery('textarea.editor-post-text-editor').val() !== undefined ) {
					jQuery('textarea.editor-post-text-editor').val(new_value);
				}
			}
			else if( is_classic_editor ) {
				if( classic_editor_visual ) {
					new_html = new_value;
					var editor = tinyMCE.get('content'); 
				   editor.setContent(new_html);
				}
				else if( classic_editor_text ) {
					jQuery( '#content.wp-editor-area' ).val( new_value );
				}

			}
			else {
				new_value = new_value.replace
				jQuery( '#content_ifr' ).contents().find( '#tinymce' ).html( new_value );
			}
		}
		else if( index.substr(0,3) == 'acf') {
			var field_name = index.substr(4);
			if(value) {
				jQuery('*[name="acf[' + field_name + ']"]').val(value);
			}			
		}
		else {
			console.log( "No action for " + index );
		}
	} );
	jQuery( '#etranslate_spinner' ).css( 'visibility', 'hidden' );
}

function checkStatus(id) {
	setTimeout(function () {
		var statusData = {
			action: 'etranslate_translate_status',
			post_id: jQuery( 'input[name="post_ID"]' ).val(),
			id: id
			};
		jQuery.post( ajaxurl, statusData, function( response ) {
			if (response.data.status == 'TRANSLATING') {
				checkStatus(id);
				return;
			}
			if (response.data.status == 'DONE') {
				replace_translations(response.data.translation);
				return;
			}
		  });
	}, 1000);
}

jQuery(document).ready(function() {
	if( jQuery('select[name="post_lang_choice"]').length ) {
		var current_language = jQuery('select[name="post_lang_choice"]').find(':selected').attr('lang');
		current_language = current_language.replace('-', '_');
		jQuery('select#etranslate_target_lang option[value="' + current_language + '"]').prop('selected', true );
	}

jQuery( "#etranslate_translate" ).on( "click", function() {

	var is_gutenberg = jQuery('.wp-block').length;
	var is_classic_editor = jQuery('.wp-editor-area').length;

	if(is_gutenberg) {
		console.log("is gutenberg");
		is_classic_editor = false;
	}
	if(is_classic_editor) console.log(" classic editor plugin");

	jQuery( '#etranslate_spinner' ).css( 'visibility', 'visible' );

	var target_lang = jQuery( '#etranslate_target_lang' ).val();
	var text_bits = {};

	if(is_gutenberg) {
		const { select } = wp.data;		
		text_bits['post_title'] = select("core/editor").getEditedPostAttribute( 'title' );
		text_bits['post_excerpt'] = select("core/editor").getEditedPostAttribute( 'excerpt' );
		text_bits['post_content'] = select("core/editor").getEditedPostAttribute( 'content' );
	}
	else {
		text_bits['post_title'] = jQuery( 'input[name="post_title"]' ).val();
		text_bits['post_excerpt'] = jQuery( 'textarea[name="excerpt"]' ).val();

		var classic_editor_visual = false;
		var classic_editor_text = false;
		if( jQuery( '#content.wp-editor-area' ).val() !== undefined ) {
			var classic_editor_text = true;
		}
		else {
			console.log("empty text editor ?");
			console.log("Please send me an email to test your installation");
		}
		if( jQuery( '#content_ifr' ).contents().find( '#tinymce' ).html() !== undefined ) {
			var classic_editor_visual = true;
		}

		text_bits['post_content'] = jQuery( '#content.wp-editor-area' ).val();
	}

	var data = {
	 	action: 'etranslate_translate',
	 	post_id: jQuery( 'input[name="post_ID"]' ).val(),
	 	to_translate: text_bits,
	 	source_lang: jQuery( '#etranslate_source_lang' ).val(),
	 	target_lang: target_lang,
	 	nonce: jQuery( '#etranslate_nonce' ).val(),
	 	};

	 jQuery.post( ajaxurl, data, function( responses ) {
		  if( !responses.success ) {
			jQuery('#deepl_metabox #deepl_error').html( responses.data[0] );
			jQuery('#deepl_metabox #deepl_error').css('display', 'block');
		}
		else {
			checkStatus(responses.data.id);
		}
	 });
	 });

});