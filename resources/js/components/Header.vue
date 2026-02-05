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
const notificationWrap = ref(null);
const notificationsOpen = ref(false);
const notifications = ref([]);
const notificationsLoading = ref(false);
const notificationsUnread = ref(0);
const notificationsHasMore = ref(false);

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
const fetchNotifications = async () => {
    if (!user.value) return;
    try {
        notificationsLoading.value = true;
        const response = await fetch('/notifications?per_page=5', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) {
            notifications.value = [];
            notificationsUnread.value = 0;
            notificationsHasMore.value = false;
            return;
        }
        const data = await response.json();
        notifications.value = data.items || [];
        notificationsUnread.value = data.unread ?? 0;
        notificationsHasMore.value = data.pagination?.has_more ?? false;
    } catch (error) {
        notifications.value = [];
        notificationsHasMore.value = false;
    } finally {
        notificationsLoading.value = false;
    }
};

const getNotificationCommentId = (item) => {
    const directId = item?.comment_id;
    if (directId) return String(directId);
    const url = item?.url;
    if (!url) return null;
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.searchParams.get('comment');
    } catch (error) {
        return null;
    }
};
const markNotificationsRead = async () => {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        await fetch('/notifications/read', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        });
        notificationsUnread.value = 0;
        const now = new Date().toISOString();
        notifications.value = notifications.value.map((item) =>
            item.read_at ? item : { ...item, read_at: now },
        );
    } catch (error) {
    }
};

