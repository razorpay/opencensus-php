import {
  ProductStatusKeys,
  PRODUCT_STATUS,
} from 'merchant/views/PaymentPages/common/Products/utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';

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
