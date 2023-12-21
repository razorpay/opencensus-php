import { uploadInvoice } from 'merchant/reducers/paymentUploadInvoice';
import PaymentList from 'merchant/views/Transactions/v1/UploadInvoice/components/PaymentList';
import {
  TEST_ITEMS,
  MOCK_USER,
  MOCK_PNG_FILE,
} from 'merchant/views/Transactions/v1/UploadInvoice/components/__tests__/mocks/fixtures/paymentList';
import { render, screen, userEvent } from 'test-utils';
import {
  JPMC_FEATURE_FLAG,
  UPLOAD_INVOICES_TYPE,
} from 'merchant/views/Transactions/v1/UploadInvoice/components/constants';

jest.mock('merchant/views/Transactions/v1/BatchRefunds/HeaderActions', () => () => <div />);
jest.mock('common/ui/DateRangePicker/index.js', () => () => <div />);
jest.mock('merchant/reducers/paymentUploadInvoice', () => ({
  uploadInvoice: jest.fn(),
  viewInvoice: jest.fn(),
}));

const renderComponent = (props) => {
  return render(<PaymentList {...props} />, {
    initialEntries: ['/'],
  });
};

describe('Test PaymentsListContainer', () => {
  let showNotification = jest.fn();
  let uploadInvoicePending = jest.fn();
  let uploadInvoiceSuccess = jest.fn();
  let uploadInvoiceError = jest.fn();

  beforeEach(() => {
    showNotification = jest.fn();
    uploadInvoicePending = jest.fn();
    uploadInvoiceSuccess = jest.fn();
    uploadInvoiceError = jest.fn();
  });

  test('should render without breaking', () => {
    renderComponent({ items: [], loading: false });
    expect(screen.getAllByText('Captured')).toHaveLength(1);
  });

  test('should upload file on upload invoice button click', async () => {
    renderComponent({
      items: TEST_ITEMS,
      loading: false,
      uploadInvoicePending,
      uploadInvoiceSuccess,
      uploadInvoiceError,
      showNotification,
      user: MOCK_USER,
    });

    expect(screen.getAllByText('Captured')).toHaveLength(2);

    const uploadInvoiceButton = screen.getByText(/Upload Invoice/);
    const fileUploader = screen.getByTestId('opgsp-file-uploader');

    await userEvent.click(uploadInvoiceButton);
    await userEvent.upload(fileUploader, MOCK_PNG_FILE);

    expect(uploadInvoice).toHaveBeenCalledWith('id', MOCK_PNG_FILE, UPLOAD_INVOICES_TYPE.OPGSP);
    expect(showNotification).toHaveBeenCalledWith({
      message: 'File uploaded successfully',
      type: 'success',
    });
    expect(uploadInvoicePending).toHaveBeenCalledWith({ id: 'id' });
    expect(uploadInvoiceSuccess).toHaveBeenCalledWith({ id: 'id' });
    expect(uploadInvoiceError).not.toHaveBeenCalled();
  });

  test('should upload file on upload invoice button click when JPMC feature flag enabled', async () => {
    renderComponent({
      items: TEST_ITEMS,
      loading: false,
      uploadInvoicePending,
      uploadInvoiceSuccess,
      showNotification,
      user: {
        tags: [JPMC_FEATURE_FLAG],
      },
    });

    const uploadInvoiceButton = screen.getByText(/Upload Invoice/);
    const fileUploader = screen.getByTestId('opgsp-file-uploader');

    await userEvent.click(uploadInvoiceButton);
    await userEvent.upload(fileUploader, MOCK_PNG_FILE);

    expect(uploadInvoice).toHaveBeenCalledWith('id', MOCK_PNG_FILE, UPLOAD_INVOICES_TYPE.JPMC);
    expect(showNotification).toHaveBeenCalledWith({
      message: 'File uploaded successfully',
      type: 'success',
    });
    expect(uploadInvoicePending).toHaveBeenCalledWith({ id: 'id' });
    expect(uploadInvoiceSuccess).toHaveBeenCalledWith({ id: 'id' });
  });

  test('should show error notification if uploading invoice fails', async () => {
    renderComponent({
      items: TEST_ITEMS,
      loading: false,
      uploadInvoicePending,
      uploadInvoiceError,
      showNotification,
      user: MOCK_USER,
    });

    uploadInvoice.mockImplementationOnce(() => Promise.reject('Failed to upload the file'));

    const uploadInvoiceButton = screen.getByText(/Upload Invoice/);
    const fileUploader = screen.getByTestId('opgsp-file-uploader');

    await userEvent.click(uploadInvoiceButton);
    await userEvent.upload(fileUploader, MOCK_PNG_FILE);

    expect(uploadInvoice).toHaveBeenCalledWith('id', MOCK_PNG_FILE, UPLOAD_INVOICES_TYPE.OPGSP);
    expect(showNotification).toHaveBeenCalledWith({
      message: 'Failed to upload file. Please try again!',
      type: 'error',
    });
    expect(uploadInvoicePending).toHaveBeenCalledWith({ id: 'id' });
    expect(uploadInvoiceError).toHaveBeenCalledWith({ id: 'id' });
    expect(uploadInvoiceSuccess).not.toHaveBeenCalled();
  });
});
