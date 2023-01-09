import { fireEvent, screen, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { render } from '@testing-library/react';
import { storeWithInitialState } from 'merchant/store';
import { createRTOHistoryBatches } from 'merchant/reducers/magicCheckout/rtoHistoryUpload/action';
import {
  MockFetchResponse,
  MockFetchSuccessPayload,
  MockTimeErrorPayload,
  MockCreateErrorResponse,
} from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/__test__/mocks/fixtures';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import ConfirmationModalActions from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/components/ModalActions';

jest.mock('merchant/reducers/magicCheckout/rtoHistoryUpload/action', () => ({
  ...jest.requireActual('merchant/reducers/magicCheckout/rtoHistoryUpload/action'),
  createRTOHistoryBatches: jest.fn(),
}));

const mockedState = {
  rtoHistoryUpload: {
    fileId: 'file_KFkSSPUmovBszP',
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
      <ConfirmationModalActions {...props} />
    </Provider>
  );
};

describe('Render modal action component while uploading batch file', () => {
  test('Render confirmation buttons', () => {
    render(<App />);
    const cancelBtn = screen.queryByRole('button', {
      name: /Cancel/i,
    });
    const confirmBtn = screen.queryByRole('button', {
      name: /Confirm/i,
    });

    expect(cancelBtn).toBeInTheDocument();
    expect(confirmBtn).toBeInTheDocument();
  });

  test('Triggering event on clicking confirmation buttons', () => {
    render(<App />);
    const cancelBtn = screen.queryByRole('button', {
      name: /Cancel/i,
    });
    const confirmBtn = screen.queryByRole('button', {
      name: /Confirm/i,
    });

    expect(cancelBtn).toBeInTheDocument();
    expect(confirmBtn).toBeInTheDocument();
    fireEvent.click(cancelBtn);
    fireEvent.click(confirmBtn);
  });

  test('Confirm CTA should disable once clicked', () => {
    render(<App />);
    const confirmBtn = screen.queryByRole('button', {
      name: /Confirm/i,
    });

    expect(confirmBtn).toBeInTheDocument();
    fireEvent.click(confirmBtn);

    expect(confirmBtn).toHaveClass('disabled');
  });

  test('Confirm CTA should not be disabled initially', () => {
    render(<App state={mockedState} />);
    const confirmBtn = screen.queryByRole('button', {
      name: /Confirm/i,
    });

    expect(confirmBtn).toBeInTheDocument();

    expect(confirmBtn).not.toHaveClass('disabled');
  });

  describe('describe batch actions', () => {
    const showNotification = jest.spyOn(NotificationsActions, 'showNotification');

    afterEach(() => {
      showNotification.mockClear();
    });

    test('should show success notification when action is completed', async () => {
      createRTOHistoryBatches.mockReturnValue({
        type: 'RTO_HISTORY_BATCHES_CREATE',
        payload: Promise.resolve(MockFetchSuccessPayload),
      });

      render(<App state={mockedState} />);

      const confirmBtn = screen.queryByRole('button', {
        name: /Confirm/i,
      });

      expect(confirmBtn).toBeInTheDocument();
      expect(confirmBtn).not.toHaveClass('disabled');

      fireEvent.click(confirmBtn);

      await waitFor(() => {
        expect(showNotification).toHaveBeenCalledWith({
          type: 'success',
          message: 'Order history file uploaded successfully.',
        });
      });
    });

    test('should show time period expired notification when file uploaded is after 30 days', async () => {
      createRTOHistoryBatches.mockReturnValue({
        type: 'RTO_HISTORY_BATCHES_CREATE',
        payload: Promise.reject(MockTimeErrorPayload),
      });

      render(<App state={mockedState} />);

      const confirmBtn = screen.queryByRole('button', {
        name: /Confirm/i,
      });

      expect(confirmBtn).toBeInTheDocument();
      expect(confirmBtn).not.toHaveClass('disabled');

      fireEvent.click(confirmBtn);

      await waitFor(() => {
        expect(showNotification).toHaveBeenCalledWith({
          type: 'error',
          message: "Order history can't be uploaded after 30 days.",
        });
      });
    });

    test('should show default error notification when file upload is unsuccessful', async () => {
      createRTOHistoryBatches.mockReturnValue({
        type: 'RTO_HISTORY_BATCHES_CREATE',
        payload: Promise.reject(MockCreateErrorResponse),
      });

      render(<App state={mockedState} />);

      const confirmBtn = screen.queryByRole('button', {
        name: /Confirm/i,
      });

      expect(confirmBtn).toBeInTheDocument();
      expect(confirmBtn).not.toHaveClass('disabled');

      fireEvent.click(confirmBtn);

      await waitFor(() => {
        expect(showNotification).toHaveBeenCalledWith({
          type: 'error',
          message: 'Order history file upload unsuccessful. Please Try again.',
        });
      });
    });
  });
});
