<script setup>
import { ref } from 'vue'

const props = defineProps({
    content: String,
    postType: String,
    error: String,
})

const emit = defineEmits(['update:content', 'media-selected'])

const charCount = ref(0)
const MAX_CHARS = 2000

function onInput(e) {
    charCount.value = e.target.value.length
    emit('update:content', e.target.value)
}

function onFileChange(e) {
    const file = e.target.files[0]
    if (file) emit('media-selected', file)
}

const acceptAttr = (type) => {
    if (type === 'reel' || type === 'story') return 'video/mp4,video/quicktime'
    return 'image/jpeg,image/png,video/mp4,video/quicktime'
}
</script>

<template>
    <div class="bg-white rounded-xl shadow p-6 space-y-4">
        <div>
            <div class="flex justify-between mb-1">
                <label class="text-sm font-medium text-gray-700">Contenido</label>
                <span :class="['text-xs', charCount > 1900 ? 'text-red-500' : 'text-gray-400']">
                    {{ charCount }}/{{ MAX_CHARS }}
                </span>
            </div>
            <textarea
                :value="content"
                @input="onInput"
                :maxlength="MAX_CHARS"
                rows="5"
                placeholder="Escribí el texto de tu publicación..."
                class="w-full border-gray-300 rounded-lg shadow-sm text-sm resize-none focus:ring-blue-500 focus:border-blue-500"
            />
            <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Media
                <span class="text-gray-400 font-normal">
                    ({{ postType === 'reel' || postType === 'story' ? 'Video 9:16 — MP4/MOV' : 'Imagen JPEG/PNG o video MP4/MOV' }})
                </span>
            </label>
            <input
                type="file"
                :accept="acceptAttr(postType)"
                @change="onFileChange"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
        </div>
    </div>
</template>
