<template>
    <div class="min-h-screen bg-[#2a2820] flex items-center justify-center">
        <div class="w-full max-w-sm bg-[#383838] border border-[#4f4943] rounded-lg p-8">
            <h1 class="font-['Reenie_Beanie'] text-4xl text-[#c3e062] mb-6 text-center">chimbi admin</h1>
            <form @submit.prevent="submit">
                <input
                    v-model="password"
                    type="password"
                    placeholder="Password"
                    class="w-full bg-[#1e1c18] border border-[#4f4943] rounded px-3 py-2
                           text-[#ebe5cb] outline-none focus:border-[#c3e062] mb-3"
                    autofocus
                />
                <p v-if="error" class="text-red-400 text-sm mb-3">{{ error }}</p>
                <button
                    type="submit"
                    class="w-full py-2 bg-[#c3e062] text-[#2a2820] font-bold rounded
                           hover:bg-[#d4ef73] font-['Reenie_Beanie'] text-2xl"
                >Enter</button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const password = ref('');
const error    = ref(usePage().props.errors?.password ?? '');

function submit() {
    router.post('/chimbi/login', { password: password.value }, {
        onError: (e) => { error.value = e.password ?? 'Wrong password.'; }
    });
}
</script>
