# YT Auto Tag Generator

A WordPress plugin that automatically suggests and adds tags based on post content analysis. Uses keyword frequency analysis to extract the most relevant terms and provides a preview interface before applying tags.

## Description

The Auto Tag Generator plugin analyzes your post content (title, body, and excerpt) to identify frequent keywords and suggests them as tags. It intelligently filters out common stop words, ensures minimum word length and frequency, and ranks keywords by relevance to help you tag posts efficiently.

## Features

- **Intelligent Keyword Extraction**: Analyzes post content for relevant keywords
- **Stop Words Filtering**: Excludes 80+ common English words (the, is, and, etc.)
- **Frequency Analysis**: Ranks words by appearance count
- **Weighted Analysis**: Title words count 3x, excerpt 2x, content 1x
- **Preview Interface**: Review suggested tags before applying
- **Meta Box Integration**: Convenient sidebar panel in post editor
- **One-Click Generation**: Generate tags with a single button click
- **Tag Removal**: Remove unwanted suggestions before applying
- **Configurable Settings**: Control all aspects of tag generation
- **Auto-Generate Mode**: Optionally auto-generate on save
- **Append or Replace**: Choose to keep or replace existing tags
- **Multiple Post Types**: Works with posts, pages, and custom post types
- **Min Word Length**: Set minimum characters for tag candidates
- **Min Frequency**: Require words appear multiple times
- **Max Tags Limit**: Control how many tags to generate (1-20)
- **Case Sensitivity**: Optional case-sensitive analysis
- **AJAX Powered**: No page reloads required
- **Keyboard Shortcuts**: Ctrl+G (generate), Escape (cancel)
- **Responsive Design**: Mobile-friendly interface
- **Translation Ready**: Full i18n support

## Installation

1. Upload `yt-auto-tag-generator.php` to `/wp-content/plugins/`
2. Upload `yt-auto-tag-generator.css` to the same directory
3. Upload `yt-auto-tag-generator.js` to the same directory
4. Activate the plugin through the 'Plugins' menu
5. Configure settings at Settings → Auto Tag Generator
6. Look for the "Auto Tag Generator" meta box when editing posts

## Usage

### Manual Tag Generation

1. Create or edit a post
2. Write your content (title, body, excerpt)
3. Find the **Auto Tag Generator** meta box in the sidebar
4. Click **Generate Tags** button
5. Review the suggested tags
6. Remove any unwanted tags (click the × button)
7. Click **Apply Tags** to add them to your post
8. Save/update your post

### Automatic Tag Generation

1. Go to **Settings → Auto Tag Generator**
2. Check **"Automatically generate tags when saving posts"**
3. Optionally uncheck **"Show preview before applying"** for instant tagging
4. Save settings
5. Tags will now generate automatically when you save posts

### Tag Preview Interface

The preview shows:
- Suggested tags as clickable chips
- Remove button (×) on each tag
- Apply and Cancel buttons
- Success/error messages

## Settings Reference

### Auto Generate

**Automatically generate tags when saving posts**
- **Default**: Disabled
- **Description**: Tags generate on save_post hook
- **Note**: Still respects preview setting

**Show preview before applying (recommended)**
- **Default**: Enabled
- **Description**: Shows preview meta box
- **Recommendation**: Keep enabled for review

### Maximum Tags

- **Range**: 1-20
- **Default**: 5
- **Description**: Maximum tags to generate
- **Strategy**: Top N most frequent keywords

### Minimum Word Length

- **Range**: 2-10 characters
- **Default**: 4
- **Description**: Shortest word to consider
- **Examples**:
  - 4: "code", "test", "blog"
  - 5: "wordpress", "plugin"

### Minimum Word Frequency

- **Range**: 1-10 occurrences
- **Default**: 2
- **Description**: Times word must appear
- **Note**: Words in title count 3x

### Analyze Content From

**Post title (weight: 3x)**
- **Default**: Enabled
- **Impact**: Title words are 3x more important
- **Use Case**: Emphasize key topic words

**Post content (weight: 1x)**
- **Default**: Enabled
- **Impact**: Standard keyword extraction
- **Use Case**: Comprehensive analysis

**Post excerpt (weight: 2x)**
- **Default**: Disabled
- **Impact**: Excerpt words count 2x
- **Use Case**: Manually written summaries

### Post Types

- **Default**: Posts only
- **Options**: All public post types
- **Description**: Which content types to enable for
- **Examples**: Posts, Pages, Products, Events

## How It Works

### 1. Content Gathering

The plugin collects text from:
- Post title (if enabled)
- Post content (if enabled)
- Post excerpt (if enabled)

### 2. Text Processing

- Removes HTML tags
- Normalizes whitespace
- Optionally converts to lowercase
- Splits into words using regex

### 3. Filtering

