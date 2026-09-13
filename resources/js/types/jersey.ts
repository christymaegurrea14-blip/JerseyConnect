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

export interface ActiveOrder {
    id: number;
    order_number: string;
    template_name: string;
    team_name: string;
    quantity: number;
    status: OrderStatus;
    created_at: string;
    courier_receipt: CourierReceipt | null;
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