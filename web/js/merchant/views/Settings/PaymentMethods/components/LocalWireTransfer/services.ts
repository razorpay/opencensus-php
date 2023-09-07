import { merchantFetch } from 'merchant/utils/ajax';
import { ActivateAccountResponseType } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';

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
    let errorMessage = '';

    if (error && typeof error === 'object' && 'errors' in error && Array.isArray(error.errors)) {
      errorMessage = error.errors[0];
    } else if (error instanceof Error) {
      errorMessage = error.message;
    } else {
      errorMessage = 'Something went wrong. Please try again later.';
    }

    throw errorMessage;
  }
};
