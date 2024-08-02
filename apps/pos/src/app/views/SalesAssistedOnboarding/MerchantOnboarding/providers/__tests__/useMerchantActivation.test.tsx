import React from 'react';
import useMerchantActivation from '../useMerchantActivation';
import {
  render,
  server,
  waitForElementToBeRemoved,
  screen,
  userEvent,
  waitFor,
} from 'apps/pos/src/services/test/test-utils';
import { getMerchantDetails } from 'apps/pos/src/services/mocks/handlers/merchantDetails';

const checkModularConfig = jest.fn();

const TestApp = ({ merchantId }) => {
  const { isMerchantDetailsLoading, merchantDetails } = useMerchantActivation({
    merchantId,
    onMerchantDetailsFetchError: () => checkModularConfig('error_callback'),
  });

  return (
    <React.Fragment>
      {isMerchantDetailsLoading ? <h1> Merchant Details Loading </h1> : null}
      <button onClick={() => checkModularConfig(merchantDetails)}>Check Merchant Details</button>
    </React.Fragment>
  );
};

const renderApp = ({ merchantId }) => {
  render(<TestApp merchantId={merchantId} />);
};

describe('useMerchantActivation', () => {
  test('should return merchant details', async () => {
    server.use(getMerchantDetails({ type: 'success' }));
    renderApp({ merchantId: 'testId' });
    await waitForElementToBeRemoved(() => screen.getByText('Merchant Details Loading'));
    await userEvent.click(screen.getByText('Check Merchant Details'));
    await waitFor(() => {
      expect(checkModularConfig).toHaveBeenCalledWith(
        expect.objectContaining({ id: 'test_merchant' }),
      );
    });
  });

  test('should show error toast on merchant details fetch error', async () => {
    server.use(getMerchantDetails({ type: 'error' }));
    renderApp({ merchantId: 'testId2' });
    await waitForElementToBeRemoved(() => screen.getByText('Merchant Details Loading'));
    await waitFor(() => {
      expect(screen.getByText('Failed to fetch merchant details')).toBeInTheDocument();
    });
  });
});
