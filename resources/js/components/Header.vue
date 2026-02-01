<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props?.auth?.user ?? null);
const hideHeader = computed(() => {
    const component = page.component || '';
    return component.startsWith('Auth/');
});
const isStaff = computed(() => user.value?.role === 'admin');

const form = useForm({});
const logout = () => {
    form.post('/logout');
};

const showSearch = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchTimer = ref(null);
const searchInput = ref(null);
const activityTimer = ref(null);
const searchWrap = ref(null);

const openSearch = async () => {
    showSearch.value = true;
    await nextTick();
    searchInput.value?.focus();
};

const closeSearch = () => {
    showSearch.value = false;
    searchQuery.value = '';
    searchResults.value = [];
    searchLoading.value = false;
};

const toggleSearch = () => {
    if (showSearch.value) {
        closeSearch();
        return;
    }
    openSearch();
};



const clampText = (value, max = 25) => {
    const text = String(value ?? '');
    if (text.length <= max) return text;
    if (max <= 3) return text.slice(0, max);
    return `${text.slice(0, max - 3)}...`;
};

const fetchResults = async (value) => {
    try {
        searchLoading.value = true;
        const response = await fetch(`/anime/search?q=${encodeURIComponent(value)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) {
            searchResults.value = [];
            return;
        }
        searchResults.value = await response.json();
    } catch (error) {
        searchResults.value = [];
    } finally {
        searchLoading.value = false;
    }
};

watch(searchQuery, (value) => {
    if (!showSearch.value) return;
    if (searchTimer.value) {
        window.clearTimeout(searchTimer.value);
    }
    const trimmed = value.trim();
    if (!trimmed) {
        searchResults.value = [];
        searchLoading.value = false;
        return;
    }
    searchTimer.value = window.setTimeout(() => {
        fetchResults(trimmed);
    }, 250);
});

onBeforeUnmount(() => {
    if (searchTimer.value) {
        window.clearTimeout(searchTimer.value);
    }
});

const handleOutsideClick = (event) => {
    if (!showSearch.value) return;
    const target = event.target;
    if (searchWrap.value && !searchWrap.value.contains(target)) {
        closeSearch();
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);

    if (user.value) {
        activityTimer.value = window.setInterval(() => {
            fetch('/activity/ping', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).catch(() => {});
        }, 60000);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
    if (activityTimer.value) {
        window.clearInterval(activityTimer.value);
    }
});
</script>

<template>
    <header v-if="!hideHeader" class="sticky top-0 z-[500] border-b border-secondary bg-accent">
        <div class="mx-auto flex max-w-[1440px] items-center justify-between px-5 py-3">
            <div class="flex items-center gap-6">
                <Link class="text-lg font-semibold" href="/">
                    INOILVAULT
             
                </Link>
                <Link
                    class="text-sm text-text-primary"
                    href="/anime"
                >
                    Аниме
                </Link>
                <Link
                    v-if="isStaff"
                    class="text-sm text-text-primary"
                    href="/admin"
                >
                    Админ панель
                </Link>
            </div>

            <div class="flex items-center gap-2">
                <template v-if="user">
                    <Link class="flex items-center gap-2" href="/profile">
                        <span class="text-sm font-semibold">{{ user.username }}</span>
                        <img
                            :src="user.avatar_url || '/storage/images/placeholders/avatar-placeholder.png'"
                            alt="Avatar"
                            class="h-9 w-9 rounded-xs object-cover"
                        />
                    </Link>
                    <Link
                        class="flex h-9 w-9 items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                        :href="`/profile/${user.username}/friends`"
                    >
                     
                        <svg class="" fill="#bfbfbf" width="21px" height="21px" viewBox="0 -64 640 640" xmlns="http://www.w3.org/2000/svg"><path d="M192 256c61.9 0 112-50.1 112-112S253.9 32 192 32 80 82.1 80 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C51.6 288 0 339.6 0 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zM480 256c53 0 96-43 96-96s-43-96-96-96-96 43-96 96 43 96 96 96zm48 32h-3.8c-13.9 4.8-28.6 8-44.2 8s-30.3-3.2-44.2-8H432c-20.4 0-39.2 5.9-55.7 15.4 24.4 26.3 39.7 61.2 39.7 99.8v38.4c0 2.2-.5 4.3-.6 6.4H592c26.5 0 48-21.5 48-48 0-61.9-50.1-112-112-112z"/></svg>

                    </Link>
                    <button class="flex cursor-pointer h-9 w-9 items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover" type="button">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path
                                d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2Zm6-6V11a6 6 0 0 0-4-5.659V4a2 2 0 1 0-4 0v1.341A6 6 0 0 0 6 11v5l-2 2v1h16v-1l-2-2Z"
                                fill="currentColor"
                            />
                        </svg>
                    </button>
                    <div ref="searchWrap" class="relative z-[520] flex items-center" @click.stop>
                        <div
                            class="flex items-center overflow-hidden transition-[max-width,opacity,margin] duration-200 ease-out"
                            :class="showSearch ? 'max-w-[360px] opacity-100 mr-2' : 'max-w-0 opacity-0 pointer-events-none mr-0'"
                        >
                            <input
                                ref="searchInput"
                                v-model="searchQuery"
                                class="h-9 w-[360px] rounded-xs bg-secondary px-3 text-sm text-text-primary focus:outline-none"
                                type="text"
                                placeholder="Поиск по названию"
                            />
                        </div>

                        <button
                            class="flex cursor-pointer h-9 w-9 items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                            type="button"
                            @click="toggleSearch"
                        >
                            <svg v-if="!showSearch" id="searchIcon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M14.3851 15.4457C11.7349 17.5684 7.85544 17.4013 5.39866 14.9445C2.76262 12.3085 2.76262 8.03464 5.39866 5.3986C8.0347 2.76256 12.3086 2.76256 14.9446 5.3986C17.4014 7.85538 17.5685 11.7348 15.4458 14.3851L20.6014 19.5407C20.8943 19.8336 20.8943 20.3085 20.6014 20.6014C20.3085 20.8943 19.8337 20.8943 19.5408 20.6014L14.3851 15.4457ZM6.45932 13.8839C4.40907 11.8336 4.40907 8.50951 6.45932 6.45926C8.50957 4.40901 11.8337 4.40901 13.8839 6.45926C15.9327 8.50801 15.9342 11.8287 13.8885 13.8794C13.8869 13.8809 13.8854 13.8823 13.8839 13.8839C13.8824 13.8854 13.8809 13.8869 13.8794 13.8884C11.8288 15.9341 8.50807 15.9326 6.45932 13.8839Z"
                                    fill="#bfbfbf"
                                />
                            </svg>
                            <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </button>

                        <div v-if="showSearch && searchQuery" class="absolute right-11 top-12 z-[540] w-[360px] rounded-xs bg-accent p-3 shadow-lg">
                            <div v-if="searchLoading" class="text-xs text-text-secondary">Поиск...</div>
                            <div v-else-if="!searchResults.length" class="text-xs text-text-secondary">Ничего не найдено.</div>
                            <div v-else class="space-y-2">
                                <Link
                                    v-for="item in searchResults"
                                    :key="item.id"
                                    :href="`/anime/${item.id}`"
                                    class="flex items-center gap-3 rounded-xs bg-secondary/70 p-2 transition hover:bg-secondary"
                                    @click="closeSearch"
                                >
                                    <div class="h-14 w-10 overflow-hidden rounded-xs bg-secondary">
                                        <img v-if="item.cover_url" :src="item.cover_url" alt="" class="h-full w-full object-cover" />
                                    </div>
                                    <div class="text-xs text-text-secondary">
                                        <div class="text-sm text-text-primary">{{ clampText(item.title, 30) }}</div>
                                        <div class="text-text-secondary">{{ clampText(item.alt_title, 30) }}</div>
                                        <div class="mt-1 text-text-secondary">
                                            {{ item.season_label || '-' }} | {{ item.type || '-' }}
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                    <form @submit.prevent="logout">
                        <button
                            class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                            type="submit"
                            :disabled="form.processing"
                        >
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path
                                    d="M10 12h10m0 0-3-3m3 3-3 3"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M15 3H9C7.343 3 6 4.343 6 6v12c0 1.657 1.343 3 3 3h6"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>
                    </form>
                </template>
                <template v-else>
                    <div ref="searchWrap" class="relative z-[520] flex items-center" @click.stop>
                        <div
                            class="flex items-center overflow-hidden transition-[max-width,opacity,margin] duration-200 ease-out"
                            :class="showSearch ? 'max-w-[360px] opacity-100 mr-2' : 'max-w-0 opacity-0 pointer-events-none mr-0'"
                        >
                            <input
                                ref="searchInput"
                                v-model="searchQuery"
                                class="h-9 w-[360px] rounded-xs bg-secondary px-3 text-sm text-text-primary focus:outline-none"
                                type="text"
                                placeholder="Поиск по названию"
                            />
                        </div>

                        <button
                            class="flex h-9 w-9 items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                            type="button"
                            @click="toggleSearch"
                        >
                            <svg v-if="!showSearch" id="searchIcon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M14.3851 15.4457C11.7349 17.5684 7.85544 17.4013 5.39866 14.9445C2.76262 12.3085 2.76262 8.03464 5.39866 5.3986C8.0347 2.76256 12.3086 2.76256 14.9446 5.3986C17.4014 7.85538 17.5685 11.7348 15.4458 14.3851L20.6014 19.5407C20.8943 19.8336 20.8943 20.3085 20.6014 20.6014C20.3085 20.8943 19.8337 20.8943 19.5408 20.6014L14.3851 15.4457ZM6.45932 13.8839C4.40907 11.8336 4.40907 8.50951 6.45932 6.45926C8.50957 4.40901 11.8337 4.40901 13.8839 6.45926C15.9327 8.50801 15.9342 11.8287 13.8885 13.8794C13.8869 13.8809 13.8854 13.8823 13.8839 13.8839C13.8824 13.8854 13.8809 13.8869 13.8794 13.8884C11.8288 15.9341 8.50807 15.9326 6.45932 13.8839Z"
                                    fill="#bfbfbf"
                                />
                            </svg>
                            <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </button>

                        <div v-if="showSearch && searchQuery" class="absolute right-0 top-12 z-[540] w-[360px] rounded-xs bg-accent p-3 shadow-lg">
                            <div v-if="searchLoading" class="text-xs text-text-secondary">Поиск...</div>
                            <div v-else-if="!searchResults.length" class="text-xs text-text-secondary">Ничего не найдено.</div>
                            <div v-else class="space-y-2">
                                <Link
                                    v-for="item in searchResults"
                                    :key="item.id"
                                    :href="`/anime/${item.id}`"
                                    class="flex items-center gap-3 rounded-xs bg-secondary/70 p-2 transition hover:bg-secondary"
                                    @click="closeSearch"
                                >
                                    <div class="h-14 w-10 overflow-hidden rounded-xs bg-secondary">
                                        <img v-if="item.cover_url" :src="item.cover_url" alt="" class="h-full w-full object-cover" />
                                    </div>
                                    <div class="text-xs text-text-secondary">
                                        <div class="text-sm text-text-primary">{{ clampText(item.title, 25) }}</div>
                                        <div class="text-text-secondary">{{ clampText(item.alt_title, 25) }}</div>
                                        <div class="mt-1 text-text-secondary">
                                            {{ item.season_label || '-' }} | {{ item.type || '-' }}
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                    <Link
                        class="flex items-center gap-2 rounded-xs bg-secondary px-3 py-2 text-sm text-text-primary transition hover:bg-secondary-hover"
                        href="/login"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path
                                d="M15 3H9C7.343 3 6 4.343 6 6v12c0 1.657 1.343 3 3 3h6"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                            />
                            <path
                                d="M10 12h10m0 0-3-3m3 3-3 3"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <span>Вход/регистрация</span>
                    </Link>
                </template>
            </div>
        </div>
    </header>
</template>





