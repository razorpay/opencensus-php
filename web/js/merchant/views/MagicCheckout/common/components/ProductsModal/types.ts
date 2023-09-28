export interface ItemsCategory {
  id?: string;
  name: string;
  merchant_id?: string;
  item_count?: number;
  items?: Product[];
  type?: string;
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
