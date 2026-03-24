<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import PublishCalendar from './Partials/PublishCalendar.vue'
import { ref } from 'vue'

const props = defineProps({
    posts: Object,        // paginated
    calendarPosts: Array, // for calendar
})

const view = ref('list') // 'list' | 'calendar'

function statusColor(status) {
    return {
        draft:     'bg-gray-100 text-gray-600',
        scheduled: 'bg-blue-100 text-blue-700',
        sending:   'bg-yellow-100 text-yellow-700',
        published: 'bg-green-100 text-green-700',
        failed:    'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-400',
    }[status] ?? 'bg-gray-100 text-gray-500'
}
</script>

<template>
    <Head title="Publisher" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Posts</h2>
                <div class="flex items-center gap-3">
                    <!-- View toggle -->
                    <div class="flex gap-1 bg-gray-100 p-1 rounded-lg">
                        <button
                            v-for="v in ['list', 'calendar']"
                            :key="v"
                            @click="view = v"
                            :class="['px-3 py-1 text-xs rounded-md capitalize transition', view === v ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700']"
                        >{{ v }}</button>
                    </div>
                    <Link
                        :href="route('publisher.create')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition"
                    >+ Nuevo post</Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Calendar view -->
                <PublishCalendar v-if="view === 'calendar'" :events="calendarPosts" />

                <!-- List view -->
                <div v-else>
                    <div v-if="posts.data.length === 0" class="text-center py-16 text-gray-500">
                        <p class="text-lg font-medium mb-2">No hay posts todavía</p>
                        <Link :href="route('publisher.create')" class="text-blue-600 hover:underline">Crear el primero</Link>
                    </div>

                    <div v-else class="bg-white rounded-xl shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contenido</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Programado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Páginas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="post in posts.data" :key="post.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 max-w-xs">
                                        <p class="text-sm text-gray-900 truncate">{{ post.content }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs text-gray-500 capitalize">{{ post.post_type }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusColor(post.status)]">
                                            {{ post.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ post.scheduled_at ? new Date(post.scheduled_at).toLocaleString('es-AR') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ post.pages?.map(p => p.page_name).join(', ') || '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
