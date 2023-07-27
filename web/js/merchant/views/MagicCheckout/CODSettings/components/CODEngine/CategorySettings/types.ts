export interface ItemsCategory {
  id?: string;
  name: string;
  merchant_id?: string;
  item_count: number;
  fee_rules: Record<string, unknown>[];
  zones: Record<string, unknown>[];
}

export type ProductSearchParams = {
  search_text?: string;
  cursor?: string;
  count?: number;
};

export type PageInfo = {
  end_cursor?: string;
  has_next_page?: boolean;
};

export type Product = {
  id: string;
  image_url: string;
  internal_category: string | null;
  internal_id?: string | null;
  name: string;
};

export type APIPayload = {
  name: string;
  items: Product[];
  id?: string;
};
