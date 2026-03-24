<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    pages: Array,
})

function unlinkPage(page) {
    if (confirm(`¿Desvincular "${page.page_name}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('admin.pages.destroy', page.id))
    }
}

function statusBadgeClass(status) {
    return {
        'active': 'bg-green-100 text-green-800',
        'expiring': 'bg-yellow-100 text-yellow-800',
        'expired': 'bg-red-100 text-red-800',
    }[status] ?? 'bg-gray-100 text-gray-800'
}

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('es-AR', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
    <Head title="Páginas de Facebook" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Páginas de Facebook</h2>
                <Link
                    :href="route('facebook.connect')"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition"
                >
                    + Vincular página
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Flash messages -->
                <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Empty state -->
                <div v-if="pages.length === 0" class="text-center py-16 text-gray-500">
                    <p class="text-lg font-medium mb-2">No hay páginas vinculadas</p>
                    <p class="text-sm mb-6">Conectá tu Business Manager para comenzar.</p>
                    <Link
                        :href="route('facebook.connect')"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                        Vincular primera página
                    </Link>
                </div>

                <!-- Pages table -->
                <div v-else class="bg-white rounded-xl shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Página</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado token</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expira</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vinculada por</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="page in pages" :key="page.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ page.page_name }}</div>
                                    <div class="text-xs text-gray-400">ID: {{ page.page_id }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusBadgeClass(page.token_status)]">
                                        {{ page.token_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ formatDate(page.token_expires_at) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ page.linked_by?.name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        @click="unlinkPage(page)"
                                        class="text-red-600 hover:text-red-800 text-sm font-medium transition"
                                    >
                                        Desvincular
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
