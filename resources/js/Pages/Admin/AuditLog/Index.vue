<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
    logs:    Object, // paginated
    actions: Array,
    users:   Array,
    pages:   Array,
    filters: Object,
})

const filters = ref({ ...props.filters })

// Debounced filter apply
let debounceTimer
watch(filters, (val) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        router.get(route('admin.audit-log.index'), val, { preserveState: true, replace: true })
    }, 400)
}, { deep: true })

function clearFilters() {
    filters.value = {}
}

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleString('es-AR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

function actionColor(action) {
    if (action.includes('published')) return 'bg-green-100 text-green-700'
    if (action.includes('failed') || action.includes('expired')) return 'bg-red-100 text-red-700'
    if (action.includes('refresh') || action.includes('scheduled')) return 'bg-blue-100 text-blue-700'
    if (action.includes('cancelled')) return 'bg-gray-100 text-gray-500'
    return 'bg-gray-100 text-gray-600'
}
</script>

<template>
    <Head title="Audit Log" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Audit Log</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <select v-model="filters.action" class="border-gray-300 rounded-lg text-sm">
                            <option value="">Todas las acciones</option>
                            <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
                        </select>
                        <select v-model="filters.user_id" class="border-gray-300 rounded-lg text-sm">
                            <option value="">Todos los usuarios</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <select v-model="filters.page_id" class="border-gray-300 rounded-lg text-sm">
                            <option value="">Todas las páginas</option>
                            <option v-for="p in pages" :key="p.id" :value="p.id">{{ p.page_name }}</option>
                        </select>
                        <input
                            type="date"
                            v-model="filters.date_from"
                            class="border-gray-300 rounded-lg text-sm"
                            placeholder="Desde"
                        />
                        <input
                            type="date"
                            v-model="filters.date_to"
                            class="border-gray-300 rounded-lg text-sm"
                            placeholder="Hasta"
                        />
                    </div>
                    <button @click="clearFilters" class="mt-2 text-xs text-gray-400 hover:text-gray-600">
                        Limpiar filtros
                    </button>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div v-if="logs.data.length === 0" class="text-center py-12 text-gray-400 text-sm">
                        No hay registros para los filtros seleccionados.
                    </div>
                    <table v-else class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Página</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Post</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', actionColor(log.action)]">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ log.user?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ log.page?.page_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 max-w-xs truncate">
                                    {{ log.post ? log.post.content?.substring(0, 50) + '...' : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="logs.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex justify-between items-center text-sm text-gray-500">
                        <span>{{ logs.total }} registros</span>
                        <div class="flex gap-1">
                            <a
                                v-for="link in logs.links"
                                :key="link.label"
                                v-html="link.label"
                                :href="link.url"
                                :class="['px-3 py-1 rounded border', link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 hover:bg-gray-50']"
                            />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
