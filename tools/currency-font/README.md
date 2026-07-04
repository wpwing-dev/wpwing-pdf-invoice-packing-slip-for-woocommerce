# WPWing Currency font build

Builds `src/assets/fonts/WPWingCurrency-Regular.ttf` - a ~120 KB font covering
every codepoint used by WooCommerce currency symbols (verified 99/99 against
`get_woocommerce_currency_symbols()`), plus the complete Unicode Currency
Symbols block U+20A0-U+20C0.

It is a subset/merge derivative of Noto fonts (SIL OFL 1.1), renamed per the
OFL Reserved Font Name rule. See `src/assets/fonts/OFL.txt`.

## Why

Dompdf has no per-glyph font fallback - a font stack resolves to the first
registered family for the whole element. DejaVu Sans (bundled with Dompdf)
lacks 9 WooCommerce currency codepoints (Taka, manat, lari, riel, rial,
afghani, Thaana, Sinhala). WooCommerce wraps the symbol in its own
`.woocommerce-Price-currencySymbol` span, so this font is applied to just
that span by the templates.

## Rebuild

1. Download the Noto sources (Regular TTFs, hinted) from
   https://notofonts.github.io into this directory: Noto Sans, and Noto Sans
   Bengali / Armenian / Arabic / Thai / Khmer / Tamil / Gujarati / Devanagari /
   Thaana / Sinhala.
2. Run the build in a python container from this directory:

```sh
docker run --rm -v "$PWD":/work python:3.12-slim sh /work/build-currency-font.sh
```

3. Copy `WPWingCurrency-Regular.ttf` to `src/assets/fonts/`.
4. Bump the `fonts_ready_2` marker name in `class-wpwing-wcpdf-document.php`
   if glyph coverage changed, so existing installs re-register the font.
