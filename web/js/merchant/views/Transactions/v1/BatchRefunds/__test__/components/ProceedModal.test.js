import ProceedModal from 'merchant/views/Transactions/v1/BatchRefunds/components/ProceedModal';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import * as NotificationsReducers from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';

const initProps = {
  closeUrl: '/refunds/batchuploads',
  submitUploadBatch: jest.fn(),
};

describe('BatchRefunds - ProceedModal', () => {
  const showNotification = jest.spyOn(NotificationsReducers, 'showNotification');
  const closeModal = jest.spyOn(ModalActions, 'closeModal');

  beforeEach(() => {
    showNotification.mockClear();
    closeModal.mockClear();
  });

  test('should render proceed modal', () => {
    render(<ProceedModal {...initProps} />);
    ['Send link and payment instructions to', 'Sms Notify', 'Email Notify'].forEach((textEl) => {
      expect(screen.getByText(new RegExp(textEl, 'i'))).toBeInTheDocument();
    });
    const submitBtn = screen.getByRole('button', {
      name: 'Submit',
    });
    expect(submitBtn).toBeInTheDocument();
  });

  test('should render sms and email CTA checkbox', () => {
    render(<ProceedModal {...initProps} />);
    const smsCTA = screen.getByLabelText('Sms Notify');
    expect(smsCTA).toBeInTheDocument();

    const emailCTA = screen.getByLabelText('Email Notify');
    expect(emailCTA).toBeInTheDocument();
  });

  describe('should should show the notification if upload success', () => {
    test('if sms or email are not enabled', async () => {
      render(
        <ProceedModal {...initProps} submitUploadBatch={jest.fn(() => Promise.resolve('OK'))} />,
      );

      const submitBtn = screen.getByRole('button', {
        name: 'Submit',
      });
      expect(submitBtn).toBeInTheDocument();
      fireEvent.click(submitBtn);

      await waitFor(() => {
        expect(closeModal).toHaveBeenCalled();
        expect(showNotification).toHaveBeenCalledWith({
          type: 'success',
          message: 'Successful',
        });
        // assert presence of notification text on the screen
        expect(screen.getByText('Successful')).toBeInTheDocument();
      });
    });

    test('if sms or email are enabled', async () => {
      render(
        <ProceedModal {...initProps} submitUploadBatch={jest.fn(() => Promise.resolve('OK'))} />,
      );

      fireEvent.click(screen.getByLabelText('Email Notify'));
      fireEvent.click(screen.getByLabelText('Sms Notify'));

      const submitBtn = screen.getByRole('button', {
        name: 'Submit',
      });
      expect(submitBtn).toBeInTheDocument();
      fireEvent.click(submitBtn);

      await waitFor(() => {
        expect(closeModal).toHaveBeenCalled();
        expect(showNotification).toHaveBeenCalledWith({
          type: 'success',
          message: 'Successful',
        });
        expect(screen.getByText('Successful')).toBeInTheDocument();
      });
    });
  });

  test('should show the notification if upload fails', async () => {
    const mockError = 'submitUploadBatch NOT OK';
    render(
      <ProceedModal
        {...initProps}
        submitUploadBatch={jest.fn(() =>
          Promise.reject({
            errors: mockError,
          }),
        )}
      />,
    );

    const submitBtn = screen.getByRole('button', {
      name: 'Submit',
    });
    expect(submitBtn).toBeInTheDocument();
    fireEvent.click(submitBtn);

    await waitFor(() => {
      expect(closeModal).toHaveBeenCalled();
      expect(showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: mockError,
      });
      expect(screen.getByText(mockError)).toBeInTheDocument();
    });
  });
});
