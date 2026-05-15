<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import SlideOver from '@/Components/UI/SlideOver.vue';
import SelectField from '@/Components/Forms/Fields/SelectField.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    restaurantGroups: { type: Array, default: () => [] },
    roleOptions: { type: Array, default: () => [] },
    defaultPermissions: { type: Object, default: () => ({}) },
});

const { confirm } = useConfirm();

// ── Invite slide-over ──────────────────────────────────────────────────────────
const inviteOpen = ref(false);

const restaurantOptions = computed(() =>
    props.restaurantGroups.map((g) => ({ value: g.id, label: g.name })),
);

const inviteForm = useForm({
    restaurant_id: props.restaurantGroups[0]?.id ?? null,
    invited_email: '',
    invited_name: '',
    role: 'waiter',
});

watch(
    () => inviteForm.role,
    (role) => {
        if (editTarget.value) return; // don't interfere with edit form
        // permissions auto-apply on backend based on role — nothing needed here
    },
);

function openInvite() {
    inviteForm.reset();
    inviteForm.restaurant_id = props.restaurantGroups[0]?.id ?? null;
    inviteOpen.value = true;
}

function submitInvite() {
    inviteForm.post(route('merchant.staff.store'), {
        preserveScroll: true,
        onSuccess: () => {
            inviteOpen.value = false;
            inviteForm.reset();
        },
    });
}

// ── Edit slide-over ────────────────────────────────────────────────────────────
const editTarget = ref(null);

const editForm = useForm({
    role: '',
    permissions: { menu_edit: false, order_access: false, finance_access: false },
    status: 'active',
});

function openEdit(staff) {
    editTarget.value = staff;
    editForm.role = staff.role;
    editForm.permissions = { ...staff.permissions };
    editForm.status = staff.status;
}

watch(
    () => editForm.role,
    (role) => {
        if (props.defaultPermissions[role]) {
            editForm.permissions = { ...props.defaultPermissions[role] };
        }
    },
);

function submitEdit() {
    editForm.put(route('merchant.staff.update', editTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editTarget.value = null;
        },
    });
}

// ── Remove ─────────────────────────────────────────────────────────────────────
async function removeStaff(staff) {
    const ok = await confirm({
        title: 'Remove team member?',
        message: `${staff.displayName} will lose access to this restaurant.`,
        confirmLabel: 'Yes, remove',
        cancelLabel: 'Keep member',
        intent: 'danger',
    });
    if (!ok) return;
    router.delete(route('merchant.staff.destroy', staff.id), { preserveScroll: true });
}

// ── Role badge colours ─────────────────────────────────────────────────────────
const roleBadgeClass = {
    manager: 'bg-[#f0f8f8] text-[var(--brand-teal)] ring-[#d4e8e9]',
    kitchen: 'bg-orange-50 text-orange-700 ring-orange-200',
    cashier: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    waiter: 'bg-slate-100 text-slate-600 ring-slate-200',
};

const statusBadgeClass = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    suspended: 'bg-red-50 text-red-600 ring-red-200',
    pending: 'bg-amber-50 text-amber-700 ring-amber-200',
};
</script>

