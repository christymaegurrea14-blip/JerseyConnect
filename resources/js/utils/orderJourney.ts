import type { DesignRequestStatus, OrderStatus } from "@/types/jersey";

// The full real lifecycle of a kit, from the moment a design is submitted
// through to delivery — a single continuous stage list so the home page
// tracker never has a gap between "design request" and "order" phases.
// Order::STATUS_FLOW covers the last 6 of these; the first 3 map from a
// design request's real status (see designRequestStatus.ts for the
// design-request-only badge labels used elsewhere, e.g. the Design page).
export type JourneyStageKey =
    | "design_submitted"
    | "in_discussion"
    | "down_payment"
    | "processing"
    | "in_production"
    | "ready_for_delivery"
    | "shipped"
    | "delivered"
    | "completed";

export const JOURNEY_STAGES: { key: JourneyStageKey; label: string }[] = [
    { key: "design_submitted", label: "Design Submitted" },
    { key: "in_discussion", label: "In Discussion" },
    { key: "down_payment", label: "Down Payment" },
    { key: "processing", label: "Processing" },
    { key: "in_production", label: "In Production" },
    { key: "ready_for_delivery", label: "Ready for Delivery" },
    { key: "shipped", label: "Shipped" },
    { key: "delivered", label: "Delivered" },
    { key: "completed", label: "Completed" },
];

const DESIGN_STATUS_TO_STAGE: Partial<Record<DesignRequestStatus, JourneyStageKey>> = {
    pending_review: "design_submitted",
    in_discussion: "in_discussion",
    revision_requested: "in_discussion",
    waiting_for_down_payment: "down_payment",
    pending_down_payment_review: "down_payment",
};

const ORDER_STATUS_TO_STAGE: Record<OrderStatus, JourneyStageKey> = {
    processing: "processing",
    in_production: "in_production",
    ready_for_delivery: "ready_for_delivery",
    shipped: "shipped",
    delivered: "delivered",
    completed: "completed",
};

/** Index into JOURNEY_STAGES for a given design-request or order status, or -1 if unmapped (e.g. "cancelled"/"approved" design requests, which shouldn't reach the tracker at all). */
export function journeyStageIndex(kind: "design" | "order", status: string): number {
    const key =
        kind === "design"
            ? DESIGN_STATUS_TO_STAGE[status as DesignRequestStatus]
            : ORDER_STATUS_TO_STAGE[status as OrderStatus];
    if (!key) return -1;
    return JOURNEY_STAGES.findIndex((s) => s.key === key);
}

export function journeyStageLabel(kind: "design" | "order", status: string): string {
    const index = journeyStageIndex(kind, status);
    return index >= 0 ? JOURNEY_STAGES[index].label : status;
}
