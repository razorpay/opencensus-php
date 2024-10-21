import React from 'react';
import { render, screen, waitFor } from 'test-utils';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import InvoiceActions from 'merchant/views/Transactions/v2/UploadInvoices/components/PaymentsTable/InvoiceActions';
import { PaymentStatus } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';
import { PopupContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/PopupContext';
import { MODAL_TYPES } from 'merchant/views/Transactions/v2/UploadInvoices/constants';
import * as reduxActions from 'merchant/reducers/collection';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as services from 'merchant/views/Transactions/v2/UploadInvoices/components/PaymentsTable/services';

// Mocked functions for props
const mockOpenPopup = jest.fn();
const mockClosePopup = jest.fn();

const item = {
  id: '123',
  enitity_id: 'INV-456',
  status: PaymentStatus.AUTHORIZED,
};

// Mock context
const renderComponent = (props) => {
  return render(
    <PopupContext.Provider value={{ openPopup: mockOpenPopup, closePopup: mockClosePopup }}>
      <InvoiceActions item={item} {...props} />
    </PopupContext.Provider>,
  );
};

describe('InvoiceActions', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('renders the view and delete buttons when invoiceId is present', () => {
    renderComponent();

    expect(screen.getByText('View')).toBeInTheDocument();
    expect(screen.getByText('Delete')).toBeInTheDocument();
  });

  test('renders the Add Invoice button when invoiceId is not present and status is AUTHORIZED', () => {
    const itemWithoutInvoice = { ...item, enitity_id: null };
    renderComponent({ item: itemWithoutInvoice });

    expect(screen.getByText('Add Invoice')).toBeInTheDocument();
  });

  test('calls openPopup with the correct arguments when Add Invoice is clicked', async () => {
    const itemWithoutInvoice = { ...item, enitity_id: null };
    renderComponent({ item: itemWithoutInvoice });

    await userEvent.click(screen.getByText('Add Invoice'));

    expect(mockOpenPopup).toHaveBeenCalledWith(MODAL_TYPES.UPLOAD_INVOICE, {
      id: item.id,
      onDismiss: expect.any(Function),
      showNotification: expect.any(Function),
      onUploadSuccess: expect.any(Function),
    });
  });

  test('calls viewInvoice and opens a new tab when View is clicked', async () => {
    const viewInvoiceSpy = jest
      .spyOn(services, 'viewInvoice')
      .mockReturnValue(Promise.resolve('http://example.com/invoice.pdf'));

    renderComponent();
    window.open = jest.fn();

    await userEvent.click(screen.getByText('View'));

    await waitFor(() => expect(viewInvoiceSpy).toHaveBeenCalledWith(item.enitity_id));
    expect(window.open).toHaveBeenCalledWith('http://example.com/invoice.pdf', '_blank');
  });

  test('shows notification when delete fails', async () => {
    const deleteInvoiceSpy = jest
      .spyOn(services, 'deleteInvoice')
      .mockRejectedValue(new Error('Delete failed'));
    const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

    renderComponent();
    await userEvent.click(screen.getByText('Delete'));

    await waitFor(() => expect(deleteInvoiceSpy).toHaveBeenCalledWith(item.enitity_id));
    await waitFor(() =>
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'Failed to delete file. Please try again!',
      }),
    );
  });

  test('updates the export payment and shows success notification when delete is successful', async () => {
    const deleteInvoiceSpy = jest
      .spyOn(services, 'deleteInvoice')
      .mockReturnValue(Promise.resolve(true));
    const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
    const updateExportPaymentSpy = jest.spyOn(reduxActions, 'updateExportPayment');

    renderComponent();
    await userEvent.click(screen.getByText('Delete'));

    await waitFor(() => expect(deleteInvoiceSpy).toHaveBeenCalledWith(item.enitity_id));
    expect(updateExportPaymentSpy).toHaveBeenCalledWith({
      ...item,
      enitity_id: null,
    });
    expect(showNotificationSpy).toHaveBeenCalledWith({
      type: 'success',
      message: 'File deleted successfully!',
    });
  });
});
