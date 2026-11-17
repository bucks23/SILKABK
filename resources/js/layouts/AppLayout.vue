<template>
  <div class="app-shell bg-gray-50 flex">

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 flex flex-col transition-transform duration-300',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
      style="padding-top: env(safe-area-inset-top, 0)"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 flex-shrink-0">
        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center flex-shrink-0">
          <span class="text-white font-bold text-sm">BK</span>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-800 leading-tight truncate">UPTD BLKABK</p>
          <p class="text-xs text-gray-400">Balikpapan</p>
        </div>
        <!-- Tombol tutup sidebar di mobile -->
        <button
          class="ml-auto lg:hidden p-1.5 rounded-lg hover:bg-gray-100 text-gray-400"
          @click="sidebarOpen = false"
          aria-label="Tutup menu"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">

        <NavItem :href="route('dashboard')" label="Dashboard" icon="home" />

        <!-- ─── Super Admin ───────────────────────────────── -->
        <template v-if="isSuperAdmin">
          <NavGroup label="Sistem" />
          <NavItem :href="route('users.index')" label="Kelola Pengguna" />
          <NavItem :href="route('roles.index')" label="Kelola Role" />

          <NavGroup label="Manajemen Pasien" />
          <NavItem :href="route('patients.index')"       label="Data Pasien" />
          <NavItem :href="route('registrations.index')"  label="Pendaftaran Layanan" />
          <NavItem :href="route('registrations.create')" label="+ Buat Pendaftaran" />

          <NavGroup label="Operasional" />
          <NavItem :href="route('payments.index')"   label="Pembayaran" />
          <NavItem :href="route('schedules.index')"  label="Jadwal Terapi" />

          <NavGroup label="Pengaturan" />
          <NavItem :href="route('therapy-types.index')" label="Kelola Biaya Layanan" />

          <NavGroup label="Laporan" />
          <NavItem :href="route('reports.index')" label="Laporan & Statistik" />

          <NavGroup label="Rekam Medis" />
          <NavItem :href="route('doctor.medical-records')"       label="Rekam Medis (Dokter)" />
          <NavItem :href="route('doctor.diagnoses.index')"       label="Diagnosa Penyakit" />
          <NavItem :href="route('psychologist.medical-records')" label="Asesmen (Psikolog)" />
          <NavItem :href="route('psychologist.medical-records.create')" label="+ Input Asesmen" />
        </template>

        <!-- ─── Admin ──────────────────────────────────────── -->
        <template v-if="isAdmin">
          <NavGroup label="Manajemen Pasien" />
          <NavItem :href="route('patients.index')"       label="Data Pasien" />
          <NavItem :href="route('registrations.index')"  label="Pendaftaran Layanan" />
          <NavItem :href="route('registrations.create')" label="+ Buat Pendaftaran" />

          <NavGroup label="Laporan & Operasional" />
          <NavItem :href="route('payments.index')"      label="Laporan Pembayaran" />
          <NavItem :href="route('schedules.index')"     label="Kelola Jadwal Terapi" />
          <NavItem :href="route('therapy-types.index')" label="Kelola Biaya Layanan" />
          <NavItem :href="route('reports.index')"       label="Laporan & Statistik" />
        </template>

        <!-- ─── Orang Tua ──────────────────────────────────── -->
        <template v-if="isOrangTua">
          <NavGroup label="Anak Saya" />
          <NavItem :href="route('parent.children.create')" label="+ Tambah Anak" />
          <NavItem :href="route('parent.register')"        label="Daftar Layanan Baru" />
          <NavItem :href="route('parent.registrations')"   label="Status Pendaftaran" />

          <NavGroup label="Riwayat" />
          <NavItem :href="route('parent.schedules')"  label="Jadwal Terapi" />
          <NavItem :href="route('parent.payments')"   label="Riwayat Pembayaran" />
          <NavItem :href="route('parent.diagnoses')"  label="Riwayat Diagnosa" />

          <NavGroup label="Skrining" />
          <NavItem :href="route('parent.sdq.index')"  label="Kuesioner SDQ" />
          <NavItem :href="route('parent.sdq.create')" label="+ Isi Kuesioner SDQ" />
        </template>

        <!-- ─── Terapis ───────────────────────────────────── -->
        <template v-if="isTerapis">
          <NavGroup label="Tugas Saya" />
          <NavItem :href="route('therapist.schedules')" label="Jadwal Terapi Saya" />
        </template>

        <!-- ─── Dokter ────────────────────────────────────── -->
        <template v-if="isDokter">
          <NavGroup label="Rekam Medis" />
          <NavItem :href="route('doctor.medical-records')"        label="Daftar Rekam Medis" />
          <NavItem :href="route('doctor.medical-records.create')" label="Input Rekam Medis Baru" />
          <NavGroup label="Diagnosa" />
          <NavItem :href="route('doctor.diagnoses.index')"  label="Daftar Diagnosa" />
          <NavItem :href="route('doctor.diagnoses.create')" label="+ Diagnosa Baru" />
          <NavGroup label="Asesmen Psikologi" />
          <NavItem :href="route('psychologist.medical-records')"        label="Daftar Asesmen" />
          <NavItem :href="route('psychologist.medical-records.create')" label="+ Input Asesmen" />
        </template>

        <!-- ─── Psikolog ───────────────────────────────────── -->
        <template v-if="isPsikolog">
          <NavGroup label="Asesmen & KPSP" />
          <NavItem :href="route('psychologist.medical-records')"        label="Daftar Asesmen" />
          <NavItem :href="route('psychologist.medical-records.create')" label="Input Asesmen Baru" />
        </template>

        <!-- ─── Sistem Lain (semua role) ──────────────────── -->
        <NavGroup label="Sistem Lain" />
        <NavItem :href="route('spk-skrining')" label="SPK Skrining Anak" />

      </nav>

      <!-- User Info + Logout -->
      <div class="px-4 py-4 border-t border-gray-100 flex-shrink-0"
        style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
        <Link :href="route('profile.edit')"
          class="flex items-center gap-3 mb-3 px-2 py-2 rounded-xl hover:bg-gray-50 transition-colors group">
          <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0">
            <span class="text-white font-semibold text-xs uppercase">
              {{ user?.name?.charAt(0) ?? 'U' }}
            </span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-800 truncate group-hover:text-blue-600 transition-colors">{{ user?.name }}</p>
            <p class="text-xs text-gray-400 truncate capitalize">{{ user?.role?.replace('_', ' ') }}</p>
          </div>
          <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </Link>
        <button
          @click="logout"
          class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 rounded-lg hover:bg-red-50 transition-colors"
        >
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Keluar
        </button>
      </div>
    </aside>

    <!-- Overlay mobile — tap untuk tutup -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-20 bg-black/40 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Main Content -->
    <div class="flex-1 lg:ml-64 flex flex-col"
      style="min-height: 100dvh; min-height: 100vh">

      <!-- Topbar -->
      <header class="sticky top-0 z-10 bg-white border-b border-gray-200 flex items-center gap-3"
        style="padding: 0.75rem 1rem; padding-top: max(0.75rem, env(safe-area-inset-top))">
        <button
          class="lg:hidden p-2 -ml-1 rounded-lg hover:bg-gray-100 text-gray-600 flex-shrink-0"
          style="min-height: 44px; min-width: 44px"
          @click="sidebarOpen = !sidebarOpen"
          aria-label="Buka menu"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <h1 class="text-base font-semibold text-gray-800 flex-1 truncate">{{ title }}</h1>
      </header>

      <!-- Flash Messages -->
      <div v-if="flashSuccess" class="px-4 pt-4 lg:px-6">
        <div class="flex items-start gap-3 bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">
          <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span>{{ flashSuccess }}</span>
        </div>
      </div>
      <div v-if="flashError" class="px-4 pt-4 lg:px-6">
        <div class="flex items-start gap-3 bg-red-50 text-red-800 border border-red-200 rounded-xl px-4 py-3 text-sm">
          <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
          <span>{{ flashError }}</span>
        </div>
      </div>

      <!-- Page Content -->
      <main class="flex-1 p-4 lg:p-6"
        style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom))">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useAuth } from '@/composables/useAuth'
import { useFlash } from '@/composables/useFlash'
import NavItem from '@/components/NavItem.vue'
import NavGroup from '@/components/NavGroup.vue'

defineProps({
  title: { type: String, default: 'Dashboard' },
})

const { user, isSuperAdmin, isAdmin, isOrangTua, isTerapis, isDokter, isPsikolog } = useAuth()
const { success: flashSuccess, error: flashError } = useFlash()

const sidebarOpen = ref(false)

// Tutup sidebar otomatis saat Inertia berpindah halaman (mobile)
let removeListener
onMounted(() => {
  removeListener = router.on('navigate', () => {
    sidebarOpen.value = false
  })
})
onUnmounted(() => {
  if (removeListener) removeListener()
})

function logout() {
  router.post('/logout')
}
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
  min-height: 100dvh;
}
</style>
