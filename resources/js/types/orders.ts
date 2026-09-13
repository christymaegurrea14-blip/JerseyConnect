import type { DesignRequest, RosterPlayer } from "@/types/jersey";

/**
 * An Order is created the moment a DesignRequest becomes "approved".
 * The jersey design fields (template, colors, font, etc.) are copied
 * over at creation time so the order keeps a frozen snapshot of what
 * was actually approved, even if the customer edits the design again later.
 */
export type OrderStatus =
    | "processing" // order created, default status on creation
    | "in_production" // jerseys being printed/sewn
    | "ready_for_delivery" // production done, packed, waiting for courier drop-off
    | "shipped" // handed to courier, shipping fee + tracking info now known
    | "delivered" // courier marked it delivered
    | "completed"; // customer confirmed receipt / order closed

export interface Address {
    id?: number;
    recipient_name: string;
    contact_number: string;
    line1: string;
    barangay?: string | null;
    city: string;
    province: string;
    postal_code: string;
    is_default?: boolean;
    latitude?: number | null;
    longitude?: number | null;
}

/**
 * There is no courier API integration. The shipping fee and transaction
 * number are still entered manually by the admin from the courier's paper
 * receipt, but the courier itself — and its tracking site — is looked up
 * from the Courier module (see @/types/couriers.ts) via courier_id, so
 * nobody has to retype a courier name or tracking link that's already
 * stored there.
 */
export interface CourierReceipt {
    id?: number;
    courier_id: number;
    transaction_number: string;
    shipping_fee: number;
    date_shipped: string;
    remarks?: string | null;
}

export interface Order {
    id: number;
    order_number: string;
    design_request_id: number;

    // Frozen design snapshot (copied from the approved DesignRequest)
    template_name: string;
    template_image: string;
    team_name: string;
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    font_style: string;

    quantity: number;
    unit_price: number;

    /** The team roster submitted on the originating design request, if any. */
    players?: RosterPlayer[];

    address: Address;

    /**
     * Unknown (null) until the order is marked "shipped" — at that point it
     * is copied over from courier_receipt.shipping_fee.
     */
    shipping_fee: number | null;

    status: OrderStatus;
    courier_receipt: CourierReceipt | null;

    created_at: string;
    updated_at: string;
}

export function buildOrderFromDesignRequest(
    request: DesignRequest,
    overrides: Partial<Order> = {},
): Partial<Order> {
    return {
        design_request_id: request.id,
        template_name: request.template_name,
        template_image: request.template_image,
        team_name: request.team_name,
        primary_color: request.primary_color,
        secondary_color: request.secondary_color,
        accent_color: request.accent_color,
        font_style: request.font_style ?? "",
        quantity: request.estimated_quantity,
        unit_price: request.template_price,
        status: "processing",
        shipping_fee: null,
        courier_receipt: null,
        ...overrides,
    };
}