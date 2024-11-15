import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import RTOHistoryUpload from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload';
import { fireEvent, screen, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { render } from '@testing-library/react';
import { storeWithInitialState } from 'merchant/store';
import { fetchAllRTOHistoryBatches } from 'merchant/reducers/magicCheckout/rtoHistoryUpload/action';
import { MockFetchResponse } from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/__test__/mocks/fixtures';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

jest.mock('merchant/reducers/magicCheckout/rtoHistoryUpload/action', () => ({
  ...jest.requireActual('merchant/reducers/magicCheckout/rtoHistoryUpload/action'),
  fetchAllRTOHistoryBatches: jest.fn(),
  createRTOHistoryBatches: jest.fn(),
}));

const fetchList = jest.fn();
const validateFile = jest.fn();

const initProps = {
  fetchAll: fetchList,
  validateBatch: validateFile,
};

const initState = {
  rtoHistoryUpload: {
    error: null,
    loading: false,
    items: [MockFetchResponse],
    isUploadAllowed: true,
  },
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider
      store={storeWithInitialState({
        ...state,
      })}
    >
      <BladeProvider themeTokens={bladeTheme}>
        <RTOHistoryUpload {...initProps} {...props} />
      </BladeProvider>
    </Provider>
  );
};

describe('Order History - Order History Component', () => {
  const mockFetchFileUploads = (returnValue) => {
    fetchAllRTOHistoryBatches.mockReturnValue({
      type: 'RTO_HISTORY_BATCHES_FETCH',
      payload: Promise.resolve(returnValue),
    });
  };

  const openModal = jest.spyOn(ModalActions, 'openModal');
  const showNotification = jest.spyOn(NotificationsActions, 'showNotification');

  afterEach(() => {
    fetchAllRTOHistoryBatches.mockReset();
    openModal.mockClear();
    showNotification.mockClear();
  });

  test('should show order history instead of RTO history', async () => {
    mockFetchFileUploads(MockFetchResponse);
    render(<App state={initState} />);

    await waitFor(() => {
      expect(screen.queryByText(/RTO History/i)).not.toBeInTheDocument();
      expect(screen.queryAllByText(/Order History/i)).not.toHaveLength(0);
    });
  });

  test('should show upload order history CTA if isUploadAllowed is true', async () => {
    mockFetchFileUploads(MockFetchResponse);
    render(<App state={initState} />);

    await waitFor(() => {
      expect(screen.queryByTestId('upload-order-history-cta')).toBeInTheDocument();
    });
  });

  test('should not show upload order history CTA if isUploadAllowed is false', async () => {
    const updatedState = {
      rtoHistoryUpload: { ...initState.rtoHistoryUpload, isUploadAllowed: false },
    };
    mockFetchFileUploads(MockFetchResponse);
    render(<App state={updatedState} />);

    await waitFor(() => {
      expect(screen.queryByTestId('upload-order-history-cta')).not.toBeInTheDocument();
    });
  });

  test('should show empty component when items array is empty', async () => {
    const updatedState = { rtoHistoryUpload: { ...initState.rtoHistoryUpload, items: [] } };
    mockFetchFileUploads(MockFetchResponse);
    render(<App state={updatedState} />);

    await waitFor(() => {
      expect(screen.queryByTestId('order-history-empty-component')).toBeInTheDocument();
    });
  });

  test('should not show empty component when items array is empty', async () => {
    const updatedState = { rtoHistoryUpload: { ...initState.rtoHistoryUpload, items: [] } };
    mockFetchFileUploads(MockFetchResponse);
    render(<App state={updatedState} />);

    await waitFor(() => {
      expect(screen.queryByTestId('order-history-empty-component')).not.toBeInTheDocument();
    });
  });

  test('should open batch upload when click on upload CTA', async () => {
    mockFetchFileUploads(MockFetchResponse);
    render(<App state={initState} />);

    await waitFor(() => {
      const clickToUploadCTA = screen.queryByRole('button');
      expect(clickToUploadCTA).toBeInTheDocument();
      fireEvent.click(clickToUploadCTA);
      expect(openModal).toHaveBeenCalled();
    });
  });
});
