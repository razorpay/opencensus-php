import { RouteComponentProps } from 'react-router-dom';

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

interface NavLinkData {
  section_name: string;
  section_id: string;
  product_options: Products[];
}

export interface NavItems {
  loading: boolean;
  error: any;
  data: NavLinkData[];
}

export interface SidebarPropsInterface extends RouteComponentProps {
  logoURL?: string;
  config: any;
  user: User;
  fetchLeftNavItems: () => void;
  leftNavItems: NavItems;
  showAcceptPayments: boolean;
  org: any;
  hideAcceptPaymentsModal: () => void;
  isMobile: boolean;
}

export interface NavLinkItemInterface extends RouteComponentProps {
  title: string;
  icon: string;
  product_id: string;
  tags: string[];
  activeTab?: string;
  routes: Record<string, string>;
  additionalCondition: (payload: any) => boolean;
  getHref?: (payload: any) => boolean;
  user: User;
  section?: string;
}

export interface NavLinkProductPropsInterface {
  heading: string;
  products: Products[];
  routes: Record<string, string>;
  activeTab: string;
  loading: boolean;
  user: User;
  section_id: string;
}

export interface ProductsStateInterface {
  valid: Products[];
  reserved?: Products[];
}

export interface FilterProductInterface extends ProductsStateInterface {
  promoted: number[];
}
