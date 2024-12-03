import React, { useEffect, useState } from 'react';
import { Action, bindActionCreators, Dispatch } from 'redux';
import { connect } from 'react-redux';

import { Box, Button, Text, Spinner, TextInput } from '@razorpay/blade/components';

import { showNotification } from 'merchant_common/reducers/notifications';
import {
  getReceiptDetails,
  saveReceipt,
  sendReceipt,
} from 'merchant/views/PaymentPages/PaymentPages/model';

import { GetReceiptDetailsResponse, PaymentPagePaymentReceiptProps, ReceiptDetails } from './types';

const PaymentPagePaymentReceipt: React.FC<PaymentPagePaymentReceiptProps> = ({
  paymentId,
  showNotification,
}) => {
  const [receiptDetails, setReceiptDetails] = useState<ReceiptDetails>({});
  const [referenceId, setReferenceId] = useState<string | undefined>('');
  const [shouldShowCustomReceiptSection, setShouldShowCustomReceiptSection] =
    useState<boolean>(false);

  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isActionInProgress, setIsActionInProgess] = useState<boolean>(false);

  const [manualActionLabel, setManualActionLabel] = useState<string>('');

  const fetchReceiptDetails = () => {
    setIsLoading(true);
    getReceiptDetails(paymentId)
      .then((res: { data: GetReceiptDetailsResponse }) => {
        if (res && res.data) {
          const { invoice_id = '', receipt = '', receipt_download_url = '' } = res.data;
          setReceiptDetails({
            invoiceId: invoice_id,
            receipt,
            downloadUrl: receipt_download_url,
          });
        } else {
          throw new Error('Fetching receipt failed, please try again later.');
        }
      })
      .catch((error) => {
        showNotification({
          type: 'error',
          message: error?.errors?.[0]
            ? error.errors[0]
            : 'Fetching receipt failed, please try again later.',
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  useEffect(() => {
    if (paymentId && !Object.keys(receiptDetails).length) {
      fetchReceiptDetails();
    }

    return () => {
      setReceiptDetails({});
    };
  }, [paymentId]);

  const { invoiceId, receipt, downloadUrl } = receiptDetails;

  const sendReceiptHandler = () => {
    setIsActionInProgess(true);
    sendReceipt(paymentId, receipt || referenceId)
      .then((res) => {
        if (res.data && res.data.success) {
          showNotification({
            type: 'success',
            message: 'Receipt sent successfully.',
          });
          if (!receipt) {
            fetchReceiptDetails();
          }
        } else {
          throw new Error('Something went wrong, please try again.');
        }
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors?.[0] ? errors[0] : 'Something went wrong, please try again.',
        });
      })
      .finally(() => {
        setIsActionInProgess(false);
      });
  };

  const openDownloadReceiptUrl = () => {
    showNotification({
      type: 'success',
      message: 'Receipt is downloading...',
    });
    window.location.href = downloadUrl as string;
  };

  const handleSend = () => {
    if (!receipt && !shouldShowCustomReceiptSection) {
      setShouldShowCustomReceiptSection(true);
      setManualActionLabel('Send');
    } else {
      sendReceiptHandler();
    }
  };

  const handleDownload = () => {
    if (!receipt && !shouldShowCustomReceiptSection) {
      setShouldShowCustomReceiptSection(true);
      setManualActionLabel('Save');
    } else {
      openDownloadReceiptUrl();
    }
  };

  const handleSaveReceipt = () => {
    setIsActionInProgess(true);
    saveReceipt(paymentId, referenceId)
      .then((res) => {
        if (res && res.success) {
          fetchReceiptDetails();
        } else {
          throw new Error('Something went wrong, please try again.');
        }
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors?.[0] ? errors[0] : 'Something went wrong, please try again.',
        });
      })
      .finally(() => {
        setIsActionInProgess(false);
      });
  };

  const handleCancel = () => {
    setShouldShowCustomReceiptSection(false);
  };

  if (isLoading) {
    return <Spinner size="medium" color="neutral" accessibilityLabel="receipt-details-loader" />;
  }

  return invoiceId ? (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      {!receipt && shouldShowCustomReceiptSection ? (
        <>
          <TextInput
            label="Reference ID"
            placeholder="Enter Reference ID"
            value={referenceId}
            onChange={(e) => setReferenceId(e.value)}
            isDisabled={isActionInProgress}
          />
          <Box display="flex" gap="spacing.4" justifyContent="flex-end">
            <Button
              type="button"
              variant="secondary"
              onClick={handleCancel}
              isDisabled={isActionInProgress}
              size="small"
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="secondary"
              onClick={manualActionLabel === 'Send' ? handleSend : handleSaveReceipt}
              isDisabled={isActionInProgress}
              size="small"
            >
              {isActionInProgress
                ? manualActionLabel === 'Send'
                  ? 'Sending...'
                  : 'Saving...'
                : manualActionLabel}
            </Button>
          </Box>
        </>
      ) : (
        <>
          {receipt ? <Text>Reference ID: {receipt}</Text> : null}
          <Box display="flex" gap="spacing.4" alignItems="flex-end">
            <Button
              type="button"
              variant="secondary"
              onClick={handleSend}
              isDisabled={isActionInProgress}
              size="small"
            >
              {isActionInProgress ? 'Sending...' : 'Send'}
            </Button>
            <Button
              type="button"
              variant="secondary"
              onClick={handleDownload}
              isDisabled={isActionInProgress || !downloadUrl}
              size="small"
            >
              Download
            </Button>
          </Box>
        </>
      )}
    </Box>
  ) : (
    <Text> -- </Text>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<Action>) =>
  bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(PaymentPagePaymentReceipt);
