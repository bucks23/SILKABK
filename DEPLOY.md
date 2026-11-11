# Deploy SILKABK (+ SPK Skrining Anak)

Dua repo, masing-masing punya `docker-compose.yml` di root dan di-deploy sebagai **stack terpisah**:

| Stack | Repo | Branch | Port host |
|---|---|---|---|
| `silkabk` | `bucks23/SILKABK` (repo ini) | `testing-deploy` | frontend **8818**, backend **8817** |
| `spk-skrining` | `UstadzApuy/spk-skrining-anak-v2` | `testing-deploy` | frontend **8810**, backend **8809** |

Keduanya tersambung lewat jaringan Docker **`uptd-network`** (dibuat stack SILKABK):
SPK membaca database SILKABK (host `silkabk-db`, user baca-saja `spk_reader`) dan menerima
login otomatis (SSO) dari SILKABK. **Deploy SILKABK lebih dulu, baru SPK.**

```
                     ┌─ silkabk.domain.id ─► :8818 frontend ─► :8817 backend ─► db (ta_bismillah)
Reverse proxy HTTPS ─┤                                                            ▲ uptd-network
                     └─ spk.domain.id ─────► :8810 frontend ─► :8809 backend ─────┘ (baca-saja)
                                                                    └──────────► db (spk_uptd)
```

- **frontend** = Nginx: hasil build Vue + meneruskan request PHP. Ini yang dituju proxy.
- **backend** = PHP-FPM (Laravel). Protokol FastCGI, **bukan HTTP**.
- **db** = PostgreSQL 18. Image-nya sudah berisi file dump; saat pertama kali otomatis di-restore.

Semua image **di-build dari repo** (tidak ada image yang di-pull dari registry selain image dasar).
Tidak ada bind mount dan tidak butuh file `.env` di repo — semua setelan lewat **environment variables**.

## Membuat kunci & secret

```bash
docker run --rm php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"   # APP_KEY (beda tiap sistem)
docker run --rm php:8.2-cli php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"                   # SSO secret
docker run --rm php:8.2-cli php -r "echo bin2hex(random_bytes(16)).PHP_EOL;"                   # password
```

Ada **dua nilai yang wajib sama** di kedua stack:

| Nilai | Stack SILKABK | Stack SPK |
|---|---|---|
| Secret SSO | `SPK_SKRINING_SSO_SECRET` | `SILKABK_SSO_SECRET` |
| Password user baca-saja | `SPK_READER_PASSWORD` | `SILKABK_DB_PASSWORD` |

## Variabel stack SILKABK

Salin ke Portainer (*Environment variables → Advanced mode*) atau ke file `.env`, lalu isi:

```env
APP_KEY=base64:...
APP_URL=https://silkabk.domain.id
DB_PASSWORD=...
SPK_SKRINING_URL=https://spk.domain.id
SPK_SKRINING_SSO_SECRET=...
SPK_READER_PASSWORD=...
```

Opsional (nilai default di kanan): `DB_DATABASE=ta_bismillah`, `DB_USERNAME=silkabk_user`,
`FRONTEND_PORT=8818`, `BACKEND_PORT=8817`, `FRONTEND_BIND=0.0.0.0`, `BACKEND_BIND=127.0.0.1`,
`SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, `UPTD_NETWORK=uptd-network`.

## Variabel stack SPK

```env
APP_KEY=base64:...
APP_URL=https://spk.domain.id
DB_PASSWORD=...
SILKABK_URL=https://silkabk.domain.id
SILKABK_SSO_SECRET=...            # = SPK_SKRINING_SSO_SECRET di SILKABK
SILKABK_DB_PASSWORD=...           # = SPK_READER_PASSWORD di SILKABK
```

Opsional: `DB_DATABASE=spk_uptd`, `DB_USERNAME=spk_user`, `FRONTEND_PORT=8810`,
`BACKEND_PORT=8809`, `FRONTEND_BIND=0.0.0.0`, `BACKEND_BIND=127.0.0.1`,
`SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, `UPTD_NETWORK=uptd-network`.

