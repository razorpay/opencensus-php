/* eslint-disable import/no-restricted-paths */
import React, { useState } from 'react';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import { Box, ModalFooter, Alert } from '@razorpay/blade/components';
import { Button } from 'merchant_common/views/Reports/components';
import { useStore } from 'shell/commonStore';
import { fetchBouncememo } from './BounceMemo.types';
import pdfCreation from 'merchant/views/Transactions/v1/Payments/components/PdfCreation';
import { TITLE } from './constants';
//Bounce memo Changes
//When the merchant clicks the download button then fetch the data from emandate service and pass the data into the pdfcreation code to generate the bounce memo pdf.

const BounceMemoPopup = ({ paymentID, user }: any): JSX.Element => {
  const showNotification = useStore((state) => state.showNotification);
  const [isSubmitButtonLoading, setSubmitButtonLoading] = useState(false);

  const { id: merchantId } = user;

  const FetchBouncememo = async () => {
    setSubmitButtonLoading(true);
    try {
      const response = await fetchBouncememo(paymentID);
      if (!response.data || !response.data.data) {
        showNotification({
          type: 'error',
          message: response.data.error,
        });
      }
      pdfCreation(response.data.data, merchantId);
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Unable to fetch Bounce memo information at this moment, please try again later.',
      });
    } finally {
      setSubmitButtonLoading(false);
    }
  };

  return (
    <div>
      <div className="partner-submerchant-modal">
        <ModalHeader title="Download Failed Transaction Memo" onCloseClick={closeModal} />
        <div className="modal-body">
          <Alert
            testID="bounce-memo-modal-alert"
            marginTop="spacing.4"
            isDismissible={false}
            description={TITLE}
            isFullWidth={false}
            color="negative"
          />
        </div>
        <ModalFooter>
          <Box display="flex" justifyContent="flex-end">
            <Button
              testID="bounce-memo-modal-cancel"
              type="button"
              isDisabled={isSubmitButtonLoading}
              variant="tertiary"
              marginX="spacing.5"
              onClick={closeModal}
            >
              Cancel
            </Button>
            <Button
              testID="bounce-memo-download-btn"
              type="button"
              isLoading={isSubmitButtonLoading}
              variant="primary"
              onClick={FetchBouncememo}
            >
              Download
            </Button>
          </Box>
        </ModalFooter>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(BounceMemoPopup);
