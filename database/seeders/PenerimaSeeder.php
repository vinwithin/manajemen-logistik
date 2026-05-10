<?php

namespace Database\Seeders;

use App\Models\Tujuan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PenerimaSeeder extends Seeder
{
    public function run(): void
    {
        // Buat tujuan jika belum ada
        $kerinci = Tujuan::firstOrCreate(['nama' => 'Kerinci'], ['is_aktif' => true]);
        $jambi = Tujuan::firstOrCreate(['nama' => 'Jambi'], ['is_aktif' => true]);
        $bangko = Tujuan::firstOrCreate(['nama' => 'Bangko'], ['is_aktif' => true]);
        $bungo = Tujuan::firstOrCreate(['nama' => 'Bungo'], ['is_aktif' => true]);

        // ── KERINCI ──────────────────────────────────────────────
        // Kolom: nama, oa (ongkos_angkut), bongkar (ongkos_bongkar)
        $kerinci_data = [

        ];

        // ── JAMBI ────────────────────────────────────────────────
        $jambi_data = [
            ['MUSTAPA B',       75, 0],
            ['MUSTAPA',         75, 0],
            ['SUMIATI B',       80, 0],
            ['M IDRUS',         65, 0],
            ['H FAROUK',        65, 0],
            ['INDRIA MAYESTI B', 60, 0],
            ['INDRIA MAYESTI A', 60, 0],
            ['TAUFIK HIMAWAN',  85, 0],
            ['EVI SYAHRUL',     75, 0],
            ['JUANDA',          75, 0],
            ['ISMAIL',          75, 0],
            ['AZIZ M',          85, 0],
            ['SULASIH',         85, 0],
            ['SUSILAWATI',      80, 0],
            ['CV.SEBELAS FARM B', 80, 0],
            ['CV.SEBELAS FARM', 80, 0],
            ['EVELYN EFRILLA B', 85, 0],
            ['PRAYITNO',        85, 0],
            ['IBRAHIM',         75, 0],
            ['FAHRUDIN',        80, 0],
            ['ENY',             80, 0],
            ['ASRAL MUBARAK',   70, 0],
            ['SUTONO',          85, 0],
            ['ADAM',            90, 0],
            ['ZADIGGAH',        85, 0],
            ['SUHARDI',         65, 0],
            ['TEGUH BUDI',      80, 0],
            ['BUDI',            85, 0],
            ['TOYIB',           75, 0],
            ['SUKIMUN',         90, 0],
            ['ARMY SEVTIANSYAH', 80, 0],
            ['AMELIA DEBORA',   80, 0],
            ['LENI',            65, 0],
            ['SAJIDIN',         75, 0],
            ['KOKO WIJAYA',     80, 0],
            ['MUHIBUN',         60, 0],
            ['ARUNIKA SARI',    80, 0],
            ['ASLIN',           80, 0],
            ['INDOBANDRI',      80, 0],
            ['ASLIN B',         80, 0],
            ['ASLIN C',         80, 0],
            ['ASNAWI',          75, 0],
            ['DEPI PITRIANI',   75, 0],
            ['SOPAN SOPIYAN',   75, 0],
            ['INA',             65, 0],
            ['HENDRI',          75, 0],
            ['RIZKY',           75, 0],
            ['HERVINA MAYASARI', 80, 0],
            ['MAWARDI',         85, 0],
            ['MARTANELEVEN',    80, 0],
            ['MARTANELEVEN B',  80, 0],
            ['CV ALFATH JITU',  70, 0],
            ['ENNY',            65, 0],
            ['RISMA RIKA',      75, 0],
            ['SUMIATI',         80, 0],
            ['CV ASIA B',       80, 0],
            ['TANDRI SAPUTRA',  80, 0],
            ['CV ASIA',         80, 0],
            ['NOVI',            70, 0],
            ['RIZKY ALHAJJ',    75, 0],
            ['BURHANUDIN',      75, 0],
            ['NIKE DEPIANTI',   75, 0],
            ['M FATHONY',       80, 0],
            ['SUHARDI B',       65, 0],
            ['SUHENDRO',        80, 0],
            ['YADIMAN',         80, 0],
            ['AHMAD FADLI',     80, 0],
            ['JAP TING HUAT',   65, 0],
            ['LILY',            80, 0],
            ['LINDA GHOZALI',   80, 0],
            ['TJOH HUA',        65, 0],
            ['EVELYN EFRILLA',  85, 0],
            ['JONI HALIM',      65, 0],
            ['ASRAI MUBARAK',   70, 0],
            ['BAHARUDIN',       75, 0],
            ['BUDI SATRIANA',   85, 0],
            ['ALAN KRISTANTI',  80, 0],
            ['YULI TRIYADI',    80, 0],
            ['FATHONY B',       80, 0],
        ];

        // ── BANGKO ───────────────────────────────────────────────
        // OA = ongkos_angkut, BONGKAR = ongkos_bongkar
        $bangko_data = [
            ['RUSMAN (CH)',                  80, 20],
            ['HERLYANA (CH)',                80, 20],
            ['AMRIAL (CH)',                  80, 20],
            ['RIANA (CH)',                   80, 20],
            ['DIAN EKAWATI (CH)',            80, 20],
            ['ALDIAN YONATA (CH)',           80, 20],
            ['ZEN SAJORA (CH)',              80, 20],
            ['PIN (CH)',                     80, 20],
            ['DEWI ANGRAINI (CH)',           80, 20],
            ['ERRYKA LETHYANA F. (CH)',      80, 20],
            ['BERLI APRILDO (CH)',           80, 20],
            ['BARA SASONGKO (CH)',           90, 20],
            ['ALIMAN (CH)',                  90, 20],
            ['HALIMAH (CH)',                 90, 20],
            ['CANDRA BUANA HASIBUAN (CH)',  100, 20],
            ['HZ. USMAN (CH)',             100, 20],
            ['ERIKA',                       100, 20],
            ['ZUMROAINI (CH)',             130, 20],
            ['ZURAIDA (CH)',               130, 20],
            ['AHMAD KANI HASBULLAH (CH)',  130, 20],
        ];

        // ── BUNGO ────────────────────────────────────────────────
        $bungo_data = [
            ['Binti Alfiah',                100, 20],
            ['Bisri mustopa',               100, 20],
            ['Betty Florund sP (CH)',       100, 20],
            ['Fauziah (CH)',                100, 20],
            ['Harti (CH)',                  100, 20],
            ['Ipni Putiah (CH)',            100, 20],
            ['Nur Khamim (CH)',             100, 20],
            ['Suyanto (CH)',                100, 20],
            ['Sony Listiawanto (CH)',       100, 20],
            ['Muh Mahfud Efendi (CH)',      100, 20],
            ['Juwan Apriyadi (CH)',         100, 20],
            ['Seh Ramli (CH)',              100, 20],
            ['Abdul kadir (CH)',            100, 20],
            ['Ansri B (CH)',                100, 20],
            ['Helmi (CH)',                  100, 20],
            ['Rima Yudisti (CH)',           100, 20],
            ['Titik Fatonh',                100, 20],
            ['Jasmi',                       100, 20],
            ['Intan Widya Sari',            100, 20],
            ['Arwin Rosyadi (CH)',          100, 20],
            ['Darliansyah',                 100, 20],
            ['Widiyanto',                   100, 20],
            ['H Abasri',                    100, 20],
            ['Murtado (CH)',                100, 20],
            ['Siti Rohana (CH)',            100, 20],
            ['Tri aprida BR, Purba (CH)',   100, 20],
            ['Muslih (CH)',                 100, 20],
            ['Sulaiman',                    100, 20],
            ['Rohmin (CH)',                 100, 20],
            ['Sheny ronauli sinaturi',      100, 20],
            ['Sunoto',                       85, 20],
            ['Andung',                       85, 20],
            ['Mulfiah',                      85, 20],
            ['Mulyadi',                      85, 20],
            ['Nur asnaim',                   85, 20],
            ['Al Tamsin',                    85, 20],
            ['Julali',                       85, 20],
            ['MP Condro',                    85, 20],
            ['M Zainal Abidin',              85, 20],
            ['Suryono',                      85, 20],
            ['Yusuf Maezuki',                85, 20],
            ['Tukiran',                      85, 20],
            ['Setiyo Harwanto',              85, 20],
            ['Sutomo',                       85, 20],
            ['Kusdiyanto',                   85, 20],
            ['Indah Sri',                    85, 20],
            ['M Ali',                        85, 20],
            ['Sri Utami',                    85, 20],
            ['Suyitno (CH)',                 85, 20],
            ['Deselina CH',                  85, 20],
            ['Sodik Akbar CH',               85, 20],
            ['Setiyo Cris CH',               85, 20],
            ['Sufamali CH',                  85, 20],
            ['Desi Loskari CH',              85, 20],
            ['M Nur Alamsyah',               85, 20],
            ['Trisna widi',                  85, 20],
            ['Purwanto',                     85, 20],
            ['Cariri',                       85, 20],
            ['M Catur',                      85, 20],
            ['Etik Indarti',                 85, 20],
            ['Mori Idayani',                 85, 20],
            ['Selvi Ardyanti',               85, 20],
            ['Wawan M',                      85, 20],
            ['Endah Nuryani',                85, 20],
            ['Sunarto',                      85, 20],
            ['Sunarto',                      85, 20],
            ['Agus Soleh',                   85, 20],
        ];

        $now = now();

        // Insert Kerinci
        foreach ($kerinci_data as [$nama, $oa, $bongkar]) {
            DB::table('penerima')->insert([
                'nama' => $nama,
                'tujuan_id' => $kerinci->id,
                'ongkos_angkut' => $oa,
                'ongkos_bongkar' => $bongkar,
                'is_aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Insert Bangko
        foreach ($bangko_data as [$nama, $oa, $bongkar]) {
            DB::table('penerima')->insert([
                'nama' => $nama,
                'tujuan_id' => $bangko->id,
                'ongkos_angkut' => $oa,
                'ongkos_bongkar' => $bongkar,
                'is_aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Insert Bungo
        foreach ($bungo_data as [$nama, $oa, $bongkar]) {
            DB::table('penerima')->insert([
                'nama' => $nama,
                'tujuan_id' => $bungo->id,
                'ongkos_angkut' => $oa,
                'ongkos_bongkar' => $bongkar,
                'is_aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('✓ Kerinci : '.count($kerinci_data).' penerima');
        $this->command->info('✓ Bangko  : '.count($bangko_data).' penerima');
        $this->command->info('✓ Bungo   : '.count($bungo_data).' penerima');
        $this->command->info('✓ Total   : '.(count($kerinci_data) + count($bangko_data) + count($bungo_data)).' penerima');
    }
}
