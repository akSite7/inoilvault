<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    status: {
        type: Object,
        required: true,
    },
    last_activity: {
        type: Number,
        required: true,
    },
    force_offline: {
        type: Boolean,
        required: true,
    },
    friend_status: {
        type: String,
        default: 'none',
    },
    friend_request_id: {
        type: Number,
        default: null,
    },
    friends_preview: {
        type: Array,
        default: () => [],
    },
    friends_count: {
        type: Number,
        default: 0,
    },
    anime_counts: {
        type: Object,
        default: () => ({
            total: 0,
            episodes: 0,
            watching: 0,
            completed: 0,
            on_hold: 0,
            dropped: 0,
            planned: 0,
        }),
    },
});

const form = useForm({});
const page = usePage();

const initials = computed(() => (props.user?.username ? props.user.username.charAt(0).toUpperCase() : '?'));
const isOwner = computed(() => page.props?.auth?.user?.username === props.user?.username);
const nowTs = ref(Date.now());
let tickId = null;

const statusView = computed(() => {
    const lastActivityMs = props.last_activity ? props.last_activity * 1000 : null;
    if (!lastActivityMs) {
        return {
            label: 'Офлайн',
            detail: 'Нет активности',
        };
    }

    const diffMs = Math.max(0, nowTs.value - lastActivityMs);
    let minutes = Math.floor(diffMs / 60000);

    if (minutes < 2 && !props.force_offline) {
        return {
            label: 'Онлайн',
            detail: 'Сейчас',
        };
    }

    minutes = Math.max(1, minutes);
    if (minutes < 60) {
        return {
            label: 'Офлайн',
            detail: `Был(а) в сети ${minutes} ${formatMinutes(minutes)} назад`,
        };
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return {
            label: 'Офлайн',
            detail: `Был(а) в сети ${hours} ${formatHours(hours)} назад`,
        };
    }

    const days = Math.floor(hours / 24);
    if (days < 7) {
        return {
            label: 'Офлайн',
            detail: `Был(а) в сети ${days} ${formatDays(days)} назад`,
        };
    }

    const weeks = Math.floor(days / 7);
    if (weeks < 5) {
        return {
            label: 'Офлайн',
            detail: `Был(а) в сети ${weeks} ${formatWeeks(weeks)} назад`,
        };
    }

    const months = Math.floor(days / 30);
    if (months < 12) {
        return {
            label: 'Офлайн',
            detail: `Был(а) в сети ${months} ${formatMonths(months)} назад`,
        };
    }

    const years = Math.floor(days / 365);
    return {
        label: 'Офлайн',
        detail: `Был(а) в сети ${years} ${formatYears(years)} назад`,
    };
});
const showToast = ref(false);
const toastMessage = ref('');
const toastKeyPrefix = 'profile.toast.seen';

const triggerToast = (message, id) => {
    const key = id ? `${toastKeyPrefix}:${id}` : `${toastKeyPrefix}:${message}`;
    if (window.sessionStorage.getItem(key)) {
        return;
    }

    window.sessionStorage.setItem(key, '1');
    toastMessage.value = message;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 2600);
};

onMounted(() => {
    const message = page.props?.flash?.success;
    const id = page.props?.flash?.success_id;
    if (message) {
        triggerToast(message, id);
    }

    tickId = window.setInterval(() => {
        nowTs.value = Date.now();
    }, 60000);
});

onBeforeUnmount(() => {
    if (tickId) {
        window.clearInterval(tickId);
        tickId = null;
    }
});

watch(
    () => page.props?.flash?.success,
    (message) => {
        const id = page.props?.flash?.success_id;
        if (message) {
            triggerToast(message, id);
        }
    },
);

const submit = () => {
    form.post('/logout');
};

const sendFriendRequest = () => {
    router.post(`/friends/request/${props.user.id}`, {}, { preserveScroll: true });
};

const acceptFriendRequest = () => {
    if (!props.friend_request_id) return;
    router.post(`/friends/request/${props.friend_request_id}/accept`, {}, { preserveScroll: true });
};

const declineFriendRequest = () => {
    if (!props.friend_request_id) return;
    router.post(`/friends/request/${props.friend_request_id}/decline`, {}, { preserveScroll: true });
};

const cancelFriendRequest = () => {
    if (!props.friend_request_id) return;
    router.delete(`/friends/request/${props.friend_request_id}`, { preserveScroll: true });
};

