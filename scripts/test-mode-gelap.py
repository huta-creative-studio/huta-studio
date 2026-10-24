#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Tes regresi mode gelap — Tema Huta Studio.

Menyasurek struktur DOM & piksel yang berubah diam-diam saat mode gelap aktif,
agar perbaikan mode gelap tidak bisa diam-diam rusak lagi.

Pemakaian:
    python scripts/test-mode-gelap.py                    # uji situs produksi
    python scripts/test-mode-gelap.py --base http://127.0.0.1:8080
    python scripts/test-mode-gelap.py --only highcharts,peta

Butuh: pip install playwright && playwright install chromium
"""
from __future__ import annotations

import argparse
import re
import sys

try:
    from playwright.sync_api import sync_playwright
except ImportError:  # pragma: no cover
    print("ERROR: playwright belum terpasang. Jalankan: pip install playwright")
    sys.exit(2)

try:
    from PIL import Image
except ImportError:  # pragma: no cover
    print("ERROR: Pillow belum terpasang. Jalankan: pip install pillow")
    sys.exit(2)

DEFAULT_BASE = "https://sordangbolon-ujungpadang.desa.id"

# ---------------------------------------------------------------------------
# utilitas warna (dipakai untuk luminance & rasio kontras WCAG)
# ---------------------------------------------------------------------------


def rgb_of(css):
    """'rgb(6, 40, 22)' / 'rgba(6,40,22,.5)' / [6,40,22] -> (r,g,b) tuple atau None."""
    if isinstance(css, (list, tuple)):
        try:
            return tuple(int(round(float(x))) for x in css[:3])
        except (TypeError, ValueError):
            return None
    if not css:
        return None
    m = re.match(r"rgba?\(([^)]+)\)", css.strip())
    if not m:
        return None
    parts = [p.strip() for p in m.group(1).split(",")]
    try:
        return tuple(int(float(p)) for p in parts[:3])
    except ValueError:
        return None


def alpha_of(css: str) -> float:
    if not css:
        return 0.0
    m = re.match(r"rgba?\(([^)]+)\)", css.strip())
    if not m:
        return 1.0
    parts = [p.strip() for p in m.group(1).split(",")]
    if len(parts) >= 4:
        try:
            return float(parts[3])
        except ValueError:
            return 1.0
    return 1.0


def luminance(rgb) -> float:
    def chan(c):
        c = c / 255.0
        return c / 12.92 if c <= 0.03928 else ((c + 0.055) / 1.055) ** 2.4

    r, g, b = (chan(x) for x in rgb)
    return 0.2126 * r + 0.7152 * g + 0.0722 * b


def rasio_contrast(a, b) -> float:
    la, lb = luminance(a), luminance(b)
    hi, lo = max(la, lb), min(la, lb)
    return (hi + 0.05) / (lo + 0.05)


def gelap(rgb, ambang: float = 0.18) -> bool:
    """True bila warna dianggap 'gelap' (luminance relatif di bawah ambang)."""
    return luminance(rgb) < ambang


def Terang(rgb, ambang: float = 0.35) -> bool:
    return luminance(rgb) >= ambang


# ---------------------------------------------------------------------------
# JavaScript in-page
# ---------------------------------------------------------------------------

JS_BG_EFEKTIF = """
(el) => {
  function satu(node) {
    while (node && node.nodeType === 1) {
      const bg = getComputedStyle(node).backgroundColor;
      const m = bg && bg.match(/rgba?\\(([^)]+)\\)/);
      if (m) {
        const p = m[1].split(',').map(s => parseFloat(s.trim()));
        const a = p.length > 3 ? p[3] : 1;
        if (a > 0.05) {
          if (a >= 0.95) return p.slice(0, 3);
          const induk = satu(node.parentElement) || [255, 255, 255];
          return p.slice(0, 3).map((c, i) => c * a + induk[i] * (1 - a));
        }
      }
      node = node.parentElement;
    }
    return [255, 255, 255];
  }
  return satu(el);
}
"""

# ---------------------------------------------------------------------------
# pemeriksa per temuan
# ---------------------------------------------------------------------------


class Pemeriksa:
    def __init__(self, halaman: str, base: str, page):
        self.halaman = halaman
        self.base = base
        self.page = page
        self.gagal = []
        self.lulus = 0

    def cek(self, nama: str, ok: bool, detail: str = "") -> bool:
        if ok:
            self.lulus += 1
            print(f"    [ OK ] {nama}" + (f"  ({detail})" if detail else ""))
        else:
            self.gagal.append(nama)
            print(f"    [GAGAL] {nama}" + (f"  ({detail})" if detail else ""))
        return ok

    def goto(self, jalur: str, tunggu: float = 2.5):
        url = self.base.rstrip("/") + jalur
        self.page.goto(url, wait_until="domcontentloaded", timeout=45000)
        try:
            self.page.wait_for_load_state("networkidle", timeout=12000)
        except Exception:
            pass
        self.page.wait_for_timeout(int(tunggu * 1000))
        return url

    # -- P1-1 Highcharts ----------------------------------------------------
    def highcharts(self):
        print("\n  [P1-1] Grafik Highcharts")
        self.goto("/data-statistik/pekerjaan")
        ada = self.page.wait_for_selector(".highcharts-container", timeout=20000)
        self.cek("container Highcharts muncul", ada is not None)
        if not ada:
            return
        info = self.page.evaluate(
            """() => {
            const c = document.querySelector('.highcharts-container');
            const svg = c.querySelector('svg');
            const bg = svg.querySelector('.highcharts-background');
            const plot = svg.querySelector('.highcharts-plot-background');
            const texts = Array.from(svg.querySelectorAll('text'))
                 .map(t => getComputedStyle(t).fill);
            const r = c.getBoundingClientRect();
            return {
              bgFill: bg ? getComputedStyle(bg).fill : null,
              plotFill: plot ? getComputedStyle(plot).fill : null,
              textFills: texts,
              rect: { x: r.x, y: r.y, w: r.width, h: r.height }
            };
        }"""
        )
        bg = rgb_of(info["bgFill"] or "")
        self.cek(
            "latar grafik gelap (fill .highcharts-background)",
            bg is not None and gelap(bg),
            f"fill={info['bgFill']} lumi={luminance(bg):.3f}" if bg else "fill tidak terbaca",
        )
        if info["textFills"]:
            paling_terang = max(
                (rgb_of(t) or (0, 0, 0)) for t in info["textFills"] if rgb_of(t)
            )
            self.cek(
                "teks grafik terang (kontras dengan latar gelap)",
                Terang(paling_terang),
                f"fill paling terang={paling_terang}",
            )
        # bukti piksel: seluruh container grafik harus didominasi warna gelap.
        # (Warna irisan data memang terang — itu pengodean data, bukan latar.)
        r = info["rect"]
        if r["w"] > 20 and r["h"] > 20:
            png = self.page.screenshot(
                clip={
                    "x": max(0, r["x"]),
                    "y": max(0, r["y"]),
                    "width": max(10, r["w"]),
                    "height": max(10, r["h"]),
                }
            )
            import io

            im = Image.open(io.BytesIO(png)).convert("L")
            piksel = list(im.getdata())
            if piksel:
                med = sorted(piksel)[len(piksel) // 2]
                self.cek(
                    "piksel grafik didominasi gelap (median < 110)",
                    med < 110,
                    f"median={med} n={len(piksel)}",
                )
                terang = sum(1 for v in piksel if v > 205) / len(piksel)
                self.cek(
                    "piksel area putih graphik < 20%",
                    terang < 0.20,
                    f"fraksi_terang={terang:.3f}",
                )

    # -- P1-2 Leaflet -------------------------------------------------------
    def peta(self):
        print("\n  [P1-2] Peta Leaflet")
        self.goto("/data-lembaga/bpn-mauja-nagori", tunggu=4.0)
        ada = self.page.wait_for_selector(".leaflet-container", timeout=20000)
        self.cek("container Leaflet muncul", ada is not None)
        if not ada:
            return
        info = self.page.evaluate(
            """() => {
            const c = document.querySelector('.leaflet-container');
            const tile = c.querySelector('.leaflet-tile');
            const attr = c.querySelector('.leaflet-control-attribution');
            const zoom = c.querySelector('.leaflet-control-zoom a');
            const layers = c.querySelector('.leaflet-control-layers');
            const link = attr ? attr.querySelector('a') : null;
            return {
              tileSrc: tile ? tile.src : null,
              tileCount: c.querySelectorAll('.leaflet-tile').length,
              attrBg: attr ? getComputedStyle(attr).backgroundColor : null,
              attrColor: attr ? getComputedStyle(attr).color : null,
              linkColor: link ? getComputedStyle(link).color : null,
              zoomBg: zoom ? getComputedStyle(zoom).backgroundColor : null,
              zoomColor: zoom ? getComputedStyle(zoom).color : null,
              layersBg: layers ? getComputedStyle(layers).backgroundColor : null
            };
        }"""
        )
        self.cek(
            "lapisan gelap aktif (tile CartoDB dark_all)",
            bool(info["tileSrc"]) and "dark_all" in info["tileSrc"],
            f"tile={info['tileSrc']}",
        )
        self.cek(
            "kontrol Leaflet (attribution) berlatar gelap",
            (rgb_of(info["attrBg"] or "") is not None and gelap(rgb_of(info["attrBg"]))),
            f"bg={info['attrBg']}",
        )
        self.cek(
            "kontrol zoom Leaflet berlatar gelap",
            (rgb_of(info["zoomBg"] or "") is not None and gelap(rgb_of(info["zoomBg"]))),
            f"bg={info['zoomBg']}",
        )
        link = rgb_of(info["linkColor"] or "")
        attr_bg = rgb_of(info["attrBg"] or "")
        if link and attr_bg:
            self.cek(
                "tautan attribution kontras >= 4.5:1",
                rasio_contrast(link, attr_bg) >= 4.5,
                f"{rasio_contrast(link, attr_bg):.2f}:1 fg={info['linkColor']} bg={info['attrBg']}",
            )

    # -- P1-4 badge IDM -----------------------------------------------------
    def idm(self):
        print("\n  [P1-4] Badge IDM")
        self.goto("/status-idm/2024", tunggu=3.0)
        for sel, label in [
            (".bg-blue-500", "skor saat ini"),
            (".bg-yellow-500", "status IDM"),
            (".bg-green-500", "target status"),
            (".bg-red-500", "skor minimal"),
        ]:
            el = self.page.query_selector(f"#status-idm {sel}")
            if not el:
                self.cek(f"badge {label} ditemukan", False, sel)
                continue
            bg = rgb_of(self.page.evaluate("el => getComputedStyle(el).backgroundColor", el))
            fg = rgb_of(self.page.evaluate("el => getComputedStyle(el).color", el))
            if bg is None or fg is None:
                self.cek(f"badge {label} punya warna", False)
                continue
            r = rasio_contrast(fg, bg)
            self.cek(
                f"badge {label} kontras teks >= 4.5:1",
                r >= 4.5,
                f"{r:.2f}:1 fg={fg} bg={bg}",
            )

    # -- P1-3 token aksen ---------------------------------------------------
    def tautan(self):
        print("\n  [P1-3] Token teks aksen")
        self.goto("/artikel/kategori/berita-desa", tunggu=2.5)
        for sel, label in [
            ("a.text-h5", "judul artikel"),
            ("a.text-sm.hover\\:text-primary-100", "tautan Indeks"),
        ]:
            el = self.page.query_selector(sel)
            if not el:
                self.cek(f"{label} ditemukan", False, sel)
                continue
            fg = rgb_of(self.page.evaluate("el => getComputedStyle(el).color", el))
            bg = rgb_of(self.page.evaluate(JS_BG_EFEKTIF, el))
            if fg is None or bg is None:
                self.cek(f"{label} punya warna", False)
                continue
            r = rasio_contrast(fg, bg)
            self.cek(
                f"{label} kontras >= 4.5:1",
                r >= 4.5,
                f"{r:.2f}:1 fg={fg} bg={bg}",
            )

    # -- P2-1 paginasi DataTables ------------------------------------------
    def paginasi(self):
        print("\n  [P2-1] Paginasi DataTables")
        self.goto("/first/statistik/501", tunggu=3.5)
        tombol = self.page.query_selector_all(".paginate_button")
        if not tombol:
            self.cek("tombol paginasi ditemukan", False)
            return
        for i, el in enumerate(tombol):
            kelas = self.page.evaluate("el => el.className", el) or ""
            bg_css = self.page.evaluate("el => getComputedStyle(el).backgroundColor", el)
            fg = rgb_of(self.page.evaluate("el => getComputedStyle(el).color", el))
            bg = rgb_of(self.page.evaluate(JS_BG_EFEKTIF, el))
            label = f"paginasi[{i}] {kelas}"
            self.cek(
                f"{label} tidak berlatar terang",
                not (rgb_of(bg_css or "") == (239, 239, 239)),
                f"background-color={bg_css}",
            )
            if fg and bg:
                r = rasio_contrast(fg, bg)
                self.cek(
                    f"{label} kontras >= 4.5:1",
                    r >= 4.5,
                    f"{r:.2f}:1 fg={fg} bg={bg}",
                )


    # -- Mode terang: tampilan terang TIDAK boleh berubah --------------------
    def terang(self):
        print("\n  [Mode terang] Tampilan terang tidak boleh berubah")
        # 1. Grafik tetap terang
        self.goto("/data-statistik/pekerjaan")
        self.page.wait_for_selector(".highcharts-container", timeout=20000)
        fill = self.page.evaluate(
            "() => { const b = document.querySelector('.highcharts-background');"
            " return b ? getComputedStyle(b).fill : null; }"
        )
        self.cek(
            "latar grafik tetap putih",
            rgb_of(fill or "") == (255, 255, 255),
            f"fill={fill}",
        )

        # 2. Tautan aksen tetap teal-700 (nilai bawaan --huta-aksen)
        self.goto("/artikel/kategori/berita-desa")
        el = self.page.query_selector("a.text-h5")
        if el is None:
            self.cek("judul artikel ditemukan", False)
        else:
            fg = rgb_of(self.page.evaluate("el => getComputedStyle(el).color", el))
            self.cek(
                "warna tautan artikel tetap teal-700",
                fg == (15, 118, 110),
                f"color={fg}",
            )

        # 3. Badge IDM tetap memakai warna Tailwind bawaan
        self.goto("/status-idm/2024")
        for sel, harapan, label in [
            (".bg-blue-500", (59, 130, 246), "skor saat ini"),
            (".bg-yellow-500", (234, 179, 8), "status IDM"),
            (".bg-green-500", (34, 197, 94), "target status"),
            (".bg-red-500", (239, 68, 68), "skor minimal"),
        ]:
            el = self.page.query_selector(f"#status-idm {sel}")
            if el is None:
                self.cek(f"badge {label} ditemukan", False, sel)
                continue
            bg = rgb_of(self.page.evaluate("el => getComputedStyle(el).backgroundColor", el))
            self.cek(f"badge {label} tetap warna bawaan", bg == harapan, f"bg={bg}")

        # 4. Tombol paginasi tidak berubah di mode terang
        self.goto("/first/statistik/501", tunggu=3.5)
        el = self.page.query_selector(".paginate_button")
        if el is None:
            self.cek("tombol paginasi ditemukan", False)
        else:
            bgc = self.page.evaluate("el => getComputedStyle(el).backgroundColor", el)
            self.cek(
                "latar tombol paginasi tidak berubah",
                rgb_of(bgc or "") == (239, 239, 239),
                f"background-color={bgc}",
            )

        # 5. Peta memakai lapisan asli (bukan CartoDB gelap).
        #    Catatan: di mode terang peta memakai Mapbox GL (canvas WebGL),
        #    sehingga tidak ada elemen <img class="leaflet-tile"> sama sekali.
        self.goto("/data-lembaga/bpn-mauja-nagori", tunggu=4.0)
        self.page.wait_for_selector(".leaflet-container", timeout=20000)
        src_gelap = self.page.evaluate(
            """() => {
            const c = document.querySelector('.leaflet-container');
            const src = [];
            c.querySelectorAll('img').forEach(i => { if (i.src) src.push(i.src); });
            return src.join('|');
        }"""
        )
        self.cek(
            "peta tidak memakai lapisan gelap",
            "dark_all" not in (src_gelap or ""),
            f"img src={src_gelap[:120]}",
        )


PEMERIKSA = {
    "highcharts": "highcharts",
    "peta": "peta",
    "idm": "idm",
    "tautan": "tautan",
    "paginasi": "paginasi",
    "terang": "terang",
}


def main() -> int:
    ap = argparse.ArgumentParser(description="Tes regresi mode gelap Tema Huta Studio")
    ap.add_argument("--base", default=DEFAULT_BASE, help="URL dasar situs")
    ap.add_argument(
        "--only",
        default="",
        help="Comma-separated: highcharts,peta,idm,tautan,paginasi,terang",
    )
    args = ap.parse_args()

    hanya = [x.strip() for x in args.only.split(",") if x.strip()]
    if hanya:
        tak_dikenal = [x for x in hanya if x not in PEMERIKSA]
        if tak_dikenal:
            print(f"ERROR: pemeriksaan tidak dikenal: {', '.join(tak_dikenal)}")
            print("Pilihan: " + ", ".join(PEMERIKSA))
            return 2

    print("=" * 72)
    print(f"TES REGRESI MODE GELAP — Tema Huta Studio")
    print(f"Target: {args.base}")
    print("=" * 72)

    total_lulus = 0
    total_gagal = 0
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        ctx.add_init_script("try{localStorage.setItem('huta_dark','1');}catch(e){}")
        page = ctx.new_page()
        # Konteks terpisah tanpa localStorage -> mode terang (kontrol regresi).
        ctx_terang = browser.new_context(viewport={"width": 1440, "height": 900})
        page_terang = ctx_terang.new_page()
        errors = []
        page.on("pageerror", lambda e: errors.append(str(e)[:200]))
        for nama, meth in PEMERIKSA.items():
            if hanya and nama not in hanya:
                continue
            print(f"\n### Pemeriksaan: {nama.upper()}")
            halaman = page_terang if nama == "terang" else page
            pem = Pemeriksa(nama, args.base, halaman)
            getattr(pem, meth)()
            total_lulus += pem.lulus
            total_gagal += len(pem.gagal)
            if pem.gagal:
                print(f"  -> GAGAL {nama}: " + "; ".join(pem.gagal))
        browser.close()

    print("\n" + "=" * 72)
    print(f"HASIL: {total_lulus} lulus, {total_gagal} gagal")
    if errors:
        print(f"Catatan: {len(errors)} galat JS('_') terdeteksi: {errors[0]}")
    print("=" * 72)
    return 1 if total_gagal else 0


if __name__ == "__main__":
    sys.exit(main())
