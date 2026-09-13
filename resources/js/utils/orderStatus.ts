import type { OrderStatus } from "@/types/orders";

// Single source of truth for order tracking — every client page that shows
// an order's production/delivery status (Home's active-order widget,
// Orders' list + pipeline stepper) must import from here instead of
// hardcoding its own copy, so the same order never shows a different
// label, color, or progress percentage depending which page you're on.

export const orderStatusBadge: Record<OrderStatus, { label: string; class: string }> = {
    processing: { label: "Processing", class: "bg-yellow-100 text-yellow-700" },
    in_production: { label: "In Production", class: "bg-blue-100 text-blue-700" },
    ready_for_delivery: { label: "Ready for Delivery", class: "bg-purple-100 text-purple-700" },
    shipped: { label: "Shipped", class: "bg-indigo-100 text-indigo-700" },
    delivered: { label: "Delivered", class: "bg-teal-100 text-teal-700" },
    completed: { label: "Completed", class: "bg-green-100 text-green-700" },
};

// The real 6-stage production/delivery flow (mirrors Order::STATUS_FLOW
// server-side). Keeping "completed" in the list even though the Home
// widget never shows a completed order matters: it's what makes the
// stage COUNT match Orders' pipeline stepper, so the same status lands
// on the same fraction-complete everywhere.
export const ORDER_PIPELINE_STAGES: { value: OrderStatus; label: string }[] = [
    { value: "processing", label: "Processing" },
    { value: "in_production", label: "In Production" },
    { value: "ready_for_delivery", label: "Ready for Delivery" },
    { value: "shipped", label: "Shipped" },
    { value: "delivered", label: "Delivered" },
    { value: "completed", label: "Completed" },
];

export function orderStageIndex(status: OrderStatus | null | undefined): number {
    if (!status) return -1;
    return ORDER_PIPELINE_STAGES.findIndex((s) => s.value === status);
}
