import { merchantFetch } from 'merchant/utils/ajax';
import { ActivateAccountResponseType } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';

import { transformError } from './utils';

export const activateAccount = async (
  va_currency: string,
  accept_b2b_tnc = 0,
): Promise<ActivateAccountResponseType> => {
  try {
    const response: ActivateAccountResponseType = await merchantFetch({
      url: 'international/virtual_accounts',
      method: 'post',
      data: { accept_b2b_tnc, va_currency },
    });
    return response;
  } catch (error) {
    throw transformError(error);
  }
};

export const fetchPublicPaymentLink = async () => {
  try {
    const response = await merchantFetch('payments_cross_border_live/v1/export-link');
    return response;
  } catch (error) {
    throw transformError(error);
  }
};

export const createPublicPaymentLink = async () => {
  try {
    const response = await merchantFetch({
      url: 'payments_cross_border_live/v1/export-link',
      method: 'POST',
    });
    return response;
  } catch (error) {
    throw transformError(error);
  }
};

export const updatePublicPaymentLink = async (exportId = '') => {
  try {
    const response = await merchantFetch({
      url: 'payments_cross_border_live/v1/export-link',
      method: 'PATCH',
      data: { export_id: exportId },
    });
    return response;
  } catch (error) {
    throw transformError(error);
  }
};