**Excludes**:
- Words shorter than minimum length
- Stop words (common words)
- Pure numbers
- Words below minimum frequency

**Stop Words List** (80+ words):
- Articles: the, a, an
- Pronouns: he, she, it, they
- Prepositions: in, on, at, to
- Conjunctions: and, or, but
- Common verbs: is, are, was, were
- Common adjectives: good, some, other

### 4. Frequency Counting

- Counts each word occurrence
- Applies weighting (title 3x, excerpt 2x, content 1x)
- Sorts by frequency (descending)

### 5. Tag Suggestion

- Takes top N words (based on max tags setting)
- Returns as tag suggestions
- Displays in preview interface

### 6. Tag Application

- Optionally appends to existing tags
- Or replaces all tags
- Uses wp_set_post_tags()

## Keyboard Shortcuts

- **Ctrl/Cmd + G**: Generate tags
- **Ctrl/Cmd + A**: Apply tags (when preview visible)
- **Escape**: Cancel preview

## Configuration Examples

### SEO-Focused Blog

```
Maximum Tags: 8
Min Word Length: 4
Min Frequency: 2
Analyze: Title (3x), Content (1x)
Post Types: Posts
```

### Documentation Site

```
Maximum Tags: 5
Min Word Length: 5
Min Frequency: 3
Analyze: Title (3x), Excerpt (2x), Content (1x)
Post Types: Posts, Pages
```

### E-Commerce Store

```
Maximum Tags: 10
Min Word Length: 3
Min Frequency: 1
Analyze: Title (3x), Content (1x)
Post Types: Products
```

### News Site

```
Maximum Tags: 6
Min Word Length: 4
Min Frequency: 2
Analyze: Title (3x), Excerpt (2x)
Post Types: Posts
Auto Generate: Enabled
Preview: Disabled
```

## Code Examples

### Programmatically Generate Tags

```php
$generator = YT_Auto_Tag_Generator::get_instance();
$tags = $generator->generate_tags($post_id);

print_r($tags);
// Array ( [0] => wordpress [1] => plugin [2] => development )
```

### Add Custom Stop Words

```php
add_filter('yt_atg_stop_words', function($stop_words) {
    $stop_words[] = 'custom';
    $stop_words[] = 'ignore';
    return $stop_words;
});
```

### Modify Tag Count

```php
add_filter('yt_atg_max_tags', function($max, $post_id) {
    // More tags for longer posts
    $word_count = str_word_count(get_post_field('post_content', $post_id));
    return $word_count > 1000 ? 10 : 5;
}, 10, 2);
```

### Custom Content Weight

```php
add_filter('yt_atg_title_weight', function($weight) {
    return 5; // Increase title importance
});
```

## Use Cases

### Content Marketing

**Scenario**: Blog with hundreds of posts
**Setup**: Auto-generate enabled, preview disabled
**Result**: Tags applied automatically on publish

### SEO Optimization

**Scenario**: Improve search rankings with keywords
**Setup**: Max 8 tags, analyze title + content
**Result**: Relevant keyword tags for every post

### Content Organization

**Scenario**: Large content library needing taxonomy
**Setup**: Preview enabled, manual approval
**Result**: Curated tag set after review

### Multi-Author Blog

**Scenario**: Contributors inconsistent with tagging
**Setup**: Auto-generate on save with preview
**Result**: Standardized tagging across authors

### E-Commerce Site

**Scenario**: Product posts need categorization
**Setup**: Custom post type enabled, shorter words
**Result**: Product feature tags auto-generated

## Frequently Asked Questions

### How does the weighting work?

Words in the title count 3 times, excerpt 2 times, and content once. If "wordpress" appears once in the title and twice in content, its frequency is 3 + 2 = 5.

### Can I customize the stop words?

Yes, use the `yt_atg_stop_words` filter to add or remove stop words from the default list.

### Will it work with Block Editor (Gutenberg)?

Yes, fully compatible with both Classic Editor and Block Editor (Gutenberg).

### Does it work with custom post types?

Yes, select which post types in Settings → Auto Tag Generator.

### Can I use it with WooCommerce products?

Yes, enable the "Products" post type in settings.

### Will it replace my existing tags?

Only if "Append tags" is unchecked in settings. By default, it adds to existing tags.

### How do I prevent certain words from becoming tags?

Those words are likely stop words. Add them using the `yt_atg_stop_words` filter.

### Can I generate tags for old posts?

Yes, edit any post and click "Generate Tags" in the meta box.

### Does it support multiple languages?

The stop words are English only, but you can add your language's stop words via filter. The analysis works with any UTF-8 text.

### Will it slow down my site?

No, tag generation only happens when you click the button or save a post. It has minimal performance impact.

## Troubleshooting

### No tags suggested

