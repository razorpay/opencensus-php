import DocsLink, { getCustomURL } from 'merchant/components/DocsLink';
import store from 'merchant/store';
import cloneDeep from 'lodash/cloneDeep';
import { render, screen, updateUseI18ServiceSpy } from 'test-utils';

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

const renderApp = ({ title }, user) =>
  render(
    <DocsLink
      url="https://curlec.com/docs/payments/refunds/batc/"
      title={title}
      onClick={jest.fn()}
    />,
    updateStore(user),
  );

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

describe('test for DocLink component', () => {
  test('should hide component if documentation.documentation is enabled', () => {
    const title = 'DocLinks are enabled';
    updateUseI18ServiceSpy('documentation.documentation');

    renderApp(
      { title },
      {
        isOrgAllowedFunctionality: () => true,
      },
    );
    expect(screen.queryByText(title)).not.toBeInTheDocument();
  });

  test('should show component if we dont pass any tag', () => {
    const title = 'DocLinks are disabled';
    updateUseI18ServiceSpy();
    renderApp(
      { title },
      {
        isOrgAllowedFunctionality: () => true,
      },
    );
    expect(screen.queryByText(title)).toBeInTheDocument();
  });
});
