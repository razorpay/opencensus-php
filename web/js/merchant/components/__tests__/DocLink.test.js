import { getCustomURL } from 'merchant/components/DocsLink';
import store from 'merchant/store';
import cloneDeep from 'lodash/cloneDeep';

const storeData = store.getState();

const updateStore = (user) => {
  jest.spyOn(store, 'getState').mockImplementation(() => {
    const clonedStore = cloneDeep(storeData);
    clonedStore.session.user = {
      ...clonedStore.session.user,
      ...user,
    };
    return clonedStore;
  });
};

describe('test for getCustomURL function', () => {
  describe('test scenarios for india', () => {
    test('when merchant is from rzp org', () => {
      updateStore({
        merchant: {
          currency: 'INR',
          country_code: 'IN',
        },
        isOrgRZP: true,
        isOrgCurlec: false,
        orgCustomCode: 'rzp',
      });
      const testURL = getCustomURL('https://razorpay.com/docs/payments/refunds/batch/');
      expect(testURL).toBe('https://razorpay.com/docs/payments/refunds/batch/');
    });

    test('when merchant is from axis org', () => {
      updateStore({
        merchant: {
          currency: 'INR',
          country_code: 'IN',
        },
        isOrgRZP: false,
        isOrgCurlec: false,
        orgCustomCode: 'axis',
      });
      const testURL = getCustomURL('https://razorpay.com/docs/invoices/');
      expect(testURL).toBe('https://axisbank-docs.razorpay.com/invoices/');
    });

    test('when merchant is from non-axis org', () => {
      updateStore({
        merchant: {
          currency: 'INR',
          country_code: 'IN',
        },
        isOrgRZP: false,
        isOrgCurlec: false,
        orgCustomCode: 'hdfc',
      });
      const testURL = getCustomURL('https://razorpay.com/docs/invoices/');
      expect(testURL).toBe('https://razorpay.com/docs/invoices/');
    });

    test('when merchant is from curlec org and docURL is undefined', () => {
      updateStore({
        merchant: {
          currency: 'MYR',
          country_code: 'MY',
        },
        isOrgRZP: false,
        isOrgCurlec: true,
        orgCustomCode: 'curlec',
      });
      const testURL = getCustomURL();
      // should not break if the argument passed is undefined
      expect(testURL).toBe(undefined);
    });
  });

  describe('test scenarios for merchant', () => {
    test('when merchant is from curlec org', () => {
      updateStore({
        merchant: {
          currency: 'MYR',
          country_code: 'MY',
        },
        isOrgRZP: false,
        isOrgCurlec: true,
        orgCustomCode: 'curlec',
      });
      const testURL = getCustomURL('https://razorpay.com/docs/payments/refunds/batch/');
      expect(testURL).toBe('https://curlec.com/docs/payments/refunds/batch/');
    });
  });
});
