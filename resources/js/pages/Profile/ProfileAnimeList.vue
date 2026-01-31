<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    owner: {
        type: Object,
        required: true,
    },
    entries: {
        type: Array,
        default: () => [],
    },
    counts: {
        type: Object,
        default: () => ({}),
    },
    friends: {
        type: Array,
        default: () => [],
    },
    friends_count: {
        type: Number,
        default: 0,
    },
});

const filters = [
    { value: 'all', label: 'Все', color: 'bg-blue-500' },
    { value: 'watching', label: 'Смотрю', color: 'bg-blue-400' },
    { value: 'completed', label: 'Просмотрено', color: 'bg-green-400' },
    { value: 'on_hold', label: 'Отложено', color: 'bg-gray-300' },
    { value: 'dropped', label: 'Брошено', color: 'bg-red-400' },
    { value: 'planned', label: 'Запланировано', color: 'bg-yellow-400' },
];

const page = usePage();
const initialFilter = computed(() => {
    const url = page.url || '';
    const query = url.includes('?') ? url.split('?')[1] : '';
    const params = new URLSearchParams(query);
    const value = params.get('status') || 'all';
    return filters.some((filter) => filter.value === value) ? value : 'all';
});

const activeFilter = ref(initialFilter.value);

const filteredEntries = computed(() => {
    if (activeFilter.value === 'all') {
        return props.entries;
    }
    return props.entries.filter((entry) => entry.status === activeFilter.value);
});

const gridClass = computed(() =>
    activeFilter.value === 'all'
        ? 'grid-cols-[40px_1.5fr_0.8fr_0.6fr_0.6fr]'
        : 'grid-cols-[40px_1.5fr_0.6fr_0.6fr]'
);

const statusColor = (status) => {
    switch (status) {
        case 'watching':
            return 'bg-blue-400';
        case 'completed':
            return 'bg-green-400';
        case 'on_hold':
            return 'bg-gray-300';
        case 'dropped':
            return 'bg-red-400';
        case 'planned':
            return 'bg-yellow-400';
        default:
            return 'bg-blue-500';
    }
};
</script>

<template>
    <div class="w-full">
        <div class="max-w-[1440px] mx-auto p-5">
            <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                <div class="rounded bg-accent p-6">
                    <h1 class="text-lg font-semibold">Список аниме</h1>

                    <div class="mt-4 flex flex-wrap gap-4 text-xs text-text-secondary">
                        <button
                            v-for="filter in filters"
                            :key="filter.value"
                            class="flex items-center gap-2 hover:text-text-primary"
                            type="button"
                            @click="activeFilter = filter.value"
                        >
                            <span class="h-2.5 w-2.5 rounded-full" :class="filter.color"></span>
                            <span>{{ filter.label }}:</span>
                            <span class="text-text-primary">
                                {{ filter.value === 'all'
                                    ? entries.length
                                    : (counts[filter.value] ?? 0) }}
                            </span>
                        </button>
                    </div>

                    <div class="mt-6 text-sm text-text-secondary">
                        <div class="grid gap-4 border-b border-secondary pb-3 text-xs uppercase tracking-wide text-text-secondary" :class="gridClass">
                            <div>#</div>
                            <div>Название</div>
                            <div v-if="activeFilter === 'all'">Статус</div>
                            <div>Эпизоды</div>
                            <div>Тип</div>
                        </div>

                        <div v-if="!filteredEntries.length" class="mt-6 text-sm text-text-secondary">
                            Список пуст.
                        </div>

                        <div
                            v-for="(entry, index) in filteredEntries"
                            :key="entry.id"
                            class="grid gap-4 border-b border-secondary/60 py-4 text-sm"
                            :class="gridClass"
                        >
                            <div class="text-text-secondary">{{ index + 1 }}</div>
                            <Link :href="`/anime/${entry.anime.id}`" class="flex items-center gap-3">
                                <div class="h-20 w-14 overflow-hidden rounded bg-secondary">
                                    <img v-if="entry.anime.cover_url" :src="entry.anime.cover_url" alt="" class="h-full w-full object-cover" />
                                </div>
                                <div>
                                    <div class="text-base text-text-primary">{{ entry.anime.title }}</div>
                                    <div class="text-xs text-text-secondary">{{ entry.anime.alt_title }}</div>
                                </div>
                            </Link>
                            <div v-if="activeFilter === 'all'" class="flex items-center gap-2 text-sm">
                                <span class="h-2.5 w-2.5 rounded-full" :class="statusColor(entry.status)"></span>
                                <span class="text-text-primary">{{ entry.status_label }}</span>
                            </div>
                            <div class="text-text-primary">{{ entry.anime.episodes ?? '-' }}</div>
                            <div class="text-text-primary">{{ entry.anime.type || '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded bg-accent p-5">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <span>Друзья</span>
                        <span class="text-xs text-text-secondary">{{ friends_count }}</span>
                    </div>
                    <div class="mt-4 flex items-center gap-3">
                        <div v-if="!friends.length" class="text-xs text-text-secondary">
                            Друзей пока нет.
                        </div>
                        <div v-for="friend in friends" :key="friend.id" class="text-center text-xs">
                            <Link :href="`/profile/${friend.username}`">
                                <img :src="friend.avatar_url" alt="" class="h-10 w-10 rounded object-cover" />
                                <div class="mt-1 text-text-secondary">{{ friend.username }}</div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