## Cara A — Portainer

Buat **dua stack**, SILKABK dulu:

1. **Stacks → Add stack**, beri nama `silkabk`
2. Build method: **Repository**
   - Repository URL: `https://github.com/bucks23/SILKABK` (fork juga boleh)
   - Repository reference: `refs/heads/testing-deploy`
   - Compose path: `docker-compose.yml`
   - Repo private → aktifkan *Authentication* (username + personal access token GitHub)
3. **Environment variables** → *Advanced mode* → tempel variabel SILKABK di atas, isi nilainya
4. **Deploy the stack**. Build pertama ±5–15 menit. Cek log container `db` (harus ada
   `[init] Selesai.`) dan `backend` (`ready to handle connections`)
5. Ulangi untuk SPK: nama `spk-skrining`, URL `https://github.com/UstadzApuy/spk-skrining-anak-v2`,
   reference `refs/heads/testing-deploy`, compose path `docker-compose.yml`, variabel SPK.
   Log `backend` harus berisi `N pasien SILKABK tersinkron ke SPK.`

Update aplikasi: buka stack → **Pull and redeploy** (centang *Re-pull image and redeploy* tidak perlu).

## Cara B — Docker CLI

```bash
git clone -b testing-deploy https://github.com/bucks23/SILKABK.git
cd SILKABK
cp .env.example .env && nano .env        # isi variabel SILKABK di atas (+ ubah APP_ENV=production, APP_DEBUG=false)
docker compose up -d --build

cd ..
git clone -b testing-deploy https://github.com/UstadzApuy/spk-skrining-anak-v2.git
cd spk-skrining-anak-v2
cp .env.example .env && nano .env        # isi variabel SPK di atas
docker compose up -d --build
```

## Reverse proxy

| Subdomain | Diteruskan ke |
|---|---|
| `silkabk.domain.id` | `http://<ip-server>:8818` |
| `spk.domain.id` | `http://<ip-server>:8810` |

**Wajib dua host berbeda** (cookie login). Teruskan header `Host`, `X-Forwarded-For`, `X-Forwarded-Proto`:

```nginx
location / {
    proxy_pass http://127.0.0.1:8818;      # 8810 untuk SPK
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

## Uji

Buka `https://silkabk.domain.id` → login → klik **SPK Skrining Anak** di sidebar
→ langsung masuk dashboard SPK tanpa login ulang.

| Role SILKABK | Di SPK |
|---|---|
| super_admin, psikolog | perawat |
| dokter | dokter |
| admin | administrator |
| orang_tua | orang_tua |
| terapis | tidak punya akses |

Setelah online: **ganti password akun bawaan** (data awal berisi akun uji).

## Masalah umum

| Gejala | Solusi |
|---|---|
| `env file .../.env not found` | Pakai `docker-compose.yml` terbaru (tidak lagi memakai `env_file`) |
| `required variable X is missing a value` | Isi variabel X di Environment variables / `.env` |
| SPK: `network uptd-network ... not found` | Deploy stack SILKABK dulu (atau `docker network create uptd-network`) |
| Backend berhenti: `APP_KEY belum diisi` | Isi `APP_KEY` |
| SPK log: `sinkron SILKABK gagal` | `SILKABK_DB_PASSWORD` SPK harus = `SPK_READER_PASSWORD` SILKABK; stack SILKABK harus jalan |
| Klik menu SPK → "Tautan login tidak valid" | Secret SSO kedua stack berbeda, atau jam server tidak sinkron |
| Login satu sistem membuat logout sistem lain | Pakai dua subdomain berbeda |
| Ubah `DB_PASSWORD` tapi backend gagal konek | Password DB hanya dipakai saat volume pertama dibuat. Ubah lewat `ALTER ROLE`, atau hapus volume `db-data` lalu deploy ulang (data di-restore ulang dari dump) |
