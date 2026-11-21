#!/usr/bin/env python3
# Draws docs/static/images/forbes-and-moreword-signs.svg: two crossed street signs on
# a post, FORBES AVE facing us and MOREWORD AVE running back from it, in the
# Web Kilt's colors with Open Sans Bold as outlines (so no font is needed)
#   python3 dev/signs.py docs/static/images/forbes-and-moreword-signs.svg
import sys
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen

font = TTFont("/usr/share/fonts/truetype/open-sans/OpenSans-Bold.ttf")
gs, cmap, upm = font.getGlyphSet(), font.getBestCmap(), font["head"].unitsPerEm
hmtx = font["hmtx"]

def width(text, size, track=0.04):
    return sum(hmtx[cmap[ord(c)]][0] for c in text) * size / upm + track * size * (len(text) - 1)

def text(text, size, x, y, track=0.04):
    """Outline path for text with its baseline at (x, y)."""
    pen = SVGPathPen(gs)
    cx = 0
    for c in text:
        g = cmap[ord(c)]
        s = size / upm
        gs[g].draw(TransformPen(pen, (s, 0, 0, -s, x + cx, y)))
        cx += hmtx[g][0] * s + track * size
    return pen.getCommands()

RED, INK, GRAY, WHITE, POLE = "#c41230", "#22262a", "#6d6e71", "#fff", "#4a535b"
W, H = 600, 400
out = [f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" width="{W}" height="{H}" role="img" aria-labelledby="t">',
       '<title id="t">Two street signs on a post: Forbes Ave and Moreword Ave</title>']
px = 270  # the post's center
# The post
out.append(f'<rect x="{px-9}" y="96" width="18" height="{H-96}" fill="{POLE}"/>')
out.append(f'<rect x="{px-13}" y="88" width="26" height="12" rx="3" fill="{POLE}"/>')
# MOREWORD AVE runs back from the post: a sign seen at an angle, its far end
# smaller, on top
def sign(label, fill, x, y, w, h, size):
    tw = width(label, size)
    d = text(label, size, x + (w - tw) / 2, y + h / 2 + size * 0.36)
    return (f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="6" fill="{fill}"/>'
            f'<rect x="{x+5}" y="{y+5}" width="{w-10}" height="{h-10}" rx="3" fill="none" stroke="{WHITE}" stroke-width="2.5"/>'
            f'<path d="{d}" fill="{WHITE}"/>')
# The back sign, drawn flat then put in perspective: it narrows to the right
bw, bh = 380, 70
back = sign("MOREWORD AVE", INK, 0, 0, bw, bh, 34)
# Skew and squash so it runs away from us, starting at the post
out.append(f'<g transform="translate({px} 104) matrix(0.62 -0.14 0 0.86 0 0)">{back}</g>')
# The front sign, facing us, crossing the post below it
fw, fh = 340, 74
out.append(f'<g transform="translate({px - fw/2} 196)">{sign("FORBES AVE", RED, 0, 0, fw, fh, 38)}</g>')
out.append('</svg>')
open(sys.argv[1], "w").write("\n".join(out) + "\n")
