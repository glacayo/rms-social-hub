<script setup>
const props = defineProps({
    content: String,
    mediaUrl: String,
    mediaType: String,
    postType: String,
    mode: { type: String, default: 'mobile' },
})

const emit = defineEmits(['update:mode'])
</script>

<template>
    <div class="sticky top-6">
        <div class="bg-white rounded-xl shadow p-4">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-700">Preview</span>
                <div class="flex gap-1">
                    <button
                        v-for="m in ['mobile', 'desktop']"
                        :key="m"
                        type="button"
                        @click="emit('update:mode', m)"
                        :class="[
                            'px-3 py-1 text-xs rounded-md transition capitalize',
                            mode === m ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        ]"
                    >{{ m }}</button>
                </div>
            </div>

            <!-- Mock Facebook post frame -->
            <div :class="['mx-auto transition-all duration-300 border border-gray-200 rounded-lg overflow-hidden bg-white', mode === 'mobile' ? 'max-w-[375px]' : 'max-w-full']">
                <!-- Facebook header mock -->
                <div class="flex items-center gap-2 p-3 border-b border-gray-100">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold">RMS</div>
                    <div>
                        <div class="text-xs font-semibold text-gray-800">Raven Marketing</div>
                        <div class="text-xs text-gray-400">Ahora · 🌐</div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-3">
                    <p v-if="content" class="text-sm text-gray-800 whitespace-pre-wrap">{{ content }}</p>
                    <p v-else class="text-sm text-gray-400 italic">El texto aparecerá acá...</p>
                </div>

                <!-- Media preview -->
                <div v-if="mediaUrl" :class="['w-full bg-gray-100', postType === 'reel' || postType === 'story' ? 'aspect-[9/16]' : 'aspect-video']">
                    <img v-if="mediaType === 'image'" :src="mediaUrl" class="w-full h-full object-cover" />
                    <video v-else-if="mediaType === 'video'" :src="mediaUrl" class="w-full h-full object-cover" muted />
                </div>

                <!-- Facebook actions mock -->
                <div class="flex gap-4 px-3 py-2 border-t border-gray-100 text-xs text-gray-500">
                    <span>👍 Me gusta</span>
                    <span>💬 Comentar</span>
                    <span>↗ Compartir</span>
                </div>
            </div>
        </div>
    </div>
</template>
