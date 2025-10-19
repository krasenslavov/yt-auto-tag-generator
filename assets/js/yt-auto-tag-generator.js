/**
 * YT Auto Tag Generator - JavaScript
 *
 * @package YT_Auto_Tag_Generator
 * @version 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Auto Tag Generator Handler
	 */
	var AutoTagGenerator = {

		/**
		 * Selected tags for preview.
		 */
		selectedTags: [],

		/**
		 * Initialize the plugin.
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			// Generate tags button
			$(document).on('click', '#yt-atg-generate', this.generateTags.bind(this));

			// Apply tags button
			$(document).on('click', '#yt-atg-apply', this.applyTags.bind(this));

			// Cancel button
			$(document).on('click', '#yt-atg-cancel', this.cancelPreview.bind(this));

			// Remove individual tag
			$(document).on('click', '.yt-atg-tag-remove', this.removeTag.bind(this));

			// Toggle tag selection
			$(document).on('click', '.yt-atg-tag', this.toggleTag.bind(this));
		},

		/**
		 * Generate tags from post content.
		 *
		 * @param {Event} e Click event.
		 */
		generateTags: function(e) {
			e.preventDefault();

			var self = this;
			var $button = $('#yt-atg-generate');
			var $preview = $('#yt-atg-preview');
			var $message = $('#yt-atg-message');
			var postId = $('#post_ID').val();

			if (!postId) {
				this.showMessage(ytAtgData.strings.error, 'error');
				return;
			}

			// Show loading state
			$button.prop('disabled', true).addClass('yt-atg-loading').text(ytAtgData.strings.generating);
			$message.removeClass('yt-atg-message-success yt-atg-message-error yt-atg-message-info').hide();

			// AJAX request
			$.ajax({
				url: ytAtgData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'yt_atg_generate_preview',
					nonce: ytAtgData.nonce,
					post_id: postId
				},
				success: function(response) {
					if (response.success && response.data.tags) {
						self.showPreview(response.data.tags);
					} else {
						self.showMessage(response.data.message || ytAtgData.strings.noTags, 'error');
						$preview.hide();
					}
				},
				error: function() {
					self.showMessage(ytAtgData.strings.error, 'error');
					$preview.hide();
				},
				complete: function() {
					$button.prop('disabled', false).removeClass('yt-atg-loading').text('Generate Tags');
				}
			});
		},

		/**
		 * Show tag preview.
		 *
		 * @param {Array} tags Generated tags.
		 */
		showPreview: function(tags) {
			var self = this;
			var $preview = $('#yt-atg-preview');
			var $tagsList = $('#yt-atg-tags-list');

			// Store tags
			this.selectedTags = tags.slice();

			// Clear previous tags
			$tagsList.empty();

			// Add tags
			tags.forEach(function(tag, index) {
				var $tag = $('<div>', {
					'class': 'yt-atg-tag',
					'data-tag': tag,
					'data-index': index
				});

				var $tagText = $('<span>', {
					'class': 'yt-atg-tag-text',
					'text': tag
				});

				var $removeBtn = $('<span>', {
					'class': 'yt-atg-tag-remove',
					'html': '&times;',
					'title': 'Remove this tag'
				});

				$tag.append($tagText).append($removeBtn);
				$tagsList.append($tag);
			});

			// Show preview
			$preview.slideDown(300);

			// Hide message
			$('#yt-atg-message').hide();
		},

		/**
		 * Apply selected tags to post.
		 *
		 * @param {Event} e Click event.
		 */
		applyTags: function(e) {
			e.preventDefault();

			var self = this;
			var $button = $('#yt-atg-apply');
			var $message = $('#yt-atg-message');
			var postId = $('#post_ID').val();

			if (this.selectedTags.length === 0) {
				this.showMessage('No tags selected.', 'error');
				return;
			}

			// Show loading state
			$button.prop('disabled', true).text(ytAtgData.strings.applying);

			// AJAX request
			$.ajax({
				url: ytAtgData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'yt_atg_apply_tags',
					nonce: ytAtgData.nonce,
					post_id: postId,
					tags: this.selectedTags
				},
				success: function(response) {
					if (response.success) {
						self.showMessage(response.data.message, 'success');

						// Add success animation to tags
						$('.yt-atg-tag').addClass('yt-atg-tag-applied');

						// Update WordPress tags UI
						self.updateWordPressTags();

						// Hide preview after delay
						setTimeout(function() {
							$('#yt-atg-preview').slideUp(300);
						}, 2000);
					} else {
						self.showMessage(response.data.message || ytAtgData.strings.error, 'error');
					}
				},
				error: function() {
					self.showMessage(ytAtgData.strings.error, 'error');
				},
				complete: function() {
					$button.prop('disabled', false).text('Apply Tags');
				}
			});
		},

		/**
		 * Cancel tag preview.
		 *
		 * @param {Event} e Click event.
		 */
		cancelPreview: function(e) {
			e.preventDefault();
			$('#yt-atg-preview').slideUp(300);
			$('#yt-atg-message').hide();
			this.selectedTags = [];
		},

		/**
		 * Remove tag from preview.
		 *
		 * @param {Event} e Click event.
		 */
		removeTag: function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $tag = $(e.target).closest('.yt-atg-tag');
			var tag = $tag.data('tag');

			// Remove from selected tags
			var index = this.selectedTags.indexOf(tag);
			if (index > -1) {
				this.selectedTags.splice(index, 1);
			}

			// Visual feedback
			$tag.addClass('yt-atg-tag-removed').fadeOut(300, function() {
				$(this).remove();

				// Check if any tags remain
				if ($('.yt-atg-tag').length === 0) {
					$('#yt-atg-tags-list').html('<div class="yt-atg-empty">No tags selected.</div>');
					$('#yt-atg-apply').prop('disabled', true);
				}
			});
		},

		/**
		 * Toggle tag selection (for future enhancement).
		 *
		 * @param {Event} e Click event.
		 */
		toggleTag: function(e) {
			// Skip if clicking remove button
			if ($(e.target).hasClass('yt-atg-tag-remove')) {
				return;
			}

			// Future: Add ability to toggle tag selection
			// For now, tags are selected by default
		},

		/**
		 * Update WordPress default tags interface.
		 */
		updateWordPressTags: function() {
			// Try to update the WordPress tags metabox
			var $tagsInput = $('.tagsdiv input.newtag');

			if ($tagsInput.length > 0) {
				// Trigger tag refresh in WordPress
				// Note: This is a simplified version
				// Full implementation would need to interact with WordPress's tag management
				setTimeout(function() {
					// Reload the page to show updated tags
					// In production, you might want to update the UI without reloading
					if (typeof wp !== 'undefined' && wp.data) {
						// Block editor
						location.reload();
					} else {
						// Classic editor - update tag list
						var tagList = $('.tagchecklist');
						if (tagList.length > 0) {
							location.reload();
						}
					}
				}, 2000);
			}
		},

		/**
		 * Show notification message.
		 *
		 * @param {string} message Message text.
		 * @param {string} type    Message type (success, error, info).
		 */
		showMessage: function(message, type) {
			type = type || 'info';

			var $message = $('#yt-atg-message');
			$message
				.removeClass('yt-atg-message-success yt-atg-message-error yt-atg-message-info')
				.addClass('yt-atg-message-' + type)
				.html(message)
				.slideDown(300);

			// Auto-hide success messages
			if (type === 'success') {
				setTimeout(function() {
					$message.slideUp(300);
				}, 5000);
			}
		},

		/**
		 * Get post content for analysis.
		 *
		 * @return {string} Post content.
		 */
		getPostContent: function() {
			var content = '';

			// Get title
			var $title = $('#title, #post-title-0');
			if ($title.length > 0) {
				content += $title.val() + ' ';
			}

			// Get content from classic editor
			if (typeof tinymce !== 'undefined') {
				var editor = tinymce.get('content');
				if (editor) {
					content += editor.getContent({ format: 'text' });
				}
			}

			// Get content from textarea (if tinymce not available)
			var $content = $('#content');
			if ($content.length > 0) {
				content += $content.val();
			}

			// Get content from block editor
			if (typeof wp !== 'undefined' && wp.data) {
				var blocks = wp.data.select('core/editor').getBlocks();
				blocks.forEach(function(block) {
					if (block.attributes && block.attributes.content) {
						content += ' ' + block.attributes.content;
					}
				});
			}

			return content;
		},

		/**
		 * Count words in text.
		 *
		 * @param {string} text Text to analyze.
		 * @return {number} Word count.
		 */
		countWords: function(text) {
			// Remove HTML tags and count words
			var plainText = text.replace(/<[^>]*>/g, ' ');
			var words = plainText.trim().split(/\s+/);
			return words.filter(function(word) {
				return word.length > 0;
			}).length;
		},

		/**
		 * Add keyboard shortcuts.
		 */
		addKeyboardShortcuts: function() {
			$(document).on('keydown', function(e) {
				// Ctrl/Cmd + G: Generate tags
				if ((e.ctrlKey || e.metaKey) && e.key === 'g') {
					e.preventDefault();
					$('#yt-atg-generate').click();
				}

				// Ctrl/Cmd + A (in preview): Apply tags
				if ((e.ctrlKey || e.metaKey) && e.key === 'a' && $('#yt-atg-preview').is(':visible')) {
					e.preventDefault();
					$('#yt-atg-apply').click();
				}

				// Escape: Cancel preview
				if (e.key === 'Escape' && $('#yt-atg-preview').is(':visible')) {
					e.preventDefault();
					$('#yt-atg-cancel').click();
				}
			});
		},

		/**
		 * Show tooltip on hover.
		 */
		addTooltips: function() {
			$(document).on('mouseenter', '.yt-atg-tag', function() {
				var tag = $(this).data('tag');
				$(this).attr('title', 'Click to select/deselect: ' + tag);
			});
		}
	};

	/**
	 * Initialize when DOM is ready.
	 */
	$(document).ready(function() {
		// Check if we're on post edit screen
		if ($('#yt-atg-generate').length > 0) {
			AutoTagGenerator.init();
			AutoTagGenerator.addKeyboardShortcuts();
			AutoTagGenerator.addTooltips();
		}
	});

})(jQuery);