const removeFriend = () => {
    router.delete(`/friends/${props.user.id}`, { preserveScroll: true });
};

const formatMinutes = (value) => {
    const mod100 = value % 100;
    if (mod100 >= 11 && mod100 <= 14) {
        return 'минут';
    }
    const mod10 = value % 10;
    if (mod10 === 1) return 'минуту';
    if (mod10 >= 2 && mod10 <= 4) return 'минуты';
    return 'минут';
};

const formatHours = (value) => {
    const mod100 = value % 100;
    if (mod100 >= 11 && mod100 <= 14) {
        return 'часов';
    }
    const mod10 = value % 10;
    if (mod10 === 1) return 'час';
    if (mod10 >= 2 && mod10 <= 4) return 'часа';
    return 'часов';
};

const formatDays = (value) => {
    const mod100 = value % 100;
    if (mod100 >= 11 && mod100 <= 14) {
        return 'дней';
    }
    const mod10 = value % 10;
    if (mod10 === 1) return 'день';
    if (mod10 >= 2 && mod10 <= 4) return 'дня';
    return 'дней';
};

const formatWeeks = (value) => {
    const mod100 = value % 100;
    if (mod100 >= 11 && mod100 <= 14) {
        return 'недель';
    }
    const mod10 = value % 10;
    if (mod10 === 1) return 'неделю';
    if (mod10 >= 2 && mod10 <= 4) return 'недели';
    return 'недель';
};

const formatMonths = (value) => {
    const mod100 = value % 100;
    if (mod100 >= 11 && mod100 <= 14) {
        return 'месяцев';
    }
    const mod10 = value % 10;
    if (mod10 === 1) return 'месяц';
    if (mod10 >= 2 && mod10 <= 4) return 'месяца';
    return 'месяцев';
};

const formatYears = (value) => {
    const mod100 = value % 100;
    if (mod100 >= 11 && mod100 <= 14) {
        return 'лет';
    }
    const mod10 = value % 10;
    if (mod10 === 1) return 'год';
    if (mod10 >= 2 && mod10 <= 4) return 'года';
    return 'лет';
};

const formatNumber = (value) => new Intl.NumberFormat('ru-RU').format(value ?? 0);

const isAdmin = (role) => role === 'admin';
const isModerator = (role) => role === 'moderator';
</script>

