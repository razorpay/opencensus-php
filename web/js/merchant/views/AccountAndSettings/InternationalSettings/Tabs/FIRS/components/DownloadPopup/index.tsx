import React, { useEffect } from 'react';
import { Modal, ModalHeader, ModalBody, Box, Spinner } from '@razorpay/blade/components';

import FirsFiles from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup/FirsFiles';
import YearMonthDropdown from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup/YearMonthDropdown';
import {
  trackDownloadPopupOpened,
  trackDownloadPopupClosed,
} from 'merchant/views/AccountAndSettings/InternationalSettings/analytics';
import {
  PopupTitle,
  PopupType,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import useFirsContext from 'merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext';

const DownloadPopup = (): React.ReactElement => {
  const { popupData, setPopupData, isLoading } = useFirsContext();
  const { month, year, isOpen, type } = popupData;

  const isPopupOpen = isOpen && type === PopupType.DOWNLOAD_FIRS;

  const onDismiss = () => {
    trackDownloadPopupClosed(month, year);
    setPopupData((prev) => ({
      ...prev,
      isOpen: false,
    }));
  };

  useEffect(() => {
    if (isPopupOpen) trackDownloadPopupOpened(month, year);
  }, [isPopupOpen]);

  return (
    <Modal isOpen={isPopupOpen} onDismiss={onDismiss} size="small">
      <ModalHeader title={PopupTitle[PopupType.DOWNLOAD_FIRS]} />
      <ModalBody>
        <Box>
          <YearMonthDropdown />
        </Box>
        <Box display="flex" minHeight={{ base: '300px' }}>
          {isPopupOpen && isLoading ? (
            <Box display="flex" alignItems="center" justifyContent="center" flex="1">
              <Spinner accessibilityLabel="firs-modal-spinner" size="xlarge" />
            </Box>
          ) : (
            <FirsFiles />
          )}
        </Box>
      </ModalBody>
    </Modal>
  );
};

export default DownloadPopup;
