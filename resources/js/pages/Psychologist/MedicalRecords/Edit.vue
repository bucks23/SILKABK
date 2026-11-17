<template>
  <AppLayout title="Edit Asesmen Psikologi">
    <div class="max-w-2xl space-y-5">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <Link :href="route('psychologist.medical-records.show', record.id)" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </Link>
        <div>
          <h2 class="text-lg font-semibold text-gray-800">Edit Asesmen Psikologi</h2>
          <p class="text-sm text-gray-500">{{ record.patient?.name }}</p>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-5">

        <!-- Informasi Asesmen -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-5">
          <h3 class="font-semibold text-gray-800 pb-3 border-b border-gray-100">Informasi Asesmen</h3>

          <div v-if="psychologists.length">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Psikolog Penanggung Jawab <span class="text-red-500">*</span></label>
            <select v-model="form.psychologist_id"
              class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="form.errors.psychologist_id ? 'border-red-400' : 'border-gray-300'">
              <option value="">-- Pilih Psikolog --</option>
              <option v-for="ps in psychologists" :key="ps.id" :value="ps.id">{{ ps.nama }}</option>
            </select>
            <p v-if="form.errors.psychologist_id" class="mt-1 text-xs text-red-500">{{ form.errors.psychologist_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Pasien <span class="text-red-500">*</span></label>
            <select v-model="form.patient_id"
              class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="form.errors.patient_id ? 'border-red-400' : 'border-gray-300'">
              <option value="">-- Pilih Pasien --</option>
              <option v-for="p in patients" :key="p.id" :value="p.id">
                {{ p.name }} ({{ p.medical_record_number }})
              </option>
            </select>
            <p v-if="form.errors.patient_id" class="mt-1 text-xs text-red-500">{{ form.errors.patient_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Kunjungan <span class="text-red-500">*</span></label>
            <input type="date" v-model="form.visit_date"
              class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="form.errors.visit_date ? 'border-red-400' : 'border-gray-300'" />
            <p v-if="form.errors.visit_date" class="mt-1 text-xs text-red-500">{{ form.errors.visit_date }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Keluhan / Rujukan <span class="text-red-500">*</span></label>
            <textarea v-model="form.chief_complaint" rows="3"
              class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="form.errors.chief_complaint ? 'border-red-400' : 'border-gray-300'"/>
            <p v-if="form.errors.chief_complaint" class="mt-1 text-xs text-red-500">{{ form.errors.chief_complaint }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Hasil Asesmen / Kesimpulan <span class="text-red-500">*</span></label>
            <textarea v-model="form.diagnosis" rows="4"
              class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="form.errors.diagnosis ? 'border-red-400' : 'border-gray-300'"/>
            <p v-if="form.errors.diagnosis" class="mt-1 text-xs text-red-500">{{ form.errors.diagnosis }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Rekomendasi / Rencana Intervensi <span class="text-gray-400 font-normal">(opsional)</span>
            </label>
            <textarea v-model="form.treatment_plan" rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Catatan Tambahan <span class="text-gray-400 font-normal">(opsional)</span>
            </label>
            <textarea v-model="form.notes" rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
          </div>
        </div>

        <!-- Skrining KPSP -->
        <KpspSection ref="kpspRef" :initial="record.kpsp_result" />

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
          <Link :href="route('psychologist.medical-records.show', record.id)"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
            Batal
          </Link>
          <button type="submit" :disabled="form.processing"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors disabled:opacity-50">
            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>
        </div>
      </form>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import KpspSection from '@/components/KpspSection.vue'

const props = defineProps({
  record:        { type: Object, required: true },
  patients:      { type: Array,  default: () => [] },
  // Diisi bila yang login super admin / dokter (harus memilih psikolog)
  psychologists: { type: Array,  default: () => [] },
})

const kpspRef = ref(null)

const form = useForm({
  psychologist_id: props.record.psychologist_id ?? '',
  patient_id:      props.record.patient_id,
  visit_date:      props.record.visit_date,
  chief_complaint: props.record.chief_complaint ?? '',
  diagnosis:       props.record.diagnosis ?? '',
  treatment_plan:  props.record.treatment_plan ?? '',
  kpsp_result:     props.record.kpsp_result ?? null,
  notes:           props.record.notes ?? '',
})

function submit() {
  form.kpsp_result = kpspRef.value?.build() ?? null
  form.put(route('psychologist.medical-records.update', props.record.id))
}
</script>