const deleteNotification = async (item) => {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(`/notifications/${item.id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        });
        if (!response.ok) return;
        const data = await response.json().catch(() => ({}));
        notifications.value = notifications.value.filter((notification) => notification.id !== item.id);
        if (typeof data.unread === 'number') {
            notificationsUnread.value = data.unread;
        }
        if (notifications.value.length < 5 && notificationsHasMore.value) {
            await fetchNotifications();
        }
    } catch (error) {
    }
};

const handleNotificationClick = (item) => {
    const id = getNotificationCommentId(item);
    if (id) {
        sessionStorage.setItem('scroll_comment_id', id);
        sessionStorage.setItem('scroll_comment_once', '1');
    }
    closeNotifications();
};
const closeNotifications = () => {
    notificationsOpen.value = false;
};

const toggleNotifications = async () => {
    if (notificationsOpen.value) {
        closeNotifications();
        return;
    }
    notificationsOpen.value = true;
    await fetchNotifications();
    if (notificationsUnread.value > 0) {
        await markNotificationsRead();
    }
};




const clampText = (value, max = 100) => {
    const text = String(value ?? '');
    if (text.length <= max) return text;
    if (max <= 3) return text.slice(0, max);
    return `${text.slice(0, max - 3)}...`;
};
const formatNotificationMessage = (item, max) => {
    if (!item) return '';
    const body = item.comment_body;
    if (body && (item.type === 'comment_reply' || item.type === 'comment_like')) {
        const actorName = item.actor?.username || '';
        const action = item.type === 'comment_reply'
            ? 'ответил(а) на ваш комментарий:'
            : 'лайкнул(а) ваш комментарий:';
        const snippet = clampText(body, max);
        return `${actorName} ${action}\n"${snippet}".`;
    }
    if (item.message && max) {
        return clampText(item.message, max);
    }
    return item.message || '';
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
    const target = event.target;
    if (showSearch.value && searchWrap.value && !searchWrap.value.contains(target)) {
        closeSearch();
    }
    if (notificationsOpen.value && notificationWrap.value && !notificationWrap.value.contains(target)) {
        closeNotifications();
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick, true);

    if (user.value) {
        fetchNotifications();
    }

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
    document.removeEventListener('click', handleOutsideClick, true);
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
                    <div ref="notificationWrap" class="relative" @click.stop>
                        <button
                            class="relative flex cursor-pointer h-9 w-9 items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                            type="button"
                            @click="toggleNotifications"
                        >
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path
                                    d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2Zm6-6V11a6 6 0 0 0-4-5.659V4a2 2 0 1 0-4 0v1.341A6 6 0 0 0 6 11v5l-2 2v1h16v-1l-2-2Z"
                                    fill="currentColor"
                                />
                            </svg>
                            <span
                                v-if="notificationsUnread"
                                class="absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-primary px-1 text-[12px] font-semibold text-white"
                            >
                                {{ notificationsUnread }}
                            </span>
                        </button>
                        <div
                            v-if="notificationsOpen"
                            class="absolute right-0 top-12 z-[540] w-[400px] rounded-xs bg-accent p-3 shadow-lg border border-secondary/60"
                        >
                            <div v-if="notificationsLoading" class="text-xs text-text-secondary">Загрузка...</div>
                            <div v-else-if="!notifications.length" class="text-xs text-text-secondary">Нет уведомлений.</div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="item in notifications"
                                    :key="item.id"
                                    class="flex items-start gap-2 rounded-xs p-2 transition hover:bg-secondary"
                                    :class="item.read_at ? 'bg-secondary/70' : 'bg-secondary'"
                                >
                                    <Link
                                        :href="item.url || '#'"
                                        class="flex flex-1 items-start gap-3"
                                        @click="handleNotificationClick(item)"
                                    >
                                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-xs bg-secondary">
                                            <img
                                                :src="(item.actor && item.actor.avatar_url) || '/storage/images/placeholders/avatar-placeholder.png'"
                                                alt=""
                                                class="h-full w-full object-cover"
                                            />
                                        </div>
                                        <div class="text-xs text-text-secondary">
                                            <div class="text-sm text-text-primary">{{ item.title }}</div>
                                            <div class="whitespace-pre-line break-words overflow-wrap-anywhere">{{ formatNotificationMessage(item, 30) }}</div>
                                        </div>
                                    </Link>
                                    <button
                                        class="flex cursor-pointer h-7 w-7 self-center shrink-0 items-center justify-center rounded-xs bg-secondary text-text-secondary transition hover:bg-secondary-hover hover:text-text-primary"
                                        type="button"
                                        @click.stop.prevent="deleteNotification(item)"
                                    >
                                        <svg width="13px" height="13px" viewBox="-3 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
        <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-261.000000, -205.000000)" fill="#000000">
            <path fill="#838287" d="M268,220 C268,219.448 268.448,219 269,219 C269.552,219 270,219.448 270,220 L270,232 C270,232.553 269.552,233 269,233 C268.448,233 268,232.553 268,232 L268,220 L268,220 Z M273,220 C273,219.448 273.448,219 274,219 C274.552,219 275,219.448 275,220 L275,232 C275,232.553 274.552,233 274,233 C273.448,233 273,232.553 273,232 L273,220 L273,220 Z M278,220 C278,219.448 278.448,219 279,219 C279.552,219 280,219.448 280,220 L280,232 C280,232.553 279.552,233 279,233 C278.448,233 278,232.553 278,232 L278,220 L278,220 Z M263,233 C263,235.209 264.791,237 267,237 L281,237 C283.209,237 285,235.209 285,233 L285,217 L263,217 L263,233 L263,233 Z M277,209 L271,209 L271,208 C271,207.447 271.448,207 272,207 L276,207 C276.552,207 277,207.447 277,208 L277,209 L277,209 Z M285,209 L279,209 L279,207 C279,205.896 278.104,205 277,205 L271,205 C269.896,205 269,205.896 269,207 L269,209 L263,209 C261.896,209 261,209.896 261,211 L261,213 C261,214.104 261.895,214.999 262.999,215 L285.002,215 C286.105,214.999 287,214.104 287,213 L287,211 C287,209.896 286.104,209 285,209 L285,209 Z" id="trash" sketch:type="MSShapeGroup"></path>
        </g>
    </g>
</svg>
                                    </button>
                                </div>
                                <Link
                                    v-if="notificationsHasMore"
                                    class="block w-full rounded-xs bg-secondary px-3 py-2 text-center text-xs text-text-primary transition hover:bg-secondary-hover"
                                    href="/notifications"
                                    @click="handleNotificationClick(item)"
                                >
                                    Показать еще
                                </Link>
                            </div>
                        </div>
                    </div>
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

                        <div v-if="showSearch && searchQuery" class="absolute right-11 top-12 z-[540] w-[360px] rounded-xs bg-accent p-3 shadow-lg border border-secondary/60">
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

                        <div v-if="showSearch && searchQuery" class="absolute right-0 top-12 z-[540] w-[360px] rounded-xs bg-accent p-3 shadow-lg border border-secondary/60">
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












