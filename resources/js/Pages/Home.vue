<template>
    <div>
        <h1>{{ message }}</h1>
        <p>Laravel + Inertia + Vue is working! 🎉 🤣🤣🤣🤣</p>
    </div>
    <div style="color: red; font-size: 24px">test text</div>
</template>

<script setup>
defineProps({
    message: String,
});
</script>
