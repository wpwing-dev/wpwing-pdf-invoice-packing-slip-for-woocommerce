#!/usr/bin/env python3
"""Set name table records for the WPWing Currency font (OFL-compliant rename)."""
import sys
from fontTools.ttLib import TTFont

src, dst = sys.argv[1], sys.argv[2]
font = TTFont(src)

COPYRIGHT = (
    "Copyright 2022-2025 The Noto Project Authors (https://github.com/notofonts). "
    "WPWing Currency is a subset/merge derivative of Noto fonts, renamed per OFL."
)
LICENSE = (
    "This Font Software is licensed under the SIL Open Font License, Version 1.1. "
    "This license is available with a FAQ at: https://openfontlicense.org"
)
NAMES = {
    0: COPYRIGHT,
    1: "WPWing Currency",
    2: "Regular",
    3: "1.000;WPWG;WPWingCurrency-Regular",
    4: "WPWing Currency Regular",
    5: "Version 1.000",
    6: "WPWingCurrency-Regular",
    13: LICENSE,
    14: "https://openfontlicense.org",
}

name = font["name"]
# Drop all existing records, then write ours for Windows (3,1,0x409) and Mac (1,0,0).
name.names = []
for nid, value in NAMES.items():
    name.setName(value, nid, 3, 1, 0x409)
    name.setName(value, nid, 1, 0, 0)

# Normalize vertical metrics to DejaVu Sans ratios (ascent 1901 / descent -483
# at 2048 upem -> 928 / -236 at 1000). The merge otherwise takes the max ascent
# and min descent across all source scripts (~1374/-738), and Dompdf sizes line
# boxes from ascender-descender, which would nearly double the line height of
# any line containing a currency symbol.
hhea = font["hhea"]
hhea.ascent = 928
hhea.descent = -236
hhea.lineGap = 0
os2 = font["OS/2"]
os2.usWinAscent = 928
os2.usWinDescent = 236
os2.sTypoAscender = 928
os2.sTypoDescender = -236
os2.sTypoLineGap = 0

font.save(dst)
print("saved", dst)