<template>
    <div class="w-full">
        <div class="max-w-[1440px] mx-auto p-5">
            <div class="bg-accent rounded mb-5 border border-secondary/60">
                <div class="relative h-100">
                    <div class="relative w-full h-full bg-secondary rounded-t">
                        <img v-if="user.cover_url" :src="user.cover_url" alt="Обложка" class="rounded-t h-full w-full object-cover" />
                        <div v-else class="absolute inset-0 flex items-center justify-center text-sm text-text-secondary">
                            Обложка отсутствует.
                        </div>
                        <div class="absolute ml-5 bottom-[-40px]">
                            <div class="w-34 h-34 rounded border-4 border-accent bg-secondary flex items-center justify-center overflow-hidden">
                                <img
                                    :src="user.avatar_url || '/storage/images/placeholders/avatar-placeholder.png'"
                                    alt="Аватар"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pt-11 px-6 pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-2xl font-semibold mt-[2px] mr-1.5 flex items-center gap-1">
                                {{ user.username }}
                                <span v-if="isAdmin(user.role)" class="inline-flex">
                                    <svg
                                        class="h-5 w-5 text-blue-400"
                                        fill="currentColor"
                                        version="1.0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 64 64"
                                        xml:space="preserve"
                                    >
                                        <g>
                                            <path
                                                d="M63.652,14.49c-0.277-1.584-1.283-1.544-2.209-0.618s-9.787,9.786-9.787,9.786c-3.123,3.123-8.189,3.123-11.312-0.001c-3.125-3.124-3.125-8.188,0-11.313c0,0,8.963-8.963,9.787-9.786c0.822-0.823,0.9-1.901-0.621-2.21C48.375,0.117,47.201,0,46,0c-9.941,0-18,8.06-18,18s8.059,18,18,18s18-8.06,18-18C64,16.799,63.852,15.631,63.652,14.49z"
                                            />
                                            <path d="M2.342,50.344c-3.123,3.123-3.123,8.189,0.001,11.313s8.19,3.124,11.313,0.001l7.794-7.794L10.136,42.55L2.342,50.344z M8,59c-1.656,0-3-1.344-3-3s1.344-3,3-3s3,1.344,3,3S9.657,59,8,59z" />
                                            <path d="M27.393,25.293L11.55,41.136L22.865,52.45l15.842-15.843C33.535,34.578,29.422,30.466,27.393,25.293z" />
                                        </g>
                                    </svg>
                                </span>
                                <span v-else-if="isModerator(user.role)" class="inline-flex">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4.35009 13.3929L8 16L11.6499 13.3929C13.7523 11.8912 15 9.46667 15 6.88306V3L8 0L1 3V6.88306C1 9.46667 2.24773 11.8912 4.35009 13.3929Z" />
                                    </svg>
                                </span>
                            </span>
                            <span class="w-3 h-3 mt-1 rounded-full" :class="statusView.label === 'Онлайн' ? 'bg-green-500' : 'bg-red-400'"></span>
                            <span class="mb-[3px] text-green-600 ml-1 mt-1" v-if="statusView.label === 'Онлайн'">сейчас на сайте</span>
                            <span class="text-sm  ml-1 mt-1" v-else>{{ statusView.detail }}</span>
                        </div>
                        <Link
                            v-if="isOwner"
                            class="flex gap-2 text-text-primary bg-secondary hover:bg-secondary-hover px-2 rounded-xs cursor-pointer transition duration-200"
                            :href="`/profile/${user.username}/settings`"
                        >
                            <svg class="mt-[8px]" width="21" height="21" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_3_10266)">
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M8.99984 3.22263C8.99984 2.75606 9.32248 2.3515 9.77735 2.24769C11.2596 1.90944 12.7791 1.92079 14.2224 2.2501C14.6773 2.35389 14.9999 2.75846 14.9999 3.22504L14.9999 5.07178C14.9999 5.84158 15.8333 6.3227 16.4999 5.9378L18.101 5.01345C18.5054 4.77993 19.0176 4.85747 19.3349 5.20022C19.8331 5.73857 20.279 6.33981 20.6601 7.00004C21.0413 7.66024 21.3391 8.34696 21.5562 9.04761C21.6944 9.49371 21.5054 9.97608 21.101 10.2096L19.4999 11.134C18.8332 11.5189 18.8332 12.4811 19.4999 12.866L21.0993 13.7894C21.5034 14.0227 21.6924 14.5044 21.5548 14.9503C21.1184 16.3648 20.3684 17.6865 19.3344 18.801C19.0171 19.143 18.5054 19.2201 18.1013 18.9869L16.4999 18.0623C15.8333 17.6774 14.9999 18.1585 14.9999 18.9283L14.9999 20.7775C14.9999 21.244 14.6773 21.6486 14.2224 21.7524C12.7402 22.0906 11.2206 22.0793 9.77738 21.75C9.32249 21.6462 8.99983 21.2416 8.99983 20.775L8.99984 18.9282C8.99984 18.1584 8.1665 17.6773 7.49983 18.0622L5.89878 18.9866C5.49432 19.2201 4.98209 19.1426 4.66487 18.7998C4.16664 18.2615 3.72081 17.6602 3.33964 17C2.95846 16.3398 2.66069 15.6531 2.44359 14.9524C2.30537 14.5063 2.49433 14.0239 2.89879 13.7904L4.49981 12.8661C5.16647 12.4812 5.16648 11.5189 4.49981 11.134L2.9005 10.2107C2.49643 9.97737 2.30739 9.49565 2.44495 9.04981C2.88139 7.63526 3.63134 6.31361 4.66537 5.1991C4.98271 4.85707 5.49439 4.77994 5.89845 5.01322L7.49984 5.93778C8.1665 6.32268 8.99984 5.84155 8.99983 5.07175L8.99984 3.22263ZM12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z"
                                        fill="#bfbfbf"
                                    />
                                </g>
                            </svg>
                            <span class="text-sm py-2 ">Настройки</span>
                        </Link>
                        <div v-else class="flex items-center gap-2">
                            <button
                                v-if="friend_status === 'none'"
                                class="flex gap-2 text-text-primary bg-secondary hover:bg-secondary-hover py-2 px-3 text-sm rounded-xs cursor-pointer transition duration-200"
                                type="button"
                                @click="sendFriendRequest"
                            >
                            <svg fill="#bfbfbf" width="20px" height="20px" viewBox="0 -64 640 640" xmlns="http://www.w3.org/2000/svg"><path d="M624 208h-64v-64c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v64h-64c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h64v64c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-64h64c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm-400 48c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"/></svg>

                                Добавить в друзья
                            </button>
                            <div v-else-if="friend_status === 'outgoing'" class="flex items-center gap-2">
                                <span class="text-sm text-text-secondary">Заявка в друзья отправлена</span>
                                <button
                                    class="text-text-primary cursor-pointer bg-secondary hover:bg-secondary-hover py-2 px-3 rounded-xs text-sm transition duration-200"
                                    type="button"
                                    @click="cancelFriendRequest"
                                >
                                    Отменить заявку
                                </button>
                            </div>
                            <div v-else-if="friend_status === 'incoming'" class="flex items-center gap-2">
                                <button
                                    class="text-white bg-primary cursor-pointer hover:bg-primary-hover py-2 px-3 rounded-xs text-sm transition duration-200"
                                    type="button"
                                    @click="acceptFriendRequest"
                                >
                                    Принять
                                </button>
                                <button
                                    class="text-text-primary bg-secondary cursor-pointer hover:bg-secondary-hover py-2 px-3 rounded-xs text-sm transition duration-200"
                                    type="button"
                                    @click="declineFriendRequest"
                                >
                                    Отклонить
                                </button>
                            </div>
                            <button
                                v-else
                                class="flex gap-2 text-text-primary bg-secondary hover:bg-secondary-hover py-2 px-3  cursor-pointer rounded-xs text-sm transition duration-200"
                                type="button"
                                @click="removeFriend"
                            >
                                <svg fill="#bfbfbf" width="20px" height="20px" viewBox="0 -64 640 640" xmlns="http://www.w3.org/2000/svg"><path d="M624 208H432c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h192c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm-400 48c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"/></svg>

                                <span>Удалить из друзей</span>
                            </button>
                        </div>
                    </div>
                    <p class="text-sm max-w-[900px] text-text-secondary mt-1 break-words">{{ user.bio || 'Описание отсутствует.' }}</p>
                </div>
                <div class="flex items-center gap-3"></div>
            </div>
            <!-- Нижний блок -->
            <div class="flex flex-col gap-5 md:flex-row">
                <!-- Аниме -->
                <div class="rounded w-full bg-accent p-5 pt-3 md:w-[37.5%] border border-secondary/60">
                    <div class="flex items-center gap-2 font-semibold">
                        <Link :href="`/profile/${user.username}/anime-list`" class="text-xl mb-1">
                            <div class="flex">
                                <svg class="mr-2 mt-[4px]" svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M7.70711 1.29289C7.31658 0.902369 6.68342 0.902369 6.29289 1.29289C5.90237 1.68342 5.90237 2.31658 6.29289 2.70711L9.58579 6H4C2.34315 6 1 7.34315 1 9V20C1 21.6569 2.34315 23 4 23H20C21.6569 23 23 21.6569 23 20V9C23 7.34315 21.6569 6 20 6H14.4142L17.7071 2.70711C18.0976 2.31658 18.0976 1.68342 17.7071 1.29289C17.3166 0.902369 16.6834 0.902369 16.2929 1.29289L12 5.58579L7.70711 1.29289ZM3 9C3 8.44772 3.44772 8 4 8H20C20.5523 8 21 8.44771 21 9V18H3V9Z"
                                        fill="#bfbfbf"
                                    />
                                </svg>
                                Аниме
                            </div>
                        </Link>
                    </div>
                    <div class="text-text-secondary text-sm">Всего в списках: {{ anime_counts.total }}</div>
                    <div class="text-text-secondary text-sm">Всего эпизодов: {{ formatNumber(anime_counts.episodes) }}</div>
                    <div class="space-y-1 mt-2 text-sm">
                        <div class="flex items-center justify-between">
                            <Link :href="`/profile/${user.username}/anime-list?status=watching`" class="flex items-center gap-2 ">
                                <span class="h-3 w-3 rounded-full bg-blue-400 "></span>
                                <span class="text-text-primary hover:underline">Смотрю:</span>
                            </Link>
                            <Link :href="`/profile/${user.username}/anime-list?status=watching`" class="text-text-secondary font-medium">
                                {{ anime_counts.watching }}
                            </Link>
                        </div>
                        <div class="flex items-center justify-between">
                            <Link :href="`/profile/${user.username}/anime-list?status=completed`" class="flex items-center gap-2 ">
                                <span class="h-3 w-3 rounded-full bg-green-400"></span>
                                <span class="text-text-primary hover:underline">Просмотрено:</span>
                            </Link>
                            <Link :href="`/profile/${user.username}/anime-list?status=completed`" class="text-text-secondary font-medium">
                                {{ anime_counts.completed }}
                            </Link>
                        </div>
                        <div class="flex items-center justify-between">
                            <Link :href="`/profile/${user.username}/anime-list?status=on_hold`" class="flex items-center gap-2 ">
                                <span class="h-3 w-3 rounded-full bg-gray-300"></span>
                                <span class="text-text-primary hover:underline">Отложено:</span>
                            </Link>
                            <Link :href="`/profile/${user.username}/anime-list?status=on_hold`" class="text-text-secondary font-medium">
                                {{ anime_counts.on_hold }}
                            </Link>
                        </div>
                        <div class="flex items-center justify-between">
                            <Link :href="`/profile/${user.username}/anime-list?status=dropped`" class="flex items-center gap-2 ">
                                <span class="h-3 w-3 rounded-full bg-red-400"></span>
                                <span class="text-text-primary hover:underline">Брошено:</span>
                            </Link>
                            <Link :href="`/profile/${user.username}/anime-list?status=dropped`" class="text-text-secondary font-medium">
                                {{ anime_counts.dropped }}
                            </Link>
                        </div>
                        <div class="flex items-center justify-between">
                            <Link :href="`/profile/${user.username}/anime-list?status=planned`" class="flex items-center gap-2 ">
                                <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                                <span class="text-text-primary hover:underline">Запланировано:</span>
                            </Link>
                            <Link :href="`/profile/${user.username}/anime-list?status=planned`" class="text-text-secondary font-medium">
                                {{ anime_counts.planned }}
                            </Link>
                        </div>
                    </div>
                </div>
                <!-- Манга -->
                <div class="rounded w-full bg-accent p-5 pt-3 md:w-[37.5%] relative overflow-hidden border border-secondary/60">
                    <div class="absolute inset-0 z-10 flex items-center justify-center text-base text-text-secondary">
                        В разработке.
                    </div>
                    <div class="blur-sm pointer-events-none">
                        <div class="flex items-center gap-2 font-semibold">
                            <Link :href="`/profile/${user.username}/anime-list`" class="text-xl mb-1">
                                <div class="flex">
                                    <svg class="mr-[6px] mt-[5px]" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            fill-rule="evenodd"
                                            clip-rule="evenodd"
                                            d="M6.5 1C5.57174 1 4.6815 1.36875 4.02513 2.02513C3.36875 2.6815 3 3.57174 3 4.5V19.5C3 20.4283 3.36875 21.3185 4.02513 21.9749C4.6815 22.6313 5.57174 23 6.5 23H20C20.5523 23 21 22.5523 21 22V2C21 1.44772 20.5523 1 20 1H6.5ZM7 16.5H19.5V21.5H7C5.61929 21.5 4.5 20.3807 4.5 19C4.5 17.6193 5.61929 16.5 7 16.5Z"
                                            fill="#bfbfbf"
                                        />
                                    </svg>
                                    Манга
                                </div>
                            </Link>
                        </div>
                        <div class="text-text-secondary text-sm">Всего в списках: 0</div>
                        <div class="text-text-secondary text-sm">Всего глав: 0</div>
                        <div class="space-y-1 mt-2 text-sm">
                            <div class="flex items-center justify-between">
                                <Link :href="`/profile/${user.username}/anime-list?status=watching`" class="flex items-center gap-2 ">
                                    <span class="h-3 w-3 rounded-full bg-blue-400 "></span>
                                    <span class="text-text-primary hover:underline">Читаю:</span>
                                </Link>
                                <Link :href="`/profile/${user.username}/anime-list?status=watching`" class="text-text-secondary font-medium">0</Link>
                            </div>
                            <div class="flex items-center justify-between">
                                <Link :href="`/profile/${user.username}/anime-list?status=completed`" class="flex items-center gap-2 ">
                                    <span class="h-3 w-3 rounded-full bg-green-400"></span>
                                    <span class="text-text-primary hover:underline">Прочитано:</span>
                                </Link>
                                <Link :href="`/profile/${user.username}/anime-list?status=completed`" class="text-text-secondary font-medium">0</Link>
                            </div>
                            <div class="flex items-center justify-between">
                                <Link :href="`/profile/${user.username}/anime-list?status=on_hold`" class="flex items-center gap-2 ">
                                    <span class="h-3 w-3 rounded-full bg-gray-300"></span>
                                    <span class="text-text-primary hover:underline">Отложено:</span>
                                </Link>
                                <Link :href="`/profile/${user.username}/anime-list?status=on_hold`" class="text-text-secondary font-medium">0</Link>
                            </div>
                            <div class="flex items-center justify-between">
                                <Link :href="`/profile/${user.username}/anime-list?status=dropped`" class="flex items-center gap-2 ">
                                    <span class="h-3 w-3 rounded-full bg-red-400"></span>
                                    <span class="text-text-primary hover:underline">Брошено:</span>
                                </Link>
                                <Link :href="`/profile/${user.username}/anime-list?status=dropped`" class="text-text-secondary font-medium">0</Link>
                            </div>
                            <div class="flex items-center justify-between">
                                <Link :href="`/profile/${user.username}/anime-list?status=planned`" class="flex items-center gap-2 ">
                                    <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                                    <span class="text-text-primary hover:underline">Запланировано:</span>
                                </Link>
                                <Link :href="`/profile/${user.username}/anime-list?status=planned`" class="text-text-secondary font-medium">0</Link>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Друзья -->
                <div class="w-full rounded bg-accent p-4 md:w-[25%] border border-secondary/60">
                    <div class="flex items-center gap-1">
                        <Link :href="`/profile/${user.username}/friends`" class="hover:text-text-primary">
                            <div class="flex font-semibold">
                                    <svg class="mr-2 mt-[2px]" fill="#bfbfbf" width="21px" height="21px" viewBox="0 -64 640 640" xmlns="http://www.w3.org/2000/svg"><path d="M192 256c61.9 0 112-50.1 112-112S253.9 32 192 32 80 82.1 80 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C51.6 288 0 339.6 0 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zM480 256c53 0 96-43 96-96s-43-96-96-96-96 43-96 96 43 96 96 96zm48 32h-3.8c-13.9 4.8-28.6 8-44.2 8s-30.3-3.2-44.2-8H432c-20.4 0-39.2 5.9-55.7 15.4 24.4 26.3 39.7 61.2 39.7 99.8v38.4c0 2.2-.5 4.3-.6 6.4H592c26.5 0 48-21.5 48-48 0-61.9-50.1-112-112-112z"/></svg>
                                Друзья
                            </div>
                        </Link>
                        <span class="text-sm text-text-secondary mt-[2px]">{{ friends_count }}</span>
                    </div>
                    <div class="mt-3">
                        <div v-if="!friends_preview.length" class="flex min-h-[130px] items-center justify-center text-center text-sm text-text-secondary">
                            У {{ user.username }} друзей нет.
                        </div>
                        <div v-else class="grid grid-cols-5 gap-3">
                            <div v-for="friend in friends_preview" :key="friend.id" class="text-xs">
                                <Link :href="`/profile/${friend.username}`" class="flex w-[52px] flex-col items-start">
                                    <div class="relative">
                                        <img
                                            :src="friend.avatar_url"
                                            alt=""
                                            class="h-13 w-13 rounded-xs object-cover  border-b-[2px]"
                                            :class="friend.is_online ? 'border-green-500' : 'border-red-400'"
                                        />
                                    </div>
                                    <div class="mt-1 flex w-[50px] items-center text-center text-text-secondary">
                                        <span class="truncate min-w-0 flex-1">
                                            {{ friend.username }}
                                        </span>
                                    </div>
                                </Link>
                                
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Уведомление о сохранении изменений в профиле -->
        <transition
            enter-active-class="transition transform duration-300"
            enter-from-class="translate-x-4 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition transform duration-500"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-6 opacity-0"
        >
            <div
                v-if="showToast"
                class="fixed right-6 top-20 z-[200] flex items-center gap-2 rounded-xs bg-accent border border-secondary px-4 py-4 text-sm text-text-primary shadow-lg"
            >
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-text-primary">
                    <svg width="800px" height="800px" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
                        <path fill="oklch(72.3% 0.219 149.579)" d="M512 64a448 448 0 1 1 0 896 448 448 0 0 1 0-896zm-55.808 536.384-99.52-99.584a38.4 38.4 0 1 0-54.336 54.336l126.72 126.72a38.272 38.272 0 0 0 54.336 0l262.4-262.464a38.4 38.4 0 1 0-54.272-54.336L456.192 600.384z"/>
                    </svg>

                </span>
                <span>{{ toastMessage }}</span>
            </div>
        </transition>
    </div>
</template>

