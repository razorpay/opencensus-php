import { merchantFetch } from 'merchant/utils/ajax';
import {
  CustomerLoginListResponse,
  SSO_CUSTOMER_FILTER,
  SSOCustomerResponse,
  SSOGraphDataResponse,
} from 'merchant/views/MagicCheckout/SSODashboard/types';

export const fetchCustomerLoginList = ({
  from,
  to,
  skip = 0,
  count = 10,
  search = '',
  field = '',
  type = SSO_CUSTOMER_FILTER.ALL,
}): Promise<CustomerLoginListResponse> => {
  return merchantFetch<CustomerLoginListResponse>({
    method: 'get',
    url: `magic/sso_dashboard/customers/latest_login`,
    params: {
      start_time: from,
      end_time: to,
      count,
      skip,
      search_term: search,
      search_field: field,
      type,
    },
  });
};

export const fetchCustomerDetails = ({ customer_id, from, to }): Promise<SSOCustomerResponse> => {
  return merchantFetch<SSOCustomerResponse>({
    url: `magic/sso_dashboard/customers/logins`,
    method: 'get',
    params: {
      start_time: from,
      end_time: to,
      customer_id,
    },
  });
};

export const fetchSSOLoginGraphData = ({ from, to }): Promise<SSOGraphDataResponse> => {
  return merchantFetch<SSOGraphDataResponse>({
    url: `magic/sso_dashboard/login_metrics`,
    method: 'get',
    params: {
      start_time: from,
      end_time: to,
    },
  });
};

export const exportSSOMerchantData = ({ from, to }) => {
  return merchantFetch<{
    data: {
      file_url: string;
    };
  }>({
    url: `magic/sso_dashboard/customers/latest_login/export`,
    method: 'get',
    params: {
      start_time: from,
      end_time: to,
    },
  });
};
