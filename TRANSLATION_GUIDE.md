# Custom Ebook Submission Plugin - Translation Guide

## Overview

This plugin has been fully internationalized and is ready for translation. All user-facing strings have been wrapped with WordPress translation functions using the text domain `ces`.

## Text Domain

The plugin uses the text domain: `ces`

## Files Modified for Translation

### 1. PHP Files
- `functions.php` - Main plugin functions and meta box labels
- `includes/class-ces-form-renderer.php` - Form rendering and JavaScript localization
- `includes/class-ces-form-handler.php` - Form processing
- `includes/helpers.php` - Helper functions
- `templates/ebook-submission-form.php` - Main form template
- `templates/ces-preview-modal.php` - Preview modal template

### 2. JavaScript Files
- `assets/js/ces-form.js` - Form functionality
- `assets/js/ces-image-slider.js` - Image slider functionality

## Translation Files

### POT File
- `languages/ces.pot` - Template file containing all translatable strings

### Translation Process

1. **For New Translations:**
   - Copy `languages/ces.pot` to `languages/ces-{locale}.po`
   - Translate the strings in the .po file
   - Generate the .mo file using a tool like Poedit or WP-CLI

2. **For Existing Translations (Loco Translate):**
   - The client already has translations via Loco Translate
   - When updating the plugin, Loco Translate will detect new strings
   - The client can add translations for new strings without losing existing ones

## JavaScript Localization

All JavaScript strings are localized using `wp_localize_script()` in `includes/class-ces-form-renderer.php`. The localized strings are available in the `ces_ajax.strings` object.

### Example Usage in JavaScript:
```javascript
// Instead of hardcoded strings:
alert('Please select a file');

// Use localized strings:
alert(ces_ajax.strings.please_select_file);
```

## Key Translation Functions Used

- `__()` - For simple string translation
- `_e()` - For echoing translated strings
- `esc_html__()` - For escaping and translating strings
- `esc_attr__()` - For escaping attributes and translating strings
- `esc_js()` - For escaping JavaScript strings

## Maintaining Existing Translations

### For Plugin Updates:

1. **Backup Existing Translations:**
   - If using Loco Translate, the translations are stored in the database
   - Export translations before updating the plugin

2. **After Plugin Update:**
   - Loco Translate will scan for new strings
   - Add translations for new strings
   - Existing translations will be preserved

3. **Manual Translation Files:**
   - If using .po/.mo files, merge the new POT with existing PO files
   - Update only the new strings

## Translation Tools

### Recommended Tools:
1. **Loco Translate** (WordPress Plugin) - User-friendly interface
2. **Poedit** - Desktop application for .po files
3. **WP-CLI** - Command line tool for generating .mo files

### WP-CLI Commands:
```bash
# Generate .mo file from .po file
wp i18n make-mo languages/ces-fr_FR.po languages/

# Update .po file from .pot file
wp i18n update-po languages/ces.pot languages/ces-fr_FR.po
```

## Testing Translations

1. **Change WordPress Language:**
   - Go to Settings > General
   - Change Site Language to your target language

2. **Test All Strings:**
   - Submit an eBook form
   - Check all error messages
   - Verify JavaScript alerts and confirmations
   - Test form validation messages

## Common Translation Issues

1. **JavaScript Strings Not Translating:**
   - Ensure strings are properly localized in `wp_localize_script()`
   - Check that `ces_ajax.strings` object is available

2. **Missing Translations:**
   - Verify text domain is correct (`ces`)
   - Check that .mo file is in the correct location
   - Ensure WordPress language is set correctly

3. **Loco Translate Not Detecting Strings:**
   - Clear Loco Translate cache
   - Rescan the plugin for strings

## File Structure for Translations

```
custom-ebook-submission/
├── languages/
│   ├── ces.pot                    # Template file
│   ├── ces-fr_FR.po              # French translations
│   ├── ces-fr_FR.mo              # Compiled French translations
│   ├── ces-es_ES.po              # Spanish translations
│   └── ces-es_ES.mo              # Compiled Spanish translations
```

## Support

For translation support or questions:
1. Check the WordPress Codex on internationalization
2. Review the Loco Translate documentation
3. Test thoroughly in the target language environment

## Notes

- All strings are now translatable
- JavaScript strings are properly localized
- The plugin maintains backward compatibility
- Existing translations via Loco Translate will be preserved during updates
