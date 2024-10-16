import React, { useEffect, useState } from 'react';
import { Action, bindActionCreators, Dispatch } from 'redux';
import { connect } from 'react-redux';

import { Box, Button, Text, Spinner } from '@razorpay/blade/components';

import { showNotification } from 'merchant_common/reducers/notifications';
import { getReceiptDetails, sendReceipt } from 'merchant/views/PaymentPages/PaymentPages/model';

import { GetReceiptDetailsResponse, PaymentPagePaymentReceiptProps, ReceiptDetails } from './types';

const PaymentPagePaymentReceipt: React.FC<PaymentPagePaymentReceiptProps> = ({
  paymentId,
  showNotification,
}) => {
  const [receiptDetails, setReceiptDetails] = useState<ReceiptDetails>({});

  const [isLoading, setIsLoading] = useState(false);
  const [isActionInProgress, setIsActionInProgess] = useState(false);

  useEffect(() => {
    if (paymentId && !Object.keys(receiptDetails).length) {
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
    }

    return () => {
      setReceiptDetails({});
    };
  }, [paymentId]);

  const { invoiceId, receipt, downloadUrl } = receiptDetails;

  const sendReceiptHandler = () => {
    setIsActionInProgess(true);
    sendReceipt(paymentId, receipt)
      .then((res) => {
        if (res.data && res.data.success) {
          showNotification({
            type: 'success',
            message: 'Receipt sent successfully.',
          });
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
    sendReceiptHandler();
  };

  const handleDownload = () => {
    openDownloadReceiptUrl();
  };

  if (isLoading) {
    return <Spinner size="medium" color="neutral" accessibilityLabel="receipt-details-loader" />;
  }

  return (
    <>
      {invoiceId && receipt ? (
        <Box display="flex" flexDirection="column" gap="spacing.4">
          <Text>Reference ID: {receipt}</Text>
          <Box display="flex" gap="spacing.4" alignItems="flex-end">
            <Button
              type="button"
              variant="secondary"
              onClick={handleSend}
              isDisabled={isActionInProgress || !receipt}
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
        </Box>
      ) : (
        <Text variant="body" size="small">
          --
        </Text>
      )}
    </>
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
