<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    anime: {
        type: Array,
        default: () => [],
    },
    genres: {
        type: Array,
        default: () => [],
    },
    studios: {
        type: Array,
        default: () => [],
    },
    characters: {
        type: Array,
        default: () => [],
    },
    sources: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    initialSection: {
        type: String,
        default: 'anime',
    },
});

const sections = [
    {
        key: 'anime',
        label: 'Аниме',
        title: 'Список аниме',
        createHref: '/admin/anime',
    },
    {
        key: 'genres',
        label: 'Жанры',
        title: 'Список жанров',
        createHref: '/admin/genres',
    },
    {
        key: 'studios',
        label: 'Студии',
        title: 'Список студий',
        createHref: '/admin/studios',
    },
    {
        key: 'characters',
        label: 'Персонажи',
        title: 'Список персонажей',
        createHref: '/admin/characters',
    },
    {
        key: 'sources',
        label: 'Первоисточники',
        title: 'Список первоисточников',
        createHref: '/admin/sources',
    },
    {
        key: 'users',
        label: 'Пользователи',
        title: 'Список пользователей',
    },
];

const sectionKeys = new Set(sections.map((section) => section.key));
const active = ref(sectionKeys.has(props.initialSection) ? props.initialSection : 'anime');
const activeSection = computed(() => sections.find((section) => section.key === active.value));
const searchQuery = ref('');
const selectedAnimeIds = ref([]);
const isAnimeSection = computed(() => active.value === 'anime');
const activeItems = computed(() => {
    if (active.value === 'anime') {
        return props.anime;
    }
    if (active.value === 'genres') {
        return props.genres;
    }
    if (active.value === 'studios') {
        return props.studios;
    }
    if (active.value === 'characters') {
        return props.characters;
    }
    if (active.value === 'sources') {
        return props.sources;
    }
    if (active.value === 'users') {
        return props.users;
    }
    return activeSection.value?.items || [];
});

const filteredItems = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return activeItems.value;
    return activeItems.value.filter((item) => {
        const name = item.title ?? item.name ?? item.username ?? '';
        return String(name).toLowerCase().includes(query);
    });
});

const allAnimeSelected = computed(() => {
    if (!isAnimeSection.value) return false;
    const ids = filteredItems.value.map((item) => item.id).filter(Boolean);
    return ids.length > 0 && ids.every((id) => selectedAnimeIds.value.includes(id));
});

const toggleSelectAll = () => {
    if (!isAnimeSection.value) return;
    if (allAnimeSelected.value) {
        selectedAnimeIds.value = [];
        return;
    }
    selectedAnimeIds.value = filteredItems.value.map((item) => item.id).filter(Boolean);
};

const editHref = (item) => {
    if (active.value === 'anime') return `/admin/anime/${item.id}/edit`;
    if (active.value === 'genres') return `/admin/genres/${item.id}/edit`;
    if (active.value === 'studios') return `/admin/studios/${item.id}/edit`;
    if (active.value === 'characters') return `/admin/characters/${item.id}/edit`;
    if (active.value === 'sources') return `/admin/sources/${item.id}/edit`;
    if (active.value === 'users') return `/admin/users/${item.id}/edit`;
    return null;
};

const deleteItem = (item) => {
    if (!confirm('Удалить элемент?')) return;
    let url = null;
    if (active.value === 'anime') url = `/admin/anime/${item.id}`;
    if (active.value === 'genres') url = `/admin/genres/${item.id}`;
    if (active.value === 'studios') url = `/admin/studios/${item.id}`;
    if (active.value === 'characters') url = `/admin/characters/${item.id}`;
    if (active.value === 'sources') url = `/admin/sources/${item.id}`;
    if (active.value === 'users') url = `/admin/users/${item.id}`;
    if (!url) return;
    router.delete(url, { preserveScroll: true });
};

const bulkDeleteAnime = () => {
    if (!selectedAnimeIds.value.length) return;
    if (!confirm('Удалить выбранные аниме?')) return;
    router.post(
        '/admin/anime/bulk-delete',
        { ids: selectedAnimeIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedAnimeIds.value = [];
            },
        },
    );
};

watch(active, () => {
    selectedAnimeIds.value = [];
});
</script>

