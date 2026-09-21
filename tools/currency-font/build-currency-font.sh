#!/bin/sh
# Builds WPWingCurrency-Regular.ttf by subsetting and merging Noto fonts (SIL OFL 1.1).
# Runs inside a python docker image. Working dir: /work (this scratchpad).
set -e

pip install -q fonttools

cd /work
rm -f sub-*.ttf merged.ttf WPWingCurrency-Regular.ttf

SUBSET="pyftsubset --drop-tables+=GSUB,GPOS,GDEF,vhea,vmtx --name-IDs=* --glyph-names"

# Base: Latin (kr, R$, KSh, zl...), Latin-1 + Ext-A, florin, Cyrillic (lv, den, rub),
# and the complete Unicode Currency Symbols block U+20A0-U+20C0.
$SUBSET NotoSans-Regular.ttf \
  --unicodes=U+0020-007E,U+00A0-00FF,U+0100-017F,U+0192,U+0400-045F,U+20A0-20C0 \
  --output-file=sub-base.ttf

# Script-specific currency signs (sets are disjoint from base and each other).
$SUBSET NotoSansBengali-Regular.ttf    --unicodes=U+09F2-09F3            --output-file=sub-bn.ttf
$SUBSET NotoSansArmenian-Regular.ttf   --unicodes=U+0530-058F            --output-file=sub-hy.ttf
$SUBSET NotoSansArabic-Regular.ttf     --unicodes=U+0600-06FF,U+FDFC     --output-file=sub-ar.ttf
$SUBSET NotoSansThai-Regular.ttf       --unicodes=U+0E3F                 --output-file=sub-th.ttf
$SUBSET NotoSansKhmer-Regular.ttf      --unicodes=U+17DB                 --output-file=sub-km.ttf
$SUBSET NotoSansTamil-Regular.ttf      --unicodes=U+0BF9                 --output-file=sub-ta.ttf
$SUBSET NotoSansGujarati-Regular.ttf   --unicodes=U+0AF1                 --output-file=sub-gu.ttf
$SUBSET NotoSansDevanagari-Regular.ttf --unicodes=U+A830-A83F            --output-file=sub-dv.ttf
$SUBSET NotoSansThaana-Regular.ttf     --unicodes=U+0780-07BF            --output-file=sub-dh.ttf
$SUBSET NotoSansSinhala-Regular.ttf    --unicodes=U+0D80-0DFF            --output-file=sub-si.ttf

fonttools merge sub-base.ttf sub-bn.ttf sub-hy.ttf sub-ar.ttf sub-th.ttf \
  sub-km.ttf sub-ta.ttf sub-gu.ttf sub-dv.ttf sub-dh.ttf sub-si.ttf --output-file=merged.ttf

python /work/rename-currency-font.py merged.ttf WPWingCurrency-Regular.ttf

ls -la WPWingCurrency-Regular.ttf
