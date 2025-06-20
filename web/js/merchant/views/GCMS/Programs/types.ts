import { ListApiParams } from 'merchant/views/GCMS/shared/types';

export enum ProgramPriceType {
  RANGE = 'range',
  FIXED = 'fixed',
}

export type ProgramApiParams = { programId?: string } & ListApiParams;

type voidFn = (x: never) => void;

export type ProgramPolicy = {
  gift_card_brand_name: string;
  gift_card_pin_enabled: boolean;
  gift_card_ppi_type: string;
  gift_card_maximum_price: number;
  gift_card_minimum_price: number;
  gift_card_price_denominations: number[];
  gift_card_price_type: ProgramPriceType;
  gift_card_reissue_enabled: boolean;
  gift_card_tnc_link: string;
  gift_card_validity_in_days: number;
  image_link: string;
  max_discount_percent: number;
  min_discount_percent: number;
  ppi_type: string;
  program_category: string;
  program_desc: string;
  program_distribution: string;
  program_type: string;
  gift_card_number_length: number;
  gift_card_number_prefix?: string;
  gift_card_number_alphanumeric_enabled: boolean;
};

export type Program = {
  id: string;
  name: string;
  merchant: string;
  type: string;
  policies: ProgramPolicy;
  program_id: string;
  default_discount: number;
};

export interface SKU extends Program {
  program_id: string;
}

export type LinkedResellerProps = {
  program: Program;
  mode: string;
  merchantId: string;
  isOpen: voidFn;
  closeModal: voidFn;
  selectedTab: string;
};
