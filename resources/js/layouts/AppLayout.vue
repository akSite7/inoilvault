<script setup>
import Header from '../components/Header.vue';
</script>

<template>
    <div class="app-shell min-h-screen text-text-primary">
        <Header />
        <main class="flex-1">
            <slot />
        </main>
    </div>
</template>

