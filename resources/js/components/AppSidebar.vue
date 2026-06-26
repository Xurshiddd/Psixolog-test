<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, LayoutGrid, MessageSquare, Users, Activity, Briefcase, UserPlus } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const role = computed(() => (page.props.auth as any)?.user?.role as string);
const route = role.value === 'admin' ? '/admin/requests' : '/psiholog/requests';
const unreadCount = computed(
    () => (page.props.unread_requests_count as number) || 0,
);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Tests',
        href: '/test/index',
        icon: BookOpen,
    },
    {
        title: 'Natijalar Kategoriyalari',
        href: '/result-categories',
        icon: BookOpen,
    },
    {
        title: 'Talabalar',
        href: '/admin/students',
        icon: Users,
    },
    {
        title: 'Xodimlar',
        href: '/admin/employees',
        icon: Briefcase,
    },
    {
        title: 'Ishga qabul qilinmaganlar',
        href: '/admin/guests',
        icon: UserPlus,
    },
    {
        title: 'Kategoriyalar',
        href: '/categories',
        icon: LayoutGrid,
    },
    {
        title: 'Murojaatlar',
        href: route,
        icon: MessageSquare,
        badge: unreadCount.value > 0 ? unreadCount.value : undefined,
    },
    ...(role.value === 'admin' ? [{
        title: 'Tizim loglari',
        href: '/admin/activity-logs',
        icon: Activity,
    }] : []),
]);

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
