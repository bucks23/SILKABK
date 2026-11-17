<template>
  <AppLayout title="Kelola Role">
    <div class="max-w-2xl space-y-5">

      <!-- Header -->
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold text-gray-800">Kelola Role</h2>
          <p class="text-sm text-gray-500 mt-0.5">Daftar role pengguna sistem</p>
        </div>
        <div class="flex gap-2 text-sm">
          <span class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700">
            <strong>{{ summary.total_roles }}</strong> role
          </span>
          <span class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700">
            <strong>{{ summary.total_users }}</strong> pengguna
          </span>
        </div>
      </div>

      <!-- Tabel Role -->
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
              <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Nama Role</th>
              <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Slug</th>
              <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Pengguna</th>
              <th class="px-5 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">

              <!-- Edit inline nama -->
              <td class="px-5 py-3">
                <template v-if="editingId === role.id">
                  <input v-model="editName" type="text" autofocus
                    class="w-full px-3 py-1.5 text-sm border border-blue-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @keyup.enter="saveEdit(role)"
                    @keyup.escape="cancelEdit" />
                  <p v-if="editError" class="text-xs text-red-500 mt-1">{{ editError }}</p>
                </template>
                <span v-else class="font-medium text-gray-800">{{ role.name }}</span>
              </td>

              <td class="px-5 py-3">
                <code class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-lg">{{ role.slug }}</code>
                <span v-if="systemSlugs.includes(role.slug)"
                  class="ml-2 text-xs text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">sistem</span>
              </td>

              <td class="px-5 py-3 text-center">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold"
                  :class="role.users_count > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'">
                  {{ role.users_count }}
                </span>
              </td>

              <td class="px-5 py-3">
                <div class="flex items-center justify-end gap-2">
                  <!-- Simpan / Batal edit -->
                  <template v-if="editingId === role.id">
                    <button @click="saveEdit(role)"
                      class="text-xs font-medium text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-50">
                      Simpan
                    </button>
                    <button @click="cancelEdit"
                      class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1 rounded hover:bg-gray-100">
                      Batal
                    </button>
                  </template>
                  <!-- Edit nama -->
                  <button v-else @click="startEdit(role)"
                    class="text-xs font-medium text-gray-600 hover:text-gray-800 px-2 py-1 rounded hover:bg-gray-100">
                    Edit Nama
                  </button>
                  <!-- Hapus (hanya non-sistem) -->
                  <button v-if="!systemSlugs.includes(role.slug) && role.users_count === 0"
                    @click="destroy(role)"
                    class="text-xs font-medium text-red-600 hover:text-red-800 px-2 py-1 rounded hover:bg-red-50">
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
          </table>
        </div>
      </div>

      <!-- Form tambah role baru -->
      <div class="bg-white rounded-2xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Tambah Role Baru</h3>
        <form @submit.prevent="store" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Nama Role <span class="text-red-500">*</span>
              </label>
              <input v-model="newForm.name" type="text"
                class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="newForm.errors.name ? 'border-red-400' : 'border-gray-300'"
                placeholder="cth: Perawat" />
              <p v-if="newForm.errors.name" class="mt-1 text-xs text-red-500">{{ newForm.errors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Slug <span class="text-red-500">*</span>
                <span class="text-gray-400 font-normal text-xs ml-1">(huruf_kecil_underscore)</span>
              </label>
              <input v-model="newForm.slug" type="text"
                class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                :class="newForm.errors.slug ? 'border-red-400' : 'border-gray-300'"
                placeholder="cth: perawat" />
              <p v-if="newForm.errors.slug" class="mt-1 text-xs text-red-500">{{ newForm.errors.slug }}</p>
            </div>
          </div>

          <div class="flex justify-end">
            <button type="submit" :disabled="newForm.processing"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors disabled:opacity-50">
              {{ newForm.processing ? 'Menyimpan...' : 'Tambah Role' }}
            </button>
          </div>
        </form>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  roles:   { type: Array,  default: () => [] },
  summary: { type: Object, default: () => ({ total_roles: 0, total_users: 0 }) },
})

const systemSlugs = ['super_admin', 'admin', 'orang_tua', 'terapis', 'dokter', 'psikolog']

// ─── Tambah Role ──────────────────────────────────────────────
const newForm = useForm({ name: '', slug: '' })

function store() {
  newForm.post(route('roles.store'), {
    onSuccess: () => newForm.reset(),
  })
}

// ─── Edit Nama Role ───────────────────────────────────────────
const editingId = ref(null)
const editName  = ref('')
const editError = ref('')

function startEdit(role) {
  editingId.value = role.id
  editName.value  = role.name
  editError.value = ''
}

function cancelEdit() {
  editingId.value = null
  editName.value  = ''
  editError.value = ''
}

function saveEdit(role) {
  if (!editName.value.trim()) {
    editError.value = 'Nama tidak boleh kosong.'
    return
  }
  router.put(route('roles.update', role.id), { name: editName.value }, {
    onSuccess: () => cancelEdit(),
    onError: (errors) => { editError.value = errors.name ?? 'Terjadi kesalahan.' },
    preserveScroll: true,
  })
}

// ─── Hapus Role ───────────────────────────────────────────────
function destroy(role) {
  if (confirm(`Hapus role "${role.name}"?`)) {
    router.delete(route('roles.destroy', role.id), { preserveScroll: true })
  }
}
</script>
