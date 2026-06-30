<script setup lang="ts">
definePageMeta({
    layout: "default",
});

useHead({
    title: "Accueil",
});
</script>

<template>
    <div class="container-page">
        <section class="text-center py-16">
            <h1 class="text-3xl font-bold mb-4" v-if="authStore.user">
                - Bonjour {{ authStore.user.firstName }} -
            </h1>
        </section>
    </div>
</template>
