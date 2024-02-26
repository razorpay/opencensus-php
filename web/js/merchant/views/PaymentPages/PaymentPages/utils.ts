import {
  ProductStatusKeys,
  PRODUCT_STATUS,
} from 'merchant/views/PaymentPages/common/Products/utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, isExperimentActive } from 'common/utils/rzp-utils';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';
import { fetchPaymentsListForPaymentPage } from './model';
import { fetchStorefrontPayments } from 'merchant/reducers/invoices/list';
import { fetchPayments as fetchPaymentPagesPayments } from 'merchant/reducers/collection';

interface InputType {
  units: string;
  quantitySold: string;
  status: ProductStatusKeys;
}

export const getUnitsDescription = ({ units, quantitySold, status }: InputType): string => {
  const _units = Number(units);
  const _quantitySold = Number(quantitySold);

  if (status === PRODUCT_STATUS.UNLIMITED) {
    return quantitySold;
  }

  return `${quantitySold} of ${_units + _quantitySold}`;
};

export const getProductBaseLink = (
  isStorefront: boolean,
  id: string,
  isBatchPaymentPages: boolean,
): string => {
  let endPoint = '';

  if (isStorefront) {
    endPoint = 'storefront/';
  } else if (isBatchPaymentPages) {
    endPoint = 'batchpaymentpages/';
  }

  return `/paymentpages/${endPoint}${id}`;
};

export const getPaymentPagesTabs = (
  user,
): Array<{ title: string; url: string; onTabClick: () => void; hidden?: boolean }> => {
  return [
    {
      title: 'Payment Pages',
      url: '/paymentpages',
      onTabClick: () => {},
    },
    {
      title: 'Products',
      url: '/paymentpages/products',
      hidden: !user.isPaymentPageStorefrontEnabled,
      onTabClick: () => {
        analyticsTrack({
          objectName: 'Products tab',
          actionName: 'clicked',
          screen: 'Payment pages screen',
          properties: {
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      },
    },
    {
      title: 'Batch Payment Pages',
      url: BATCH_PAYMENT_PAGES_BASE_URL,
      hidden: !user.isPaymentPageFileUploadEnabled,
      onTabClick: () => {},
    },
  ];
};

export const isFetchViaNCA = (splitz: any) => {
  const {
    abExperiments: { NcaPaymentFetch },
  } = splitz;

  return isExperimentActive(NcaPaymentFetch);
};

export const fetchCapturedPaymentPagePayments = (type: string, splitz: any, id: any) => {
  if (isFetchViaNCA(splitz)) {
    const params = { type, status: 'captured', count: 5 };
    return fetchStorefrontPayments(id, params).payload;
  } else {
    return fetchPaymentsListForPaymentPage(id);
  }
};

export const fetchPaymentPagePayments = (type: string, splitz: any, id: any, params: any) => {
  if (isFetchViaNCA(splitz)) {
    params = { type, ...params };
    return fetchStorefrontPayments(id, params);
  }

  return fetchPaymentPagesPayments({
    ...params,
    payment_link_id: id,
  });
};
