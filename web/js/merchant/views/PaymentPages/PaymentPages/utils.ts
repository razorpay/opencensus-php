import {
  ProductStatusKeys,
  PRODUCT_STATUS,
} from 'merchant/views/PaymentPages/common/Products/utils';

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

export const getProductBaseLink = (isStorefront, id) => {
  return `/paymentpages/${isStorefront ? 'storefront/' : ''}${id}`;
};

export const checkBatchPaymentPages = () => {
  return window.location.pathname.includes('/batchpaymentpages');
};
