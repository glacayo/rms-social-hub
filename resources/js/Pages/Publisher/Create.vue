<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import ContentEditor from './Partials/ContentEditor.vue'
import PageSelector from './Partials/PageSelector.vue'
import PostPreview from './Partials/PostPreview.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    pages: Array,
})

const form = useForm({
    content: '',
    page_ids: [],
    post_type: 'post',
    media_type: 'none',
    media: null,
    scheduled_at: '',
})

const previewMode = ref('mobile')
const mediaPreviewUrl = ref(null)

function onMediaSelected(file) {
    form.media = file
    form.media_type = file.type.startsWith('video/') ? 'video' : 'image'
    mediaPreviewUrl.value = URL.createObjectURL(file)
}

function submit() {
    form.post(route('publisher.store'), {
        forceFormData: true,
    })
}

const isScheduled = computed(() => !!form.scheduled_at)
</script>

<template>
    <Head title="Nuevo Post" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Nuevo Post</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left: Editor + Config -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Post type selector -->
                        <div class="bg-white rounded-xl shadow p-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Tipo de publicación</label>
                            <div class="flex gap-3">
                                <button
                                    v-for="type in ['post', 'reel', 'story']"
                                    :key="type"
                                    type="button"
                                    @click="form.post_type = type"
                                    :class="[
                                        'px-4 py-2 rounded-lg text-sm font-medium capitalize transition',
                                        form.post_type === type
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    ]"
                                >{{ type }}</button>
                            </div>
                        </div>

                        <!-- Content Editor -->
                        <ContentEditor
                            v-model:content="form.content"
                            :post-type="form.post_type"
                            :error="form.errors.content"
                            @media-selected="onMediaSelected"
                        />

                        <!-- Page Selector -->
                        <PageSelector
                            :pages="pages"
                            v-model:selected="form.page_ids"
                            :error="form.errors.page_ids"
                        />

                        <!-- Schedule -->
                        <div class="bg-white rounded-xl shadow p-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Programar publicación (opcional)
                            </label>
                            <input
                                type="datetime-local"
                                v-model="form.scheduled_at"
                                class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p v-if="form.errors.scheduled_at" class="mt-1 text-sm text-red-600">
                                {{ form.errors.scheduled_at }}
                            </p>
                        </div>

                        <!-- Media errors -->
                        <p v-if="form.errors.media" class="text-sm text-red-600">
                            {{ form.errors.media }}
                        </p>

                        <!-- Submit -->
                        <div class="flex justify-end gap-3">
                            <a :href="route('publisher.index')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                                Cancelar
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition"
                            >
                                {{ isScheduled ? 'Programar' : 'Guardar borrador' }}
                            </button>
                        </div>
                    </div>

                    <!-- Right: Preview -->
                    <div class="lg:col-span-1">
                        <PostPreview
                            :content="form.content"
                            :media-url="mediaPreviewUrl"
                            :media-type="form.media_type"
                            :post-type="form.post_type"
                            v-model:mode="previewMode"
                        />
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
