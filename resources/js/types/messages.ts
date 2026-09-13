// ── Message Module types ────────────────────────────────────────────────────
//
// A conversation is anchored to a Design Request, NOT to an Order. This is
// intentional: an Order is just what a Design Request becomes once it's
// approved (Order.design_request_id points back to it). By keying threads
// off design_request_id, the same conversation naturally carries over from
// "discussing the design" to "discussing production/delivery" — which is
// exactly the continuity the workflow calls for. The Order module's
// "Message" button links back to this same design_request_id instead of
// opening a separate order-scoped conversation.

export type MessageSender = "admin" | "client";

export type ConversationStage = "design" | "order";

export interface ThreadMessage {
    id: string;
    from: MessageSender;
    name: string;
    body: string;
    time: string;
    /** URL (or data URL, for the static-data demo) of an attached image. */
    attachment_url?: string | null;
    attachment_name?: string | null;
}

export interface MessageThread {
    id: number;

    // Anchor — always present
    design_request_id: number;
    design_request_ref: string; // e.g. "DR-2026-0002"

    // Populated once the design request is approved and becomes an order.
    // Stage flips to "order" the moment this is set.
    order_id: number | null;
    order_ref: string | null; // e.g. "ORD-2026-0004"

    stage: ConversationStage;

    // Mirrors whatever status the design request/order is currently in, so
    // the thread list and header can show it without a second lookup.
    status_key: string;
    status_label: string;
    status_class: string;

    team_name: string;
    template_name: string;
    template_image: string;
    client_name: string;

    read: boolean;
    updated_at: string; // ISO timestamp, used for sorting + "last active"

    messages: ThreadMessage[];

    // True once the order is completed — history stays visible, but the
    // thread becomes read-only.
    closed: boolean;

    // Real order-spec data for the context panel.
    unit_price: number;
    quantity: number | null;
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    font_style: string | null;
    players: ThreadPlayer[];
}

export interface ThreadPlayer {
    id: number;
    name: string;
    number: string | null;
    position: string | null;
    size: string | null;
}