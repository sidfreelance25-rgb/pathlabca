import { Link, usePage } from '@inertiajs/react';
import {
    Barcode,
    BookOpen,
    FileText,
    FlaskConical,
    LayoutGrid,
    ScanLine,
    ShieldAlert,
    SwatchBook,
    TestTubes,
    Users,
} from 'lucide-react';
import AppearanceToggleTab from '@/components/appearance-tabs';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const icons = {
    dashboard: LayoutGrid,
    patients: Users,
    catalogue: TestTubes,
    orders: FlaskConical,
    collection: Barcode,
    accession: ScanLine,
    reports: FileText,
    critical: ShieldAlert,
    templates: SwatchBook,
};

const footerNavItems: NavItem[] = [
    {
        title: 'Catalogue sources',
        href: 'https://www.abim.org/media/e2wdwdqu/laboratory-reference-ranges.pdf',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { labNav } = usePage().props as {
        labNav?: { title: string; href: string; icon: keyof typeof icons }[];
    };
    const { state } = useSidebar();
    const mainNavItems: NavItem[] = (labNav ?? []).map((item) => ({
        title: item.title,
        href: item.href,
        icon: icons[item.icon] ?? LayoutGrid,
    }));

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <div className="px-2 pb-2 group-data-[collapsible=icon]:px-1">
                    <p className="mb-1 px-2 text-xs text-muted-foreground group-data-[collapsible=icon]:hidden">
                        Theme
                    </p>
                    <AppearanceToggleTab
                        className="w-full justify-between group-data-[collapsible=icon]:flex-col"
                        showLabels={state !== 'collapsed'}
                    />
                </div>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
