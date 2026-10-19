export type Paginated<T> = {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
};

export type Category = {
    id: number;
    name: string;
    slug: string;
    children?: Category[];
};

export type Seller = {
    id: number;
    name: string;
    avatar: string | null;
    shop_name: string | null;
    is_verified: boolean;
    rating: number | null;
    reviews_count: number;
};

export type Listing = {
    id: number;
    slug: string;
    title: string;
    description: string;
    price: number;
    is_negotiable: boolean;
    condition: string;
    status: string;
    quantity: number;
    views_count: number;
    likes_count: number;
    comments_count: number;
    published_at: string | null;
    boosted?: boolean;
    category?: { id: number; name: string };
    location?: { id: number; name: string } | null;
    images?: { id: number; url: string; on_feed?: boolean }[];
    feed_images?: { id: number; url: string }[];
    thumbnail?: string | null;
    seller?: Seller;
    viewer: { saved: boolean; reaction: string | null };
};

export type Comment = {
    id: number;
    parent_id: number | null;
    content: string;
    created_at: string;
    likes_count: number;
    user: { id: number; name: string; avatar: string | null };
    is_seller: boolean;
    seller_badge: string | null;
    viewer_liked: boolean;
    can_edit: boolean;
    can_delete: boolean;
    replies: Comment[];
};
