<script setup>
const props = defineProps({
    pages: Array,
    selected: Array,
    error: String,
})

const emit = defineEmits(['update:selected'])

function toggle(pageId) {
    const current = [...props.selected]
    const idx = current.indexOf(pageId)
    if (idx === -1) {
        current.push(pageId)
    } else {
        current.splice(idx, 1)
    }
    emit('update:selected', current)
}

function isSelected(pageId) {
    return props.selected.includes(pageId)
}

function statusColor(status) {
    return {
        active:   'bg-green-400',
        expiring: 'bg-yellow-400',
        expired:  'bg-red-400',
    }[status] ?? 'bg-gray-400'
}
</script>

<template>
    <div class="bg-white rounded-xl shadow p-6">
        <label class="block text-sm font-medium text-gray-700 mb-3">
            Páginas destino
            <span class="text-gray-400 font-normal">(seleccioná una o más)</span>
        </label>

        <div v-if="pages.length === 0" class="text-sm text-gray-500 py-4 text-center">
            No hay páginas disponibles. Vinculá páginas desde el panel de administración.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
                v-for="page in pages"
                :key="page.id"
                type="button"
                :disabled="page.status === 'expired'"
                @click="toggle(page.id)"
                :class="[
                    'flex items-center gap-3 px-4 py-3 rounded-lg border-2 text-left transition',
                    isSelected(page.id)
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300',
                    page.status === 'expired' ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
                ]"
            >
                <span :class="['w-2 h-2 rounded-full flex-shrink-0', statusColor(page.status)]" />
                <span class="text-sm font-medium text-gray-800 truncate">{{ page.page_name }}</span>
                <span v-if="isSelected(page.id)" class="ml-auto text-blue-500">✓</span>
            </button>
        </div>

        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
