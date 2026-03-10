<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Bell } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';

interface Notification {
    id: string;
    type: string;
    data: {
        student_id: number;
        student_name: string;
        module_id: number;
        module_title: string;
    };
    created_at: string;
    read_at: string | null;
}

const notifications = ref<Notification[]>([]);

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/notifications');
        notifications.value = response.data.notifications;
    } catch (error) {
        console.error('Error fetching notifications:', error);
    }
};

const markAsRead = (id: string) => {
    router.post(`/notifications/${id}/mark-read`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            fetchNotifications();
        }
    });
};

const markAllAsRead = () => {
    axios.post('/notifications/mark-all-read').then(() => {
        notifications.value = [];
    }).catch(error => {
        console.error('Error marking all as read:', error);
    });
};

onMounted(() => {
    fetchNotifications();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="h-5 w-5" />
                <span v-if="notifications.length > 0" class="absolute top-1 right-1 flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                </span>
                <span class="sr-only">Notifications</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notifications</span>
                <Button v-if="notifications.length > 0" variant="ghost" size="sm" class="h-auto p-0 text-xs text-muted-foreground hover:text-foreground" @click.stop="markAllAsRead">
                    Mark all as read
                </Button>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup class="max-h-[300px] overflow-auto">
                <template v-if="notifications.length > 0">
                    <DropdownMenuItem
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex flex-col items-start gap-1 cursor-pointer p-3"
                        @click="markAsRead(notification.id)"
                    >
                        <div class="text-sm font-medium">
                            {{ notification.data.student_name }} completed a module.
                        </div>
                        <div class="text-xs text-muted-foreground">
                            Module: {{ notification.data.module_title }}
                        </div>
                    </DropdownMenuItem>
                </template>
                <div v-else class="p-4 text-center text-sm text-muted-foreground">
                    No new notifications
                </div>
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
