import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Link, Box, EyeIcon, TrashIcon, BillIcon } from '@razorpay/blade/components';
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
  const { id, b2b_export_invoice: invoiceId, status } = item;
  const isPaymentAuthorized = status === PaymentStatus.AUTHORIZED;

  const [isLoading, setIsLoading] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

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
      updateExportPayment({ ...item, b2b_export_invoice: null });
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

  const onInvoiceUploadSuccess = (exportInvoiceId: string) => {
    updateExportPayment({ ...item, b2b_export_invoice: exportInvoiceId });
  };

  const onDismiss = () => {
    //close popup
  };

  const onAddInvoiceClick = () => {
    //open Add Invoice Modal
    console.log(id, onDismiss, showNotification, onInvoiceUploadSuccess);
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
