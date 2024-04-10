import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';

export type User = Record<string, unknown>;

export interface Routes {
  [code: string]: string;
}

export interface ActivationProgressInterface {
  user: any;
  config: any;
  onSidebarActivationClick: () => void;
}

export interface Products {
  title: string;
  product_id: string;
  category: string;
  tags: string[];
}

export interface NavLinkData {
  section_name: string;
  section_id: string;
  product_options: Products[];
}

export interface NavItems {
  loading: boolean;
  error: any;
  data: NavLinkData[];
}

interface Merchant {
  id: string;
}

type ExtendedUser = {
  kyc_clarification_reasons: {
    nc_count: any;
  };
  merchants: Record<string, Merchant>;
};

export interface SidebarPropsInterface extends RouteComponentProps {
  logoURL?: string;
  config: any;
  user: User & ExtendedUser;
  fetchLeftNavItems: () => void;
  leftNavItems: NavItems;
  showAcceptPayments: boolean;
  org: any;
  hideAcceptPaymentsModal: () => void;
  isMobile: boolean;
  isTagsLoading: boolean;
  isNcEligibile: boolean;
  trackEvents: (props: any) => void;
  shouldShowMobileMenu: boolean;
  toggleMobileMenu: () => void;
}

export interface NavLinkItemInterface extends RouteComponentProps {
  title: string;
  icon: string;
  product_id: string;
  tags: string[];
  activeTab?: string;
  routes: Record<string, string>;
  additionalCondition: (payload: User, experiments: any) => boolean;
  getHref?: (payload: any) => string;
  user: User;
  section?: string;
  type?: string;
  heading: string;
  image?: string;
  toggleMobileMenu: () => void;
}

export interface NavLinkProductPropsInterface extends RouteComponentProps {
  heading: string;
  products: Products[];
  routes: Record<string, string>;
  activeTab: string;
  loading: boolean;
  user: User;
  section_id: string;
  toggleMobileMenu: () => void;
}

export interface ProductsStateInterface {
  valid: Products[];
  reserved?: Products[];
}

export interface FilterProductInterface extends ProductsStateInterface {
  promoted: number[];
}
