import { merchantFetch } from 'merchant/utils/ajax';

export const postInternationalVirtualAccountToggle = (action: string) =>
  merchantFetch({
    url: 'international/virtual_account/toggle',
    method: 'post',
    mode: 'live',
    data: {
      action,
    },
  });

export const postInternationalVirtualAccountActivate = (currency?: string) =>
  merchantFetch({
    url: 'international/virtual_accounts',
    method: 'post',
    mode: 'live',
    data: {
      enable_all_currencies: !currency,
      accept_b2b_tnc: true,
      va_currency: currency,
    },
  });

export const getInternationalVirtualAccounts = (): Promise<{
  data?: {
    accounts?: {
      va_currency: string;
      routing_code: string;
      routing_type: string;
      account_number: string;
      beneficiary_name: string;
      bank_name: string;
      bank_address: string;
      status: string;
    }[];
    status?: string;
  };
}> =>
  merchantFetch({
    url: 'international/virtual_accounts',
    mode: 'live',
  });
