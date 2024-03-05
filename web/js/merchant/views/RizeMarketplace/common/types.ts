// ---- THESE TYPES ARE GENERATED FROM A PROTO FILE ---- //
export interface FetchProductBySlugRequest {
  user_id: string;
  slug: string;
}

export interface FetchProductsRequest {
  user_id: string;
  search: string;
  filters: Filters | undefined;
  limit: number;
  offset: number;
}

export interface Filters {
  category: string[];
}

export interface FetchProductsResponse {
  total_count: number;
  results: Result[];
}

export enum TYPE {
  rize = 'rize',
  UNRECOGNIZED = 'UNRECOGNIZED',
}

export interface Result {
  id: string;
  name: string;
  slug: string;
  excerpt: string;
  logo_src: string;
  type: TYPE;
  category: string;
  offer: string;
}

export interface ProductResponse {
  id: string;
  hidden: boolean;
  slug: string;
  data: Data | undefined;
}

export interface Data {
  name: string;
  excerpt: string;
  logo_src: string;
  video_src: string;
  type: TYPE;
  category: string;
  company: Company | undefined;
  deal: Deal | undefined;
  details: Details | undefined;
}

export interface Company {
  company_id: string;
  name: string;
  website_url: string;
  industry: string;
  co_founders: CoFounder[];
}

export interface CoFounder {
  user_id: string;
  name: string;
  profile_picture: string;
  username: string;
}

export interface Deal {
  offer: string;
  avail_link: string;
  coupon_code: string;
}

export interface Details {
  about: string;
  features: string[];
  eligibility: string;
  how_to_avail: string;
}
// ---- GENERATED TYPES END HERE ---- //

type TrackActionType =
  | 'Clicked'
  | 'Viewed'
  | 'Initiated'
  | 'Success'
  | 'Filled'
  | 'Checked'
  | 'Failed';

export type EventActionsType = Record<
  string,
  {
    objectName: string;
    actionName: TrackActionType;
    screen: string;
  }
>;

export interface APIError {
  downstream_status_code?: number;
  [key: string]: unknown;
}
