export type SSOCustomer = {
  customer_id: string;
  name: string;
  phone: string;
  email: string;
  first_login: string;
  last_login: string;
  login_frequency: number;
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  last_order?: string;
  abandoned_checkout?: string;
};

export type SSOCustomerListFitlers = {
  start_time?: string;
  end_time?: string;
  search_term?: string;
  search_field?: string;
  type?: string;
  customer_id?: string;
};

export enum SSO_CUSTOMER_FILTER {
  ALL = 'all_customer',
  NEW = 'new_customer',
}

export type CustomerLoginListResponse = {
  data: {
    customers_latest_login: SSOCustomer[];
    pagination: Pagination;
    filters: SSOCustomerListFitlers;
  };
};

export type SSOCustomerResponse = {
  data: {
    customer_all_logins: SSOLoginDetails[];
  };
};

export type SSOGraphDataResponse = {
  data: {
    total_logged_in_users: number;
    new_account_creations: number;
    aggregate: 'daily' | 'hourly';
    metrics: SSOGraphData;
    filters: SSOCustomerListFitlers;
  };
};

export type SSOLoginDetails = {
  login_time: string;
  utm_source: string;
};

export type LoginGraphData = {
  totalLoggedIn: number;
  newLogin: number;
  graphData: null | SSOGraphData;
  aggregate: 'daily' | 'hourly';
};

export type SSOGraphData = {
  timestamps: number[];
  values: {
    label: string;
    values: number[];
  }[];
};

export type TableCellProps = {
  value: string;
  customer?: SSOCustomer;
  timeRange: {
    start: moment.Moment;
    end: moment.Moment;
  };
};

export type TimeRange = {
  start: moment.Moment;
  end: moment.Moment;
};

export type Pagination = {
  skip: number;
  count: number;
  total: number;
};
