import {
  activateAccountError,
  activateAccountPending,
  activateAccountSuccess,
} from 'merchant/reducers/b2bExports/actions';
import { merchantFetch } from 'merchant/utils/ajax';
import { ActivateAccountResponseType } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';
import { Dispatch } from 'redux';

export const activateAccount =
  (va_currency: string, accept_b2b_tnc = 0, type = 'localBankTranfer') =>
  async (dispatch: Dispatch): Promise<ActivateAccountResponseType> => {
    try {
      dispatch(activateAccountPending({ type, va_currency }));
      const response: ActivateAccountResponseType = await merchantFetch({
        url: 'international/virtual_accounts',
        method: 'post',
        data: { accept_b2b_tnc, va_currency },
      });
      dispatch(activateAccountSuccess({ type, response: response?.data ?? [] }));
      return response;
    } catch (error) {
      dispatch(activateAccountError({ type, error }));
      throw error;
    }
  };
