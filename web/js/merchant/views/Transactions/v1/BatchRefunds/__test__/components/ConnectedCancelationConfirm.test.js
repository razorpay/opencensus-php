import { ConnectedCancelConfirmation } from 'merchant/views/Transactions/v1/BatchRefunds/components/BatchList';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import { mock_batch_data } from 'merchant/views/Transactions/v1/BatchRefunds/__test__/mock';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { updateBatchInList, fetchBatchAjax } from 'merchant/reducers/batches';

jest.mock('merchant/reducers/batches', () => ({
  ...jest.requireActual('merchant/reducers/batches'),
  fetchBatchAjax: jest.fn(),
  updateBatchInList: jest.fn(),
}));

const mockCloseModal = jest.fn();
const mockCancelBatchRefund = jest.fn(() => Promise.resolve('OK'));

const App = () => {
  return (
    <ConnectedCancelConfirmation
      batch={mock_batch_data}
      cancelBatchRefund={mockCancelBatchRefund}
      closeModal={mockCloseModal}
    />
  );
};

describe('BatchRefunds - BatchList CancellationConfirm Modal', () => {
  const showNotification = jest.spyOn(NotificationsActions, 'showNotification');

  afterEach(() => {
    showNotification.mockClear();
    fetchBatchAjax.mockClear();
  });
  test('should render batch refund cancel confirmation component', () => {
    render(<App />);
    expect(
      screen.getByText(/Are you sure you want to cancel the batch file?/i),
    ).toBeInTheDocument();
    // Dismiss CTA
    expect(
      screen.getByRole('button', {
        name: "No don't",
      }),
    ).toBeInTheDocument();
    // Proceed CTA
    expect(
      screen.getByRole('button', {
        name: 'Yes, cancel',
      }),
    ).toBeInTheDocument();
  });

  test('should call closeModal on dismiss CTA click', () => {
    render(<App />);
    const dismissBtn = screen.getByRole('button', {
      name: "No don't",
    });
    fireEvent.click(dismissBtn);
    expect(mockCloseModal).toHaveBeenCalled();
  });

  test('should call fetchBatchAjax on cancel proceed click', () => {
    fetchBatchAjax.mockReturnValue(Promise.resolve({ batch: {} }));
    render(<App />);
    const cancelBtn = screen.getByRole('button', {
      name: 'Yes, cancel',
    });
    expect(cancelBtn).toBeInTheDocument();
    fireEvent.click(cancelBtn);
  });

  describe('Cancellation process', () => {
    const mockBatchStatusAndClickOnCancel = (status) => {
      fetchBatchAjax.mockReturnValue(
        Promise.resolve({
          batch: {
            status,
          },
        }),
      );
      render(<App />);
      const cancelBtn = screen.getByRole('button', {
        name: 'Yes, cancel',
      });
      expect(cancelBtn).toBeInTheDocument();
      fireEvent.click(cancelBtn);
    };
    test('should call cancel batch and show success notification when batch call returns status as created', async () => {
      mockBatchStatusAndClickOnCancel('created');
      await waitFor(() => {
        expect(mockCancelBatchRefund).toHaveBeenCalled();
        expect(mockCloseModal).toHaveBeenCalled();
        const notificationMessage = 'This batch cancellation initiated.';
        expect(showNotification).toHaveBeenCalledWith({
          type: 'success',
          message: notificationMessage,
        });
        expect(screen.getByText(notificationMessage)).toBeInTheDocument();
      });
    });

    [
      {
        input: 'processed',
        output: 'Batch refund already processed.',
      },
      {
        input: 'processing',
        output: 'Batch refund is already being processed.',
      },
      {
        input: 'cancelled',
        output: 'Batch refund already cancelled.',
      },
    ].forEach(({ input, output }) => {
      test(`should call update batch and show error notification when batch call returns status as ${input}`, async () => {
        mockBatchStatusAndClickOnCancel(input);
        await waitFor(() => {
          expect(updateBatchInList).toHaveBeenCalled();
          expect(mockCloseModal).toHaveBeenCalled();
          expect(showNotification).toHaveBeenCalledWith({
            type: 'error',
            message: output,
          });
          expect(screen.getByText(output)).toBeInTheDocument();
        });
      });
    });
  });
});