<template>
    <Head title="Staff & Roles" />

    <MerchantLayout>
        <div class="py-8">
            <div class="space-y-6 px-4 sm:px-6 lg:px-0">

                <!-- ── Header ───────────────────────────────────────────────── -->
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_58%,#f4fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Multi-user access</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Staff & Roles</h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">
                                Add team members, assign roles, and control what each person can access. Login credentials are emailed to them automatically.
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <a
                                :href="route('merchant.staff.activity')"
                                class="inline-flex items-center justify-center gap-2 rounded-full border border-[#d6e7e7] bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Activity log
                            </a>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-full bg-[var(--brand-orange)] px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-800 shadow-[0_20px_45px_-24px_rgba(197,92,24,0.55)] transition duration-200 hover:-translate-y-0.5"
                                @click="openInvite"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 4v16m8-8H4" />
                                </svg>
                                Add member
                            </button>
                        </div>
                    </div>
                </section>

                <!-- ── Role legend ────────────────────────────────────────────── -->
                <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="opt in roleOptions"
                        :key="opt.value"
                        class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.3)]"
                    >
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] ring-1"
                            :class="roleBadgeClass[opt.value]"
                        >{{ opt.label }}</span>
                        <ul class="mt-3 space-y-1.5 text-xs text-slate-500">
                            <li class="flex items-center gap-2">
                                <span
                                    class="inline-block h-2 w-2 rounded-full"
                                    :class="defaultPermissions[opt.value]?.menu_edit ? 'bg-emerald-400' : 'bg-slate-200'"
                                />
                                Menu edit
                            </li>
                            <li class="flex items-center gap-2">
                                <span
                                    class="inline-block h-2 w-2 rounded-full"
                                    :class="defaultPermissions[opt.value]?.order_access ? 'bg-emerald-400' : 'bg-slate-200'"
                                />
                                Order access
                            </li>
                            <li class="flex items-center gap-2">
                                <span
                                    class="inline-block h-2 w-2 rounded-full"
                                    :class="defaultPermissions[opt.value]?.finance_access ? 'bg-emerald-400' : 'bg-slate-200'"
                                />
                                Finance access
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- ── Per-restaurant groups ──────────────────────────────────── -->
                <div v-for="group in restaurantGroups" :key="group.id" class="space-y-4">

                    <!-- Active staff -->
                    <section class="overflow-hidden rounded-[32px] border border-white/80 bg-white/90 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.5)]">
                        <div class="flex flex-col gap-4 border-b border-[#e7efef] px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">{{ group.name }}</p>
                                <h3 class="mt-1 text-xl font-semibold text-slate-900">Team Members</h3>
                            </div>
                            <span class="rounded-full border border-white/80 bg-white px-4 py-1.5 text-sm font-medium text-slate-600 shadow-[0_10px_24px_-16px_rgba(11,77,89,0.3)]">
                                {{ group.activeStaff.length }} member{{ group.activeStaff.length !== 1 ? 's' : '' }}
                            </span>
                        </div>

                        <div v-if="group.activeStaff.length" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[#e8efef]">
                                <thead class="bg-[#f8fbfb]">
                                    <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-6 py-4 sm:px-8">Member</th>
                                        <th class="px-6 py-4">Role</th>
                                        <th class="px-6 py-4">Permissions</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4">Joined</th>
                                        <th class="px-6 py-4 sm:px-8">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#eef3f3] bg-white">
                                    <tr
                                        v-for="staff in group.activeStaff"
                                        :key="staff.id"
                                        class="align-middle transition duration-200 hover:bg-[#fcfefe]"
                                    >
                                        <td class="px-6 py-4 sm:px-8">
                                            <div class="flex min-w-[200px] items-center gap-3">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[var(--brand-teal)] to-[var(--brand-orange)] text-sm font-semibold text-white shadow-[0_8px_20px_-12px_rgba(11,77,89,0.6)]">
                                                    {{ staff.displayName.charAt(0).toUpperCase() }}
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">{{ staff.displayName }}</p>
                                                    <p class="text-xs text-slate-500">{{ staff.userEmail }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] ring-1"
                                                :class="roleBadgeClass[staff.role]"
                                            >{{ staff.roleLabel }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex min-w-[140px] flex-wrap gap-1.5">
                                                <span
                                                    v-if="staff.permissions?.menu_edit"
                                                    class="rounded-full bg-[#f0f8f8] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-[var(--brand-teal)] ring-1 ring-[#d4e8e9]"
                                                >Menu</span>
                                                <span
                                                    v-if="staff.permissions?.order_access"
                                                    class="rounded-full bg-orange-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-orange-700 ring-1 ring-orange-200"
                                                >Orders</span>
                                                <span
                                                    v-if="staff.permissions?.finance_access"
                                                    class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-700 ring-1 ring-emerald-200"
                                                >Finance</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] ring-1"
                                                :class="statusBadgeClass[staff.status]"
                                            >{{ staff.status }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="min-w-[120px] text-xs text-slate-500">{{ staff.acceptedAt ?? '—' }}</p>
                                        </td>
                                        <td class="px-6 py-4 sm:px-8">
                                            <div class="flex min-w-[120px] items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-full border border-[#d0e2e3] bg-[#f7fbfb] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5 hover:border-[var(--brand-orange)] hover:bg-[#fffaf4]"
                                                    @click="openEdit(staff)"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-full border border-red-100 bg-red-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-red-600 transition duration-200 hover:-translate-y-0.5"
                                                    @click="removeStaff(staff)"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="px-6 py-10 sm:px-8">
                            <p class="text-sm text-slate-500">No active team members yet. Use the <span class="font-semibold text-slate-700">Invite member</span> button to get started.</p>
                        </div>
                    </section>

                </div>

            </div>
        </div>

        <!-- ── Invite slide-over ─────────────────────────────────────────────── -->
        <SlideOver :show="inviteOpen" max-width="xl" @close="inviteOpen = false">
            <div class="flex h-full flex-col">
                <!-- Header -->
                <div class="border-b border-[#e7efef] bg-[linear-gradient(135deg,#f4fbfb_0%,#fff8f1_100%)] px-6 py-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Team access</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Invite a team member</h3>
                        </div>
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-[#dce8e8] bg-white text-slate-400 transition hover:text-slate-700"
                            @click="inviteOpen = false"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6L6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <form id="invite-form" class="space-y-5" @submit.prevent="submitInvite">
                        <SelectField
                            v-if="restaurantGroups.length > 1"
                            id="invite_restaurant"
                            v-model="inviteForm.restaurant_id"
                            label="Restaurant"
                            :options="restaurantOptions"
                            :message="inviteForm.errors.restaurant_id"
                        />

                        <TextField
                            id="invite_email"
                            v-model="inviteForm.invited_email"
                            label="Email address"
                            type="email"
                            placeholder="staff@example.com"
                            :message="inviteForm.errors.invited_email"
                        />

                        <TextField
                            id="invite_name"
                            v-model="inviteForm.invited_name"
                            label="Display name (optional)"
                            placeholder="e.g. Maria Santos"
                            :message="inviteForm.errors.invited_name"
                        />

                        <SelectField
                            id="invite_role"
                            v-model="inviteForm.role"
                            label="Role"
                            :options="roleOptions"
                            :message="inviteForm.errors.role"
                        />

                        <!-- Permissions preview -->
                        <div class="rounded-[20px] border border-[#e0ecec] bg-[#f8fbfb] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Default permissions for {{ inviteForm.role }}</p>
                            <ul class="mt-3 space-y-2">
                                <li
                                    v-for="(enabled, key) in (defaultPermissions[inviteForm.role] ?? {})"
                                    :key="key"
                                    class="flex items-center gap-2 text-sm text-slate-600"
                                >
                                    <span
                                        class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                                        :class="enabled ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400'"
                                    >
                                        <svg v-if="enabled" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        <svg v-else class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </span>
                                    {{ { menu_edit: 'Menu edit', order_access: 'Order access', finance_access: 'Finance access' }[key] ?? key }}
                                </li>
                            </ul>
                            <p class="mt-3 text-xs text-slate-400">You can fine-tune these at any time after adding the member.</p>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="border-t border-[#e7efef] px-6 py-4">
                    <div class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-[#d6e2e2] bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5"
                            @click="inviteOpen = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            form="invite-form"
                            class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_40px_-20px_rgba(11,77,89,0.6)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                            :disabled="inviteForm.processing"
                        >
                            {{ inviteForm.processing ? 'Adding…' : 'Add member' }}
                        </button>
                    </div>
                </div>
            </div>
        </SlideOver>

        <!-- ── Edit slide-over ───────────────────────────────────────────────── -->
        <SlideOver :show="!!editTarget" max-width="xl" @close="editTarget = null">
            <div v-if="editTarget" class="flex h-full flex-col">
                <!-- Header -->
                <div class="border-b border-[#e7efef] bg-[linear-gradient(135deg,#f4fbfb_0%,#fff8f1_100%)] px-6 py-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Edit team member</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ editTarget.displayName }}</h3>
                            <p class="text-sm text-slate-500">{{ editTarget.userEmail }}</p>
                        </div>
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-[#dce8e8] bg-white text-slate-400 transition hover:text-slate-700"
                            @click="editTarget = null"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6L6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <form id="edit-form" class="space-y-6" @submit.prevent="submitEdit">
                        <SelectField
                            id="edit_role"
                            v-model="editForm.role"
                            label="Role"
                            :options="roleOptions"
                            :message="editForm.errors.role"
                        />

                        <!-- Permissions -->
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Permissions</p>
                            <p class="mt-1 text-xs text-slate-500">Changing the role above resets permissions to defaults. Adjust individually here.</p>
                            <div class="mt-4 space-y-3">
                                <label
                                    v-for="(label, key) in { menu_edit: 'Menu edit', order_access: 'Order access', finance_access: 'Finance access' }"
                                    :key="key"
                                    class="flex cursor-pointer items-center justify-between rounded-[18px] border border-[#e0ecec] bg-[#f8fbfb] px-4 py-3 transition hover:bg-[#f2f8f8]"
                                >
                                    <span class="text-sm font-medium text-slate-700">{{ label }}</span>
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="editForm.permissions[key]"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-teal)]"
                                        :class="editForm.permissions[key] ? 'bg-[var(--brand-teal)]' : 'bg-slate-200'"
                                        @click="editForm.permissions[key] = !editForm.permissions[key]"
                                    >
                                        <span
                                            class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transition-transform duration-200"
                                            :class="editForm.permissions[key] ? 'translate-x-5' : 'translate-x-0'"
                                        />
                                    </button>
                                </label>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Account status</p>
                            <div class="mt-3 flex gap-3">
                                <label
                                    v-for="opt in [{ value: 'active', label: 'Active' }, { value: 'suspended', label: 'Suspended' }]"
                                    :key="opt.value"
                                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-[18px] border px-4 py-3 transition"
                                    :class="editForm.status === opt.value
                                        ? 'border-[var(--brand-teal)] bg-[#f0f8f8]'
                                        : 'border-[#e0ecec] bg-white hover:bg-[#f8fbfb]'"
                                >
                                    <input
                                        type="radio"
                                        :value="opt.value"
                                        v-model="editForm.status"
                                        class="accent-[var(--brand-teal)]"
                                    >
                                    <span class="text-sm font-medium text-slate-700">{{ opt.label }}</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="border-t border-[#e7efef] px-6 py-4">
                    <div class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-[#d6e2e2] bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5"
                            @click="editTarget = null"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            form="edit-form"
                            class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_40px_-20px_rgba(11,77,89,0.6)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                            :disabled="editForm.processing"
                        >
                            {{ editForm.processing ? 'Saving…' : 'Save changes' }}
                        </button>
                    </div>
                </div>
            </div>
        </SlideOver>
    </MerchantLayout>
</template>
