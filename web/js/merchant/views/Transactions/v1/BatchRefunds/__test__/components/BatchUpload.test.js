import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import { getCustomURL } from 'merchant/components/DocsLink';
import BatchUpload from 'merchant/views/Transactions/v1/BatchRefunds/components/BatchUpload';
import { SAMPLE_BATCH_REFUND_FILE } from 'merchant/views/Transactions/v1/BatchRefunds/List';
import * as ModalActions from 'merchant_common/reducers/modals';
import { fireEvent, render, screen, waitFor } from 'test-utils';

const initProps = {
  batchType: 'refund',
  docUrl: getCustomURL('https://razorpay.com/docs/payments/refunds/batch/'),
  sampleUrl: SAMPLE_BATCH_REFUND_FILE,
  closeUrl: '/refunds/batchuploads',
  title: 'refunds',
  modeFormatted: 'Test',
  uploadBatch: jest.fn(),
  showNotification: jest.fn(),
};

const App = ({ ...props }) => {
  return (
    <ConfirmModalProvider>
      <BatchUpload {...props} />
    </ConfirmModalProvider>
  );
};

describe('Refunds - RefundListFilter Component', () => {
  const openModal = jest.spyOn(ModalActions, 'openModal');

  beforeEach(() => {
    openModal.mockClear();
  });

  const uploadFileToEnableButton = ({ ...props } = {}) => {
    const { container } = render(<App {...initProps} {...props} />);
    const fileInput = container.querySelector(`input[type="file"]`);
    expect(fileInput).toBeInTheDocument();

    const file = new File(['thanks for reviewing this PR'], 'testFile.png', {
      type: 'image/png',
    });

    Object.defineProperty(fileInput, 'files', {
      value: [file],
    });
    fireEvent.change(fileInput);
  };

  test('should render BatchUpload component', () => {
    render(<App />);
    expect(screen.getByTestId('batchrefunds-batchupload')).toBeInTheDocument();
    [initProps.modeFormatted, 'download a sample file'].forEach((property) => {
      expect(screen.getByText(new RegExp(property, 'i'))).toBeInTheDocument();
    });
    ['click here', 'DOCUMENTATION'].forEach((cta) => {
      const linkEl = screen.getByRole('link', { name: new RegExp(cta, 'i') });
      expect(linkEl).toBeInTheDocument();
    });
  });

  test('should have file upload input', () => {
    const { container } = render(<App />);
    const fileInput = container.querySelector(`input[type="file"]`);
    expect(fileInput).toBeInTheDocument();
  });

  test('should enable submit button only if file is uploaded', () => {
    let submitBtn = screen.queryByRole('button', {
      name: 'Submit',
    });
    // submit button should not be present initially
    expect(submitBtn).not.toBeInTheDocument();

    // upload mocked test file
    uploadFileToEnableButton();

    submitBtn = screen.queryByRole('button', {
      name: 'Submit',
    });
    // submit button should be present now
    expect(submitBtn).toBeInTheDocument();
  });

  test('should show confirm prompt submit and cancel buttons', () => {
    // upload mocked test file to enable submit button
    uploadFileToEnableButton();
    fireEvent.click(
      screen.queryByRole('button', {
        name: 'Submit',
      }),
    );

    const modalCancelBtn = screen.getByRole('button', {
      name: /cancel/i,
    });
    expect(modalCancelBtn).toBeInTheDocument();

    const [, modalSubmitBtn] = screen.getAllByRole('button', {
      name: /submit/i,
    });
    expect(modalSubmitBtn).toBeInTheDocument();
  });

  test('should invoke showNotification with sucess when uploadBatch is sucessfull', async () => {
    initProps.uploadBatch.mockReturnValue(Promise.resolve('OK'));

    // upload mocked test file to enable submit button
    uploadFileToEnableButton();
    fireEvent.click(
      screen.queryByRole('button', {
        name: 'Submit',
      }),
    );
    const [, modalSubmitBtn] = screen.getAllByRole('button', {
      name: /submit/i,
    });
    fireEvent.click(modalSubmitBtn);

    expect(initProps.uploadBatch).toHaveBeenCalled();

    await waitFor(() => {
      expect(initProps.showNotification).toHaveBeenCalled();
      expect(initProps.showNotification).toHaveBeenCalledWith({
        type: 'success',
        message: 'Successful',
      });
    });
  });

  test('should invoke showNotification with error when uploadBatch is failure', async () => {
    const mockError = 'uploadBatch NOT OK';
    initProps.uploadBatch.mockReturnValue(
      Promise.reject({
        errors: mockError,
      }),
    );
    // upload mocked test file to enable submit button
    uploadFileToEnableButton();
    fireEvent.click(
      screen.queryByRole('button', {
        name: 'Submit',
      }),
    );
    const [, modalSubmitBtn] = screen.getAllByRole('button', {
      name: /submit/i,
    });
    fireEvent.click(modalSubmitBtn);

    expect(initProps.uploadBatch).toHaveBeenCalled();

    await waitFor(() => {
      expect(initProps.showNotification).toHaveBeenCalled();
      expect(initProps.showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: mockError,
      });
    });
  });

  test('should show proceed dialog instead when its prop is passed', async () => {
    // upload mocked test file to enable submit button
    uploadFileToEnableButton({
      isProceedDialogType: true,
    });
    const proceedBtn = screen.getByRole('button', {
      name: 'Proceed',
    });
    expect(proceedBtn).toBeInTheDocument();
    fireEvent.click(proceedBtn);

    await waitFor(() => {
      expect(openModal).toHaveBeenCalled();
    });
  });
});
