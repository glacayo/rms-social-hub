<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    stats:          Object,
    upcoming:       Array,
    tokenHealth:    Object,  // null for editors
    recentFailures: Array,
    unreadCount:    Number,
})

function formatDate(iso) {
    return new Date(iso).toLocaleString('es-AR', {
        day: '2-digit', month: 'short',
        hour: '2-digit', minute: '2-digit'
    })
}
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
                <Link
                    v-if="unreadCount > 0"
                    href="#"
                    class="text-sm text-blue-600 hover:underline"
                >
                    {{ unreadCount }} notificación{{ unreadCount > 1 ? 'es' : '' }} sin leer
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Post stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="(count, key) in stats" :key="key" class="bg-white rounded-xl shadow p-5">
                        <p class="text-sm text-gray-500 capitalize mb-1">{{ key }}</p>
                        <p class="text-3xl font-bold text-gray-800">{{ count }}</p>
                    </div>
                </div>

                <!-- Token health (admin only) -->
                <div v-if="tokenHealth" class="bg-white rounded-xl shadow p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Estado de tokens</h3>
                    <div class="flex gap-6">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-green-500"></span>
                            <span class="text-sm text-gray-600">Activos: <strong>{{ tokenHealth.active }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                            <span class="text-sm text-gray-600">Por vencer: <strong>{{ tokenHealth.expiring }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="text-sm text-gray-600">Expirados: <strong>{{ tokenHealth.expired }}</strong></span>
                        </div>
                        <Link
                            v-if="tokenHealth.expired > 0"
                            :href="route('admin.pages.index')"
                            class="ml-auto text-sm text-red-600 hover:underline font-medium"
                        >
                            ⚠️ Revincular páginas →
                        </Link>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Upcoming posts -->
                    <div class="bg-white rounded-xl shadow p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-700">Próximas publicaciones (7 días)</h3>
                            <Link :href="route('publisher.index')" class="text-xs text-blue-600 hover:underline">Ver todos</Link>
                        </div>
                        <div v-if="upcoming.length === 0" class="text-sm text-gray-400 py-4 text-center">
                            No hay posts programados para los próximos 7 días.
                        </div>
                        <ul v-else class="space-y-3">
                            <li v-for="post in upcoming" :key="post.id" class="flex gap-3 items-start">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800 truncate">{{ post.preview }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ formatDate(post.scheduled_at) }} · {{ post.pages.join(', ') }}
                                    </p>
                                </div>
                                <span class="text-xs text-blue-600 uppercase flex-shrink-0">{{ post.post_type }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Recent failures -->
                    <div class="bg-white rounded-xl shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Posts fallidos recientes</h3>
                        <div v-if="recentFailures.length === 0" class="text-sm text-gray-400 py-4 text-center">
                            No hay posts fallidos. ✅
                        </div>
                        <ul v-else class="space-y-3">
                            <li v-for="post in recentFailures" :key="post.id" class="flex gap-3 items-start">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800 truncate">{{ post.preview }}</p>
                                    <p class="text-xs text-red-400 mt-0.5 truncate">{{ post.failed_reason }}</p>
                                    <p class="text-xs text-gray-400">{{ post.retry_count }} intentos</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
