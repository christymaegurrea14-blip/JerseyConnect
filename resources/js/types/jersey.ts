export interface JerseyTemplate {
    id: number;
    name: string;
    description?: string | null;
    sport: "Basketball" | "Soccer" | "Baseball" | "Volleyball" | "Esports";
    price: number;
    badge?: "New" | "Bestseller" | "Hot";
    primaryColor: string;
    secondaryColor: string;
    accentColor: string;
    imagePath: string;
}

export type DesignRequestStatus =
    | "pending_review"
    | "in_discussion"
    | "revision_requested"
    | "waiting_for_down_payment"
    | "pending_down_payment_review"
    | "approved"
    | "cancelled";

export type OrderStatus =
    | "processing"
    | "in_production"
    | "ready_for_delivery"
    | "shipped"
    | "delivered"
    | "completed";

export interface CourierReceipt {
    id: number;
    transaction_number: string;
    shipping_fee: number;
    date_shipped: string;
    remarks: string | null;
    courier: { id: number; name: string; site: string | null } | null;
}

// The client's single "what's happening with my kit right now" item — spans
// the real lifecycle from a just-submitted design request all the way
// through order delivery, so the home page tracker stays continuous instead
// of only appearing once a design request has become an order.
export interface ActiveJourney {
    kind: "design" | "order";
    id: number;
    reference: string; // order_number, or a "DR-2026-0001"-style design ref
    design_request_id: number;
    template_name: string;
    template_image: string | null;
    team_name: string;
    quantity: number | null;
    status: DesignRequestStatus | OrderStatus;
    created_at: string;
    courier_receipt: CourierReceipt | null;
    /** Only meaningful when kind === "order" — whether a delivery address has been filled in yet. */
    address_complete: boolean;
}

export type PlayerSize = "XS" | "S" | "M" | "L" | "XL" | "XXL" | "3XL";

export interface RosterPlayer {
    id: number;
    design_request_id: number;
    name: string;
    number: string | null;
    position: string | null;
    size: PlayerSize | null;
    created_at: string;
    updated_at: string;
}

export interface DesignRequest {
    id: number;
    template_id: number;
    template_name: string;
    template_image: string;
    template_image_url?: string;
    original_template_image?: string;
    template_price: number;
    team_name: string;
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    font_style: string | null;
    estimated_quantity: number;
    notes: string | null;
    logo_path?: string | null;
    logo_url?: string | null;
    status: DesignRequestStatus;
    created_at: string;
    gcash_number?: string | null;
    reference_number?: string | null;
    proof_image_url?: string | null;
    players?: RosterPlayer[];
}