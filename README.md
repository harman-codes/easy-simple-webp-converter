# Easy & Simple WebP Converter

Convert every image in your WordPress media library to **WebP** with a single click, using a configurable quality setting from **0 to 100**.

## Features

- **Sidebar menu item** — adds a "WebP Converter" page to the WordPress admin sidebar.
- **Quality control (0–100)** — a range slider and number input, saved via the WordPress Settings API. Higher values mean better quality but larger files.
- **Auto-convert new uploads** — an ON/OFF option that automatically converts every newly added image to WebP at the selected quality, as soon as it is uploaded.
- **One-click conversion** — converts all media images (JPEG, PNG, GIF, BMP) to WebP at the selected quality.
- **Auto re-generates thumbnails** — every registered image size is re-created as a `.webp` variant and the attachment metadata is updated.
- **Safe & resumable** — conversion runs in small batches via AJAX to avoid server timeouts; you can resume if interrupted.
- **Built-in checks** — verifies WebP support on your server, checks user permissions, and validates requests with a nonce.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- PHP **GD** or **Imagick** compiled with **WebP support**

> If WebP is not available on your server, the plugin shows a notice and disables the convert button. Ask your host to enable GD/Imagick WebP support.

## Installation

1. Upload the `easy-webp-converter` folder to `/wp-content/plugins/`.
2. Activate the **Easy & Simple WebP Converter** plugin in the WordPress admin.
3. Go to the new **WebP Converter** menu item in the sidebar.

## Usage

1. **Set the quality** — use the slider or the number field (0–100), then click **Save settings**.
2. **Optional: auto-convert new uploads** — tick **Auto-convert new uploads** and click **Save settings**. New images added to the media library are converted to WebP automatically at the selected quality.
3. **Convert existing images** — click **Convert all images to WebP**. A progress bar shows the conversion status, along with converted / skipped / failed counts.
3. When finished, all supported images in the media library are now WebP.

### Notes

- Images that are already `.webp` are skipped.
- The original "full" file is retained by WordPress so you can still use the built-in image editor; all display sizes are replaced with WebP.
- A resumable queue is stored while a conversion is in progress. If you stop partway, click the convert button again to continue.

## Files

```
easy-webp-converter/
├── easy-webp-converter.php          # Plugin bootstrap
├── inc/
│   └── class-easy-webp-converter.php # Main plugin logic
└── assets/
    ├── css/admin.css                # Admin styles
    └── js/admin.js                  # AJAX conversion & UI
```

## Changelog

### 1.1.0
- Added an **Auto-convert new uploads** ON/OFF option. When enabled, newly added images are automatically converted to WebP at the selected quality.

### 1.0.0
- Initial release.

## License

GPL-2.0+ — see the LICENSE URI in the plugin header.
