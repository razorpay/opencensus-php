import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import Loader from 'common/components/Loader';
import { withRouter } from 'common/deprecated/withRouter';
import { fetchEncodedPaymentReceipt } from 'merchant/views/Transactions/model';
import { closeModal } from 'merchant_common/reducers/modals';
import { withSplitzService } from 'common/splitz';
import { showNotification as showNotificationAction } from 'merchant_common/reducers/notifications';
import fileDownload from 'common/utils/file-download';

interface IPaymentReceipt {
  showNotification: (payload: { type: 'error' | 'success'; message: string }) => void;
  id: string;
  closeModal: () => void;
}
const PaymentReceipt = ({ closeModal, id, showNotification }: IPaymentReceipt) => {
  const [encodedImage, setEncodedImage] = useState(null);
  const [isLoading, setLoading] = useState(true);
  const [isOpen, setIsOpen] = useState<boolean>(true);
  const dismissModal = () => {
    setIsOpen(false);
    closeModal();
  };

  useEffect(() => {
    async function fetchData() {
      try {
        const response = await fetchEncodedPaymentReceipt(id);
        setEncodedImage(response.receipt_encoded_image);
        setLoading(false);
      } catch (err) {
        setLoading(false);
        showNotification({
          type: 'error',
          message: 'No Charge Slip found.',
        });
        dismissModal();
      }
    }
    fetchData();
  }, []);

  const handleDownload = () => {
    return fileDownload(encodedImage, `${id}.png`, 'image/png');
  };

  const handlePrint = () => {
    const image = new Image();
    image.src = `data:image/png;base64,${encodedImage}`;
    image.onload = () => {
      const printWindow = window.open('', 'Print', 'height=600,width=800');
      if (printWindow) {
        printWindow.document.write('<html><head><title>Print</title></head><body>');
        printWindow.document.write(`<img src="${image.src}" style="max-width:100%;" />`);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
      } else {
        console.error('Failed to open print window. Please check your browser settings.');
      }
    };
  };

  return (
    <Modal isOpen={isOpen} onDismiss={dismissModal} size="medium">
      <ModalHeader title="Payment Receipt" />
      <ModalBody>
        <Box display="flex" justifyContent="center">
          {isLoading ? (
            <Loader />
          ) : (
            <Box>
              {encodedImage ? (
                <img alt="receipt" src={`data:image/png;base64,${encodedImage}`} />
              ) : null}
            </Box>
          )}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end">
          <Button type="button" variant="secondary" marginX="spacing.5" onClick={handleDownload}>
            Download
          </Button>
          <Button type="button" variant="primary" onClick={handlePrint}>
            Print
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

function mapDispatchToProps(dispatch) {
  return bindActionCreators(
    {
      showNotification: showNotificationAction,
      closeModal,
    },
    dispatch,
  );
}

export default withSplitzService(
  withRouter<any>(compose(connect(null, mapDispatchToProps)(PaymentReceipt))),
);
