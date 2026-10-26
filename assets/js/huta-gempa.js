/* Huta Studio — pemuat data halaman Gempa (BMKG)
   Sebelumnya loader tidak pernah ada: skeleton "Memuat data gempa BMKG"
   tampil selamanya. Skrip ini mengambil data langsung dari browser ke
   data.bmkg.go.id (CORS: access-control-allow-origin: *) lalu mengisi
   markup [data-huta-earthquake-page] yang sudah ada di blade.
   Ditambahkan 2026-10-26. */
(function () {
    'use strict';

    /* Tombol "Cetak" (halaman cuaca & gempa): sebelumnya tidak ada handler. */
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-huta-print]') : null;
        if (btn) {
            e.preventDefault();
            window.print();
        }
    });

    function init() {
        var root = document.querySelector('[data-huta-earthquake-page]');
        if (!root) {
            return;
        }

        var BMKG = 'https://data.bmkg.go.id/DataMKG/TEWS/';
        var $ = function (sel) { return root.querySelector(sel); };
        var $$ = function (sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); };
        var set = function (sel, val) {
            var el = $(sel);
            if (el && val != null && String(val) !== '') {
                el.textContent = val;
            }
        };
        var show = function (sel, on) {
            var el = $(sel);
            if (el) {
                el.hidden = !on;
            }
        };

        function fetchJSON(url) {
            var ctl = window.AbortController ? new AbortController() : null;
            var timer = ctl ? setTimeout(function () { ctl.abort(); }, 15000) : null;
            return fetch(url, {
                cache: 'no-store',
                credentials: 'omit',
                signal: ctl ? ctl.signal : undefined
            }).then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status + ' ' + url);
                }
                return res.json();
            }).finally(function () {
                if (timer) {
                    clearTimeout(timer);
                }
            });
        }

        function gempaList(json) {
            var list = json && json.Infogempa && json.Infogempa.gempa;
            if (!list) {
                return [];
            }
            return Array.isArray(list) ? list : [list];
        }

        function parseCoord(str) {
            if (!str) {
                return null;
            }
            var p = String(str).split(',');
            if (p.length !== 2) {
                return null;
            }
            var lat = parseFloat(p[0]);
            var lng = parseFloat(p[1]);
            return isFinite(lat) && isFinite(lng) ? [lat, lng] : null;
        }

        function jarakKm(a, b) {
            var R = 6371;
            var rad = Math.PI / 180;
            var dLat = (b[0] - a[0]) * rad;
            var dLng = (b[1] - a[1]) * rad;
            var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(a[0] * rad) * Math.cos(b[0] * rad) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
        }

        function formatJarak(km) {
            if (km < 1) {
                return '< 1 km';
            }
            try {
                return Math.round(km).toLocaleString('id-ID') + ' km';
            } catch (err) {
                return Math.round(km) + ' km';
            }
        }

        function waktu(g) {
            return [g.Tanggal, g.Jam].filter(Boolean).join(' ');
        }

        function potonganAwal(teks) {
            return String(teks || '').split(',')[0].replace(/\s*\([^)]*\)/g, '').trim();
        }

        function lokasiSingkat(w) {
            return String(w || '-')
                .replace(/^Pusat gempa berada di\s*/i, '')
                .replace(/^Berlokasi di\s*/i, '')
                .trim();
        }

        /* Koordinat desa untuk kolom "Jarak dari ..." (data-lat/data-lng di blade) */
        var desaCoord = parseCoord(
            (root.getAttribute('data-lat') || '') + ',' + (root.getAttribute('data-lng') || '')
        );

        function jarakDariDesa(g) {
            var epi = parseCoord(g.Coordinates);
            if (!desaCoord || !epi) {
                return '';
            }
            return formatJarak(jarakKm(desaCoord, epi));
        }

        function buatBaris(labels, values) {
            var tr = document.createElement('tr');
            for (var i = 0; i < labels.length; i++) {
                var td = document.createElement('td');
                td.setAttribute('data-label', labels[i]);
                td.textContent = (values[i] == null || values[i] === '') ? '-' : values[i];
                tr.appendChild(td);
            }
            return tr;
        }

        function pasangShakemap(g) {
            var link = $('[data-q-shakemap-link]');
            var img = $('[data-q-shakemap]');
            var kosong = $('[data-q-shakemap-empty]');
            if (!link || !img) {
                show('[data-q-shakemap-empty]', true);
                return;
            }
            var nama = g.Shakemap || 'shakemap.jpg';
            var url = /^https?:/i.test(nama) ? nama : BMKG + nama;
            img.onerror = function () {
                link.hidden = true;
                img.removeAttribute('src');
                if (kosong) {
                    kosong.hidden = false;
                }
            };
            img.onload = function () {
                link.hidden = false;
                if (kosong) {
                    kosong.hidden = true;
                }
            };
            link.href = url;
            img.src = url;
        }

        function isiTabel(panelSel, rowsSel, emptySel, list, jenis) {
            var panel = $(panelSel);
            if (!panel) {
                return;
            }
            var tbody = panel.querySelector(rowsSel);
            var kosong = panel.querySelector(emptySel);
            var wrap = panel.querySelector('.huta-table-scroll');
            var ths = Array.prototype.slice.call(panel.querySelectorAll('thead th'));
            var labelJarak = ths[4] ? ths[4].textContent.trim() : 'Jarak';
            var labelAkhir = ths[5] ? ths[5].textContent.trim() : (jenis === 'm5' ? 'Potensi' : 'Dirasakan');
            if (tbody) {
                tbody.innerHTML = '';
            }
            (list || []).forEach(function (g) {
                if (!tbody) {
                    return;
                }
                var akhir = jenis === 'm5' ? (g.Potensi || '-') : (g.Dirasakan || '-');
                tbody.appendChild(buatBaris(
                    ['Waktu', 'Magnitudo', 'Kedalaman', 'Lokasi', labelJarak, labelAkhir],
                    [waktu(g), g.Magnitude, g.Kedalaman, g.Wilayah, jarakDariDesa(g) || '-', akhir]
                ));
            });
            var kosongIsi = !list || !list.length;
            if (wrap) {
                wrap.hidden = kosongIsi;
            }
            if (kosong) {
                kosong.hidden = !kosongIsi;
            }
        }

        /* Tab riwayat: Gempa Dirasakan / Gempa M 5,0+ */
        $$('.huta-earthquake-tabs [data-q-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-q-tab');
                $$('.huta-earthquake-tabs [data-q-tab]').forEach(function (b) {
                    var aktif = b === btn;
                    b.classList.toggle('is-active', aktif);
                    b.setAttribute('aria-selected', aktif ? 'true' : 'false');
                });
                $$('[data-q-panel]').forEach(function (p) {
                    var aktif = p.getAttribute('data-q-panel') === key;
                    p.classList.toggle('is-active', aktif);
                    p.hidden = !aktif;
                });
            });
        });

        var endpoint = root.getAttribute('data-endpoint') || (BMKG + 'autogempa.json');

        Promise.all([
            fetchJSON(endpoint),
            fetchJSON(BMKG + 'gempadirasakan.json').catch(function () { return null; }),
            fetchJSON(BMKG + 'gempaterkini.json').catch(function () { return null; })
        ]).then(function (hasil) {
            var utama = hasil[0];
            var g = utama && utama.Infogempa && utama.Infogempa.gempa;
            if (!g) {
                throw new Error('format data BMKG tidak dikenal');
            }

            set('[data-q-mag]', g.Magnitude);
            set('[data-q-depth]', g.Kedalaman);
            set('[data-q-region-short]', lokasiSingkat(g.Wilayah));
            set('[data-q-date]', g.Tanggal);
            set('[data-q-time]', g.Jam);
            set('[data-q-felt-short]', potonganAwal(g.Dirasakan) || 'Belum ada laporan');
            set('[data-q-region]', g.Wilayah);
            set('[data-q-coordinates]', [g.Lintang, g.Bujur].filter(Boolean).join(', ') || g.Coordinates);
            set('[data-q-potential]', g.Potensi || '-');
            set('[data-q-felt]', g.Dirasakan || 'Belum ada laporan dirasakan');

            pasangShakemap(g);

            var epi = parseCoord(g.Coordinates);
            if (desaCoord && epi) {
                show('[data-q-local]', true);
                set('[data-q-village]', root.getAttribute('data-village'));
                set('[data-q-distance]', formatJarak(jarakKm(desaCoord, epi)));
            }

            isiTabel('[data-q-panel="felt"]', '[data-q-felt-rows]', '[data-q-felt-empty]', gempaList(hasil[1]), 'felt');
            isiTabel('[data-q-panel="m5"]', '[data-q-m5-rows]', '[data-q-m5-empty]', gempaList(hasil[2]), 'm5');

            var catatan = 'Diperbarui otomatis ' + new Date().toLocaleString('id-ID') + '.';
            set('[data-q-cache-note]', catatan);

            show('[data-quake-loading]', false);
            show('[data-quake-content]', true);
        }).catch(function (err) {
            if (window.console && console.warn) {
                console.warn('[huta-gempa] ' + err);
            }
            show('[data-quake-loading]', false);
            show('[data-quake-error]', true);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
