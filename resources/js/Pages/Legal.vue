<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
</script>

<template>
    <Head title="Terms of Service &amp; Privacy Policy" />

    <div class="min-h-screen bg-slate-950 text-slate-200">
        <header class="border-b border-white/10 bg-[#0d1424]/95 backdrop-blur-md sticky top-0 z-10">
            <div class="max-w-3xl mx-auto px-4 sm:px-8 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0 overflow-hidden">
                        <img src="/images/printcode.png" alt="PrintCode" class="h-6 w-auto" />
                    </div>
                    <span class="text-lg font-extrabold text-white tracking-tight leading-none">PrintCode</span>
                </div>
                <Link href="/" class="text-xs text-slate-400 hover:text-slate-200 transition-colors">
                    <font-awesome-icon icon="fa-solid fa-angles-left" class="text-[10px]" />
                    Back to site
                </Link>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-8 py-10 flex flex-col gap-10">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Terms of Service &amp; Privacy Policy</h1>
                <p class="mt-2 text-sm text-slate-400">Effective date: registration on JerseyConnect. Last updated 2026.</p>
            </div>

            <section class="flex flex-col gap-4">
                <h2 class="text-lg font-bold text-white border-b border-white/10 pb-2">Terms of Service</h2>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">1. The Service</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        JerseyConnect is an online platform operated by PrintCode for browsing jersey templates, submitting
                        customized jersey design requests, managing team rosters, paying the required down payment through
                        GCash, and tracking an order from design approval through production and delivery.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">2. Accounts</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        You must provide accurate registration details (name, birth date, phone number, address, and email)
                        and keep your password confidential. You are responsible for activity that happens under your
                        account. PrintCode may deactivate an account that provides false information or is used to abuse
                        the platform.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">3. Design Requests and Orders</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        A design request becomes a confirmed order only after the design is approved and the required
                        down payment is verified. Team name, colors, font style, logo, and player roster details you
                        submit are used solely to produce your jersey design and are shared with PrintCode's design and
                        production staff for that purpose.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">4. Payments</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Down payments are made through GCash to the account details shown at checkout. You must submit a
                        valid reference number and payment screenshot for verification. Orders are not moved into
                        production until payment is verified by PrintCode staff.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">5. Cancellations and Changes</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Design details can be revised while a request is still under discussion. Once an order has moved
                        into production, changes or cancellations may no longer be possible, since materials and printing
                        have already been committed to your specifications.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">6. Acceptable Use</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        You agree not to upload logos, images, or team names that infringe on someone else's intellectual
                        property or that are unlawful, offensive, or misleading, and not to use the messaging feature to
                        harass staff or other users.
                    </p>
                </div>
            </section>

            <section class="flex flex-col gap-4">
                <h2 class="text-lg font-bold text-white border-b border-white/10 pb-2">Privacy Policy</h2>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">1. Information We Collect</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Account information (name, birth date, phone number, address, email, password), design request
                        details (team name, colors, font style, quantity, uploaded logo, player roster), payment
                        verification details (GCash reference number and screenshot), delivery address, and messages you
                        exchange with PrintCode staff through the platform.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">2. How We Use It</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        To create and manage your account, produce the jersey design you requested, verify payment, assign
                        a courier and track delivery, respond to your messages, and send you status notifications about
                        your design requests and orders.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">3. Who Can See Your Information</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Your design and order details are visible to PrintCode's admin, customer-service, graphic-design,
                        and printing/production staff strictly to fulfill your order. Your delivery address and contact
                        number are shared with the assigned courier only for the purpose of delivering your order. We do
                        not sell your personal information to third parties.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">4. Data Retention and Security</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Account and order records are kept for as long as your account is active and as needed to resolve
                        disputes or comply with legal obligations. Passwords are stored hashed, not in plain text, and
                        access to administrative tools is restricted to authenticated staff accounts.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-1">5. Your Rights</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Consistent with the Data Privacy Act of 2012 (Republic Act No. 10173), you may request access to,
                        correction of, or deletion of your personal data by contacting PrintCode through the in-app
                        messaging feature or the contact details on the landing page.
                    </p>
                </div>
            </section>

            <p class="text-xs text-slate-500 pt-4 border-t border-white/10">
                By creating an account on JerseyConnect, you acknowledge that you have read and agree to this Terms of
                Service and Privacy Policy.
            </p>
        </main>
    </div>
</template>
