import { ModeT } from 'common/services/mode';

export enum ProgramPriceType {
  RANGE = 'range',
  FIXED = 'fixed',
}

export interface ListApiParams {
  skip?: number;
  count?: number;
  mode: ModeT;
  account_id?: string;
}

export interface ListApiResponse<T> {
  entity: string;
  count: number;
  has_more: boolean;
  items: T[];
}

export type ProgramApiParams = { programId?: string } & ListApiParams;

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
  max_discount_percent: string;
  min_discount_percent: string;
  ppi_type: string;
  program_category: string;
  program_desc: string;
  program_distribution: string;
  program_type: string;
};

export type Program = {
  id: string;
  name: string;
  merchant: string;
  type: string;
  policies: ProgramPolicy;
};
