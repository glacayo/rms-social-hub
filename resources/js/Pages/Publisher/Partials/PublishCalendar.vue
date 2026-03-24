<script setup>
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

const props = defineProps({
    events: Array,
})

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: 'es',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek',
    },
    events: props.events,
    eventClick(info) {
        const p = info.event.extendedProps
        alert(`${info.event.title}\nEstado: ${p.status}\nTipo: ${p.post_type}\nPáginas: ${p.pages.join(', ')}`)
    },
    height: 'auto',
}
</script>

<template>
    <div class="bg-white rounded-xl shadow p-6">
        <FullCalendar :options="calendarOptions" />
    </div>
</template>
