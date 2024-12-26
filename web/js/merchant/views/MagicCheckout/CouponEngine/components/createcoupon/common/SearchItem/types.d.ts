export interface Variant {
  id: number;
  title: string;
  sku?: string;
}

export interface Product {
  id: number;
  image_url: string;
  name: string;
  variants: Variant[];
}

export interface SelectedProduct {
  product_id: number;
  product_name: string;
  product_image_url: string;
  variants: number[];
}

export interface SearchItemProps {
  product: Product;
  selectedProducts: { [key: string]: SelectedProduct };
  setSelectedProducts: (value: any) => void;
  shouldDisableProductCheckbox: boolean;
  shouldDisableVariantCheckbox: boolean;
  isFreebieCoupon: boolean;
}
