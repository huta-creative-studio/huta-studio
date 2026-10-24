/*! Huta Studio — mesin mode gelap (peta Leaflet).
 *
 * Bertugas menjaga agar isi visual yang dibuat JavaScript (hanya peta Leaflet
 * yang relevan di tema ini) ikut mengikuti mode gelap. Grafik Highcharts
 * handled sepenuhnya lewat CSS, lihat assets/css/huta-v2.css.
 *
 * Perilaku:
 *  - Menyuntikkan lapisan "Gelap" (CartoDB DarkMatter) ke daftar lapisan dasar.
 *  - Saat mode gelap aktif, lapisan gelap otomatis dipilih.
 *  - Saat mode gelap dimatikan, lapisan dasar sebelumnya dipulihkan.
 *  - Tombol "Lapis" milik Leaflet tetap berfungsi normal: pilihan pengguna
 *    selalu dihormati, sinkronisasi radio button dilakukan agar tidak bertabrakan.
 *
 * Dipakai oleh widget peta tema:
 *    var baseLayers = getBaseLayers(peta, token, jenis_peta);
 *    baseLayers['Gelap'] = L.tileLayer.provider('CartoDB.DarkMatter', {...});
 *    var kontrol = L.control.layers(baseLayers, null, {...}).addTo(peta);
 *    window.hvDaftarPeta(peta, kontrol, baseLayers);
 */
(function (window, document) {
    'use strict';

    var NAMA_LAYER_GELAP = 'Gelap';
    var petaTerdaftar = [];

    function modeGelapAktif() {
        return document.documentElement.classList.contains('dark');
    }

    /* Radio button pada kontrol "Lapis" menyimpan nama lapisan sebagai teks. */
    function namaDariRadio(radio) {
        var label = radio.closest ? radio.closest('label') : null;
        return label ? label.textContent.trim() : null;
    }

    function radioTerpilih(kontrol) {
        if (!kontrol || !kontrol._container) return null;
        var radios = kontrol._container.querySelectorAll('input.leaflet-control-layers-selector');
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) return namaDariRadio(radios[i]);
        }
        return null;
    }

    /* Selaraskan tampilan kontrol "Lapis" dengan lapisan yang benar-benar aktif. */
    function selaraskanKontrol(kontrol, namaAktif) {
        if (!kontrol || !kontrol._container) return;
        var radios = kontrol._container.querySelectorAll('input.leaflet-control-layers-selector');
        for (var i = 0; i < radios.length; i++) {
            radios[i].checked = (namaDariRadio(radios[i]) === namaAktif);
        }
    }

    function lapisanAktif(peta, base) {
        var hasil = null;
        for (var nama in base) {
            if (!Object.prototype.hasOwnProperty.call(base, nama)) continue;
            if (nama === NAMA_LAYER_GELAP) continue;
            if (peta.hasLayer(base[nama])) hasil = nama;
        }
        return hasil;
    }

    function terapkanPeta() {
        var gelap = modeGelapAktif();

        for (var i = 0; i < petaTerdaftar.length; i++) {
            var t = petaTerdaftar[i];
            var peta = t.peta;
            var base = t.base;
            if (!peta || !base) continue;

            var layerGelap = base[NAMA_LAYER_GELAP];
            if (!layerGelap) continue;

            if (gelap) {
                if (!t.namaAwal) t.namaAwal = lapisanAktif(peta, base);
                if (peta.hasLayer(layerGelap)) continue;
                // Lepas semua lapisan dasar yang sedang tampil (mis. Mapbox GL).
                for (var nama in base) {
                    if (!Object.prototype.hasOwnProperty.call(base, nama)) continue;
                    if (nama === NAMA_LAYER_GELAP) continue;
                    if (peta.hasLayer(base[nama])) peta.removeLayer(base[nama]);
                }
                peta.addLayer(layerGelap);
                selaraskanKontrol(t.kontrol, NAMA_LAYER_GELAP);
            } else {
                if (!peta.hasLayer(layerGelap)) continue;
                peta.removeLayer(layerGelap);
                if (t.namaAwal && base[t.namaAwal] && !peta.hasLayer(base[t.namaAwal])) {
                    peta.addLayer(base[t.namaAwal]);
                }
                selaraskanKontrol(t.kontrol, t.namaAwal);
            }
        }
    }

    /* Dipanggil widget peta tema setelah peta & kontrol lapis dibuat. */
    function daftarkanPeta(peta, kontrol, base) {
        if (!peta) return;
        if (!base) base = {};
        if (!base[NAMA_LAYER_GELAP] && window.L && window.L.tileLayer && window.L.tileLayer.provider) {
            try {
                base[NAMA_LAYER_GELAP] = window.L.tileLayer.provider('CartoDB.DarkMatter', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' +
                        ' &copy; <a href="https://carto.com/attributions">CARTO</a>'
                });
            } catch (e) {
                base[NAMA_LAYER_GELAP] = null;
            }
        }
        petaTerdaftar.push({ peta: peta, kontrol: kontrol, base: base, namaAwal: null });
        terapkanPeta();
    }

    window.hvDaftarPeta = daftarkanPeta;
    window.hvTerapkanModeGelap = terapkanPeta;
})(window, document);
