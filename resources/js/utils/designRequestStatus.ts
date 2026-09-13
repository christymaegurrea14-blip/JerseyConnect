import type { DesignRequestStatus } from "@/types/jersey";

export const statusBadge: Record<DesignRequestStatus, { label: string; class: string }> = {
    pending_review: { label: "Pending Review", class: "bg-yellow-100 text-yellow-700" },
    in_discussion: { label: "In Discussion", class: "bg-blue-100 text-blue-700" },
    revision_requested: { label: "Revision Requested", class: "bg-orange-100 text-orange-700" },
    waiting_for_down_payment: { label: "Waiting for Down Payment", class: "bg-pink-100 text-pink-700" },
    pending_down_payment_review: { label: "Pending Down Payment Review", class: "bg-red-100 text-red-700" },
    approved: { label: "Approved", class: "bg-green-100 text-green-700" },
    cancelled: { label: "Cancelled", class: "bg-gray-200 text-gray-600" },
};

export const statusMeaning: Record<DesignRequestStatus, string> = {
    pending_review: "Our team is reviewing your request.",
    in_discussion: "We're discussing details with you via chat.",
    revision_requested: "We've asked for changes — check your messages.",
    waiting_for_down_payment: "Ready for the 50% down payment to begin production.",
    pending_down_payment_review: "We're verifying your GCash payment.",
    approved: "Approved — your order has been created.",
    cancelled: "This request was cancelled.",
};

export function formatDate(value: string) {
    return new Date(value).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