<template>
    <div class="w-full text-text-primary">
        <div class="mx-auto max-w-[1440px] p-5">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold">Админ-панель</h1>
            </div>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                <aside class="rounded-xs bg-accent p-5 border border-secondary/60 w-full lg:w-[260px] lg:shrink-0 lg:self-start lg:h-fit">
                    <div class="text-sm font-semibold text-text-primary">Разделы</div>
                    <nav class="mt-4 space-y-1">
                        <button
                            v-for="section in sections"
                            :key="section.key"
                            class="w-full rounded-xs px-3 py-2 text-left text-sm text-text-secondary transition hover:bg-secondary/70"
                            :class="active === section.key ? 'bg-secondary text-text-primary' : ''"
                            type="button"
                            @click="active = section.key"
                        >
                            {{ section.label }}
                        </button>
                    </nav>
                </aside>

                <section class="rounded-xs bg-accent p-5 border border-secondary/60 flex-1 min-w-0 lg:self-start">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">{{ activeSection?.title }}</h2>
                            <p class="mt-1 text-xs text-text-secondary">Выбран раздел: {{ activeSection?.label }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <label v-if="isAnimeSection" class="flex items-center gap-2 text-xs text-text-secondary">
                                <input
                                    type="checkbox"
                                    class="filter-checkbox cursor-pointer"
                                    :checked="allAnimeSelected"
                                    @change="toggleSelectAll"
                                />
                                Выбрать все
                            </label>
                            <input
                                v-model="searchQuery"
                                class="h-9 rounded-xs bg-secondary px-3 text-sm text-text-primary focus:outline-none"
                                type="text"
                                placeholder="Поиск по названию"
                            />
                            <Link
                                v-if="activeSection?.createHref"
                                class="h-9 rounded-xs bg-primary px-4 py-2 text-sm text-white transition hover:bg-primary-hover"
                                :href="activeSection.createHref"
                            >
                                Создать
                            </Link>
                            <button
                                v-if="isAnimeSection && selectedAnimeIds.length"
                                class="h-9 rounded-xs border border-secondary/60 bg-secondary px-4 py-2 text-sm text-rose-300 transition hover:bg-secondary-hover"
                                type="button"
                                @click="bulkDeleteAnime"
                            >
                                Удалить выбранные
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="item in filteredItems"
                            :key="item.id ?? item.title"
                            class="flex items-center gap-3 rounded-xs border border-secondary/60 bg-secondary p-3"
                        >
                            <div v-if="active === 'anime'" class="flex items-center">
                                <input
                                    v-model="selectedAnimeIds"
                                    :value="item.id"
                                    type="checkbox"
                                    class="filter-checkbox cursor-pointer"
                                />
                            </div>
                            <div v-if="active === 'anime'" class="h-16 w-12 overflow-hidden rounded-xs bg-input">
                                <img v-if="item.cover_url" :src="item.cover_url" alt="" class="h-full w-full object-cover" />
                                <div v-else class="h-full w-full bg-black/20"></div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-text-primary">
                                    {{ item.title ?? item.name ?? item.username }}
                                </div>
                                <div v-if="active === 'anime' && item.alt_title" class="mt-1 text-xs text-text-secondary">
                                    {{ item.alt_title }}
                                </div>
                                <div v-if="active === 'anime'" class="mt-1 text-xs text-text-secondary">
                                    {{ `${item.type || '-'} | ${item.year || '-'} | ${item.genres || '-'}` }}
                                </div>
                                <div v-else-if="active === 'characters'" class="mt-1 text-xs text-text-secondary">
                                    {{ item.voice_actor || 'Озвучка не указана' }}
                                </div>
                                <div v-else-if="active === 'users'" class="mt-1 text-xs text-text-secondary">
                                    {{ item.email }}
                                </div>
                                <div v-else class="mt-1 text-xs text-text-secondary">
                                    {{ item.meta || 'Элемент' }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    v-if="editHref(item)"
                                    class="rounded-xs border border-secondary/60 bg-secondary px-3 py-1 text-xs text-text-primary transition hover:bg-secondary-hover"
                                    :href="editHref(item)"
                                >
                                    Редактировать
                                </Link>
                                <button
                                    class="rounded-xs border border-secondary/60 bg-secondary px-3 py-1 text-xs text-rose-300 transition hover:bg-secondary-hover"
                                    type="button"
                                    @click="deleteItem(item)"
                                >
                                    Удалить
                                </button>
                            </div>
                        </div>
                        <div v-if="!filteredItems.length" class="rounded-xs border border-secondary/60 bg-secondary p-4 text-center text-sm text-text-secondary">
                            Список пуст.
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
