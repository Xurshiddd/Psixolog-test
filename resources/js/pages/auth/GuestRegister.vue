<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/GuestAuthLayout.vue';
import { login } from '@/routes';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    first_name: '',
    last_name: '',
    father_name: '',
    phone: '',
    address: '',
    desired_position: '',
    email: '',
    password: '',
    password_confirmation: '',
    captcha: '',
    image: null as File | null,
});

const captchaSrc = ref(`/guest/captcha?${Date.now()}`);

function refreshCaptcha() {
    captchaSrc.value = `/guest/captcha?${Date.now()}`;
}

function onImageChange(event: Event) {
    const target = event.target as HTMLInputElement;
    form.image = target.files && target.files.length ? target.files[0] : null;
}

function submit() {
    form.post('/guest/register', {
        forceFormData: true,
        preserveScroll: true,
        onError: () => {
            form.captcha = '';
            refreshCaptcha();
        },
    });
}
</script>

<template>
    <AuthBase
        title="Ishga qabul qilinmaganlar uchun ro'yxatdan o'tish"
        description="Ma'lumotlaringizni to'ldiring va platformaga kiring"
    >
        <Head title="Ro'yxatdan o'tish" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="last_name">Familiya</Label>
                    <Input id="last_name" v-model="form.last_name" required autofocus />
                    <InputError :message="form.errors.last_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="first_name">Ism</Label>
                    <Input id="first_name" v-model="form.first_name" required />
                    <InputError :message="form.errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="father_name">Otasining ismi</Label>
                    <Input id="father_name" v-model="form.father_name" required />
                    <InputError :message="form.errors.father_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Telefon</Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        required
                        placeholder="+998 90 123 45 67"
                    />
                    <InputError :message="form.errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="address">Manzil</Label>
                    <Input id="address" v-model="form.address" required />
                    <InputError :message="form.errors.address" />
                </div>

                <div class="grid gap-2">
                    <Label for="desired_position">Lavozim</Label>
                    <Input
                        id="desired_position"
                        v-model="form.desired_position"
                        required
                        placeholder="Ko'zlagan lavozimingiz"
                    />
                    <InputError :message="form.errors.desired_position" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="image">Rasm</Label>
                    <input
                        id="image"
                        type="file"
                        accept="image/*"
                        @change="onImageChange"
                        class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                    />
                    <InputError :message="form.errors.image" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Parol</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Kamida 6 ta belgi"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Parolni tasdiqlang</Label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="captcha">Tasdiqlash kodi</Label>
                    <div class="flex items-center gap-3">
                        <img
                            :src="captchaSrc"
                            alt="captcha"
                            class="h-14 w-40 rounded-lg border border-gray-200 bg-gray-50"
                        />
                        <button
                            type="button"
                            @click="refreshCaptcha"
                            class="rounded-lg border border-gray-200 p-2 text-gray-600 transition hover:bg-gray-100"
                            aria-label="Yangilash"
                            title="Yangilash"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h5M20 20v-5h-5M5.5 9a7 7 0 0111.9-2.5L20 9M18.5 15a7 7 0 01-11.9 2.5L4 15" />
                            </svg>
                        </button>
                    </div>
                    <Input
                        id="captcha"
                        v-model="form.captcha"
                        required
                        autocomplete="off"
                        placeholder="Rasmdagi kodni kiriting"
                    />
                    <InputError :message="form.errors.captcha" />
                </div>

                <Button type="submit" class="mt-2 w-full sm:col-span-2" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Ro'yxatdan o'tish
                </Button>

                <div class="text-center text-sm text-muted-foreground sm:col-span-2">
                    Akkountingiz bormi?
                    <TextLink :href="login()">Kirish</TextLink>
                </div>
            </div>
        </form>
    </AuthBase>
</template>