**Cause**: Content too short or too common
**Solutions**:
- Write more detailed content (150+ words recommended)
- Lower minimum word frequency (try 1)
- Lower minimum word length (try 3)
- Check that content sources are enabled

### Only common words suggested

**Cause**: Content lacks specific keywords
**Solutions**:
- Include industry-specific terms
- Use focused vocabulary
- Write about specific topics
- Increase minimum word length (try 5)

### Generate button doesn't work

**Causes**:
- JavaScript errors
- Post not saved (no post ID)
**Solutions**:
- Check browser console for errors
- Save post as draft first
- Deactivate other plugins temporarily
- Clear browser cache

### Tags not applying

**Causes**:
- Permission issue
- Post type not enabled
**Solutions**:
- Verify you can edit the post
- Check Settings → Post Types
- Save post first
- Check for JavaScript errors

### Wrong tags suggested

**Cause**: Content analysis picking wrong keywords
**Solutions**:
- Increase minimum frequency (try 3)
- Disable excerpt analysis
- Increase minimum word length
- Manually remove unwanted tags before applying

## Performance

### Impact on Save Time

- **With Preview**: No impact (AJAX only)
- **Without Preview**: +50-100ms per save
- **Depends On**: Content length, word count

### Database Queries

- **Per Generation**: 0 (pure text processing)
- **Per Save**: 1 UPDATE (wp_set_post_tags)

### Benchmarks

- **500 words**: ~20ms generation time
- **1000 words**: ~40ms generation time
- **2000 words**: ~80ms generation time
- **5000 words**: ~200ms generation time

## Security

### Features

- **Nonce Verification**: All AJAX requests
- **Capability Checks**: `edit_post` required
- **Input Sanitization**:
  - `sanitize_text_field()` for tags
  - `absint()` for post IDs
  - `wp_strip_all_tags()` for content
- **Output Escaping**:
  - `esc_html()` for text
  - `esc_attr()` for attributes
- **No SQL Queries**: Uses WordPress APIs only
- **XSS Prevention**: All output escaped

## Uninstallation

When you delete the plugin:

1. Plugin options deleted from database
2. No posts or tags are modified
3. WordPress cache flushed
4. No data remains

**Note**: Generated tags remain on posts (they're normal WordPress tags).

## Changelog

### 1.0.0 (2025-01-XX)
- Initial release
- Keyword frequency analysis
- Stop words filtering (80+ words)
- Weighted content analysis
- Preview interface with tag removal
- Manual and auto-generate modes
- Configurable min length and frequency
- Multiple post type support
- Meta box in post editor
- AJAX-powered generation
- Keyboard shortcuts
- Append or replace tags mode
- Translation ready
- Gutenberg and Classic Editor support

## Roadmap

Potential future features:
- Multi-language stop words
- Synonym detection
- Related post analysis
- Tag suggestions from similar posts
- Bulk tag generation tool
- Tag frequency dashboard
- Custom stop words in UI
- TF-IDF algorithm option
- Machine learning integration
- Tag trending analysis

## Developer Notes

### Line Count
- **PHP**: 886 lines
- **CSS**: 350 lines
- **JS**: 415 lines
- **Total**: 1,651 lines

### Extending the Plugin

#### Custom Keyword Extraction

```php
add_filter('yt_atg_extract_keywords', function($keywords, $text) {
    // Add custom keyword extraction logic
    return $keywords;
}, 10, 2);
```

#### Modify Tag Suggestions

```php
add_filter('yt_atg_suggested_tags', function($tags, $post_id) {
    // Filter or enhance suggested tags
    return array_filter($tags, function($tag) {
        return strlen($tag) > 5; // Only long tags
    });
}, 10, 2);
```

#### Custom Weight Calculation

```php
add_filter('yt_atg_word_weight', function($weight, $word, $source) {
    // source: 'title', 'content', 'excerpt'
    if ($source === 'title') {
        return 5; // Increase title weight
    }
    return $weight;
}, 10, 3);
```

### Algorithm Details

**Frequency Calculation**:
```
frequency = (title_count × 3) + (excerpt_count × 2) + (content_count × 1)
```

**Tag Ranking**:
1. Calculate frequency for each word
2. Filter by minimum frequency
3. Sort descending by frequency
4. Take top N (max tags)

### Contributing

Follow WordPress Coding Standards:

```bash
phpcs --standard=WordPress yt-auto-tag-generator.php
```

## Support

For issues, questions, or feature requests:
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Support Forums](https://wordpress.org/support/)
- [GitHub Repository](https://github.com/krasenslavov/yt-auto-tag-generator)

## License

GPL v2 or later

## Author

**Krasen Slavov**
- Website: [https://krasenslavov.com](https://krasenslavov.com)
- GitHub: [@krasenslavov](https://github.com/krasenslavov)

---

Tag your posts intelligently with automated keyword extraction!
