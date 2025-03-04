type TimeField = number | null;
type Mode = 'live' | 'test';

export interface ICategory {
  alias: string;
  catalog_count?: number;
  id: string;
  name: string;
}
interface ImageType {
  description: string;
  id: string;
  large: string;
  medium: string;
  original: string;
  small: string;
  title: string;
}

export type ICategories = Array<ICategory>;
type ImagesType = Array<ImageType>;

export interface ICatalog {
  id: string;
  created_at: number;
  updated_at: TimeField;
  deleted_at: TimeField;
  mode: Mode;
  merchant_id: string;
  meta_data: null;
  product_name: string;
  description: string;
  status: 'in_stock';
  currency: 'INR';
  amount: number;
  discounted_amount?: number;
  units: number;
  sku_id: string;
  images: ImagesType;
  categories: ICategories;
}

export interface ILineItem {
  id: string;
  created_at: number;
  updated_at: TimeField;
  deleted_at: null;
  merchant_id: string;
  title: string;
  description: string;
  status: 'active';
  catalog_id: string;
  nocode_id: string;
  entity_type: 'price';
  entity_id: string;
  position: number;
  mandatory: true;
  // TODO: Wait for backend to fix this scenario.
  // catalog: ICatalog | null;
  catalog: ICatalog;
}

export interface StoreFrontMetaData {
  user_email: string;
  user_id: string;
}

export interface CropDimensions {
  x: number;
  y: number;
  width: number;
  height: number;
}

export interface IBannerImage {
  id?: string;
  original: string;
  cropped: string;
  position: number;
  enabled: boolean;
  title?: string;
  description?: string;
  isSwitchEnabled?: boolean;
  selected_area?: CropDimensions;
}

export interface IStorefrontResponse {
  id: string;
  merchant_id: string;
  title: string;
  description: string;
  currency: 'INR';
  notes: [];
  expire_by: TimeField;
  expired_at: TimeField;
  status: 'active' | 'inactive';
  type: 'store';
  slug: string;
  banner_images: Array<IBannerImage>;
  social_handles: SocialMediaHandles;
  meta_data: StoreFrontMetaData;
  short_url: string;
  mode: Mode;
  created_at: number;
  updated_at: TimeField;
  deleted_at: TimeField;
  line_items: Array<ILineItem> | null;
  support_contact: string;
  support_email: string;
  terms?: string;
  configs;
}

export type SocialMediaHandle = {
  id?: string;
  platform: string;
  profile_url: string;
  logo_url?: string;
  position?: number;
};

export type SocialMediaHandles = SocialMediaHandle[];
