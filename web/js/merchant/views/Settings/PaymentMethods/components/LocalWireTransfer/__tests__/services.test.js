import * as Ajax from 'merchant/utils/ajax';
import { activateAccount } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services';

describe('Test activateAccount', () => {
  const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');

  test('should make a post call to activate account', async () => {
    merchantFetchSpyOn.mockReturnValue(Promise.resolve({ success: true }));

    const response = await activateAccount();

    expect(response).toEqual({
      success: true,
    });

    expect(merchantFetchSpyOn).toHaveBeenCalledWith({
      data: {
        accept_b2b_tnc: 0,
        va_currency: undefined,
      },
      method: 'post',
      url: 'international/virtual_accounts',
    });
  });

  test('should format the error response from api', async () => {
    merchantFetchSpyOn.mockReturnValue(
      Promise.reject({ errors: ['Bad request', 'http 400 status'] }),
    );

    try {
      await activateAccount();
    } catch (errors) {
      expect(errors).toEqual('Bad request');
    }
  });

  test('should throw default error message', async () => {
    merchantFetchSpyOn.mockReturnValue(Promise.reject({}));

    try {
      await activateAccount();
    } catch (errors) {
      expect(errors).toEqual('Something went wrong. Please try again later.');
    }
  });

  test('should handle Error instance and convert it into error message', async () => {
    merchantFetchSpyOn.mockReturnValue(
      Promise.reject(new Error('Throw Error. Something went wrong.')),
    );

    try {
      await activateAccount();
    } catch (errors) {
      expect(errors).toEqual('Throw Error. Something went wrong.');
    }
  });
});
