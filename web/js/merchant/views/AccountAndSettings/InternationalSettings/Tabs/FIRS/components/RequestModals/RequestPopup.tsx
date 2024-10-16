import React, { useEffect } from 'react';
import { Modal, ModalHeader, ModalBody, Box, Spinner, Text } from '@razorpay/blade/components';

import { trackRequestFirsResponse } from 'merchant/views/AccountAndSettings/InternationalSettings/analytics';
import {
  PopupTitle,
  PopupType,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import useFirsContext from 'merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext';
import { requestInternalFirs } from 'merchant/views/AccountAndSettings/InternationalSettings/services';
import { updateFirsDataObject } from 'merchant/views/AccountAndSettings/InternationalSettings/utils';

const RequestPopup = (): React.ReactElement => {
  const { popupData, setPopupData, setError, setFirsData } = useFirsContext();
  const { isOpen, type, month, year } = popupData;

  const isPopupOpen = isOpen && type === PopupType.INTERNAL_FIRS;

  const goBackToDownload = () => {
    setPopupData((prev) => ({
      ...prev,
      type: PopupType.DOWNLOAD_FIRS,
    }));
  };

  const onSuccess = (data) => {
    trackRequestFirsResponse(month, year, 'Success');
    setFirsData((prev) => updateFirsDataObject(month, year, prev, data));
    setPopupData((prev) => ({
      ...prev,
      type: PopupType.SUCCESS_MODAL,
    }));
  };

  const initRequest = async () => {
    try {
      const response = await requestInternalFirs(month, year);
      onSuccess(response);
    } catch (error) {
      if (typeof error === 'object') setError(error?.toString() ?? '');
      trackRequestFirsResponse(month, year, 'Error');
      goBackToDownload();
    }
  };

  const onDismiss = () => {
    setPopupData((prev) => ({
      ...prev,
      isOpen: false,
    }));
  };

  useEffect(() => {
    if (isOpen && type === PopupType.INTERNAL_FIRS) {
      initRequest();
    }
  }, [isOpen, type]);

  return (
    <Modal isOpen={isPopupOpen} onDismiss={onDismiss} size="small">
      <ModalHeader title={PopupTitle[PopupType.INTERNAL_FIRS]} />
      <ModalBody>
        <Box display="flex" flexDirection="column" alignItems="center">
          <Box
            display="flex"
            flexDirection="row"
            alignItems={{ base: 'center' }}
            marginBottom="spacing.7"
          >
            <Spinner accessibilityLabel="Request loader" marginRight="spacing.3" />
            <Text size="large">Requesting for Razorpay statement</Text>
          </Box>
          <Text size="medium" textAlign="center">
            Please wait for a few seconds. Your request is being processed...
          </Text>
        </Box>
      </ModalBody>
    </Modal>
  );
};

export default RequestPopup;
