import React, { useState, useContext } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Link, Box, EyeIcon, TrashIcon, BillIcon } from '@razorpay/blade/components';
import { PopupContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/PopupContext';
import { MODAL_TYPES } from 'merchant/views/Transactions/v2/UploadInvoices/constants';
import { PaymentStatus } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateExportPayment } from 'merchant/reducers/collection';

import { deleteInvoice, viewInvoice } from './services';
import { InvoiceActionProps } from './types';

const InvoiceActions = ({
  item,
  showNotification,
  updateExportPayment,
}: InvoiceActionProps): JSX.Element => {
  const { id, enitity_id: invoiceId, status } = item;
  const isPaymentAuthorized = status === PaymentStatus.AUTHORIZED;

  const [isLoading, setIsLoading] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  const { openPopup, closePopup } = useContext(PopupContext);

  const onInvoiceUploadSuccess = (exportInvoiceId: string) => {
    updateExportPayment({ ...item, enitity_id: exportInvoiceId });
  };

  const onDismiss = () => {
    closePopup();
  };

  const onAddInvoiceClick = () => {
    openPopup(MODAL_TYPES.UPLOAD_INVOICE, {
      id,
      onDismiss,
      showNotification,
      onUploadSuccess: onInvoiceUploadSuccess,
    });
  };

  const onViewClick = async () => {
    if (isLoading || !invoiceId) return;
    try {
      setIsLoading(true);
      const url = await viewInvoice(invoiceId);
      window.open(url, '_blank');
    } catch {
      showNotification({
        type: 'error',
        message: 'Failed to view file. Please try again!',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const onDeleteClick = async () => {
    if (isDeleting || !invoiceId) return;
    try {
      setIsDeleting(true);
      await deleteInvoice(invoiceId);
      updateExportPayment({ ...item, enitity_id: null });
      showNotification({
        type: 'success',
        message: 'File deleted successfully!',
      });
    } catch {
      showNotification({
        type: 'error',
        message: 'Failed to delete file. Please try again!',
      });
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <Box>
      {invoiceId ? (
        <Box
          display="flex"
          flexDirection="row"
          maxWidth="145px"
          width="145px"
          justifyContent="space-between"
        >
          <Link onClick={onViewClick} variant="button" icon={EyeIcon} size="medium">
            {isLoading ? 'Loading...' : 'View'}
          </Link>
          <Link onClick={onDeleteClick} variant="button" icon={TrashIcon} size="medium">
            {isDeleting ? 'Deleting...' : 'Delete'}
          </Link>
        </Box>
      ) : (
        isPaymentAuthorized && (
          <Link variant="button" icon={BillIcon} size="medium" onClick={onAddInvoiceClick}>
            Add Invoice
          </Link>
        )
      )}
    </Box>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification,
      updateExportPayment,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(InvoiceActions);
