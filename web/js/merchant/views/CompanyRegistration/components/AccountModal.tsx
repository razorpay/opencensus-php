import React, { useEffect } from 'react';
import { Modal, ModalBody, ModalHeader, ModalFooter } from '@razorpay/blade/components';
import {
  MULTI_ACCOUNT_TITLE,
  MULTI_ACCOUNT_SUBTITLE,
} from 'merchant/views/CompanyRegistration/constant';
import { MultiAccountBody, MultiAccountFooter } from './MultiAccountBodyFooter';
import { trackEventOnCreateAccountPageView } from '../analytics';
import { AccountBottomSheetType } from './AccountBottomSheet';
const AccountModal = ({
  isOpen,
  closeModal,
  selected,
  setSelected,
  isLoading,
  handleUserAction,
}: AccountBottomSheetType) => {
  useEffect(() => {
    trackEventOnCreateAccountPageView();
  }, []);
  return (
    <Modal isOpen={isOpen} onDismiss={closeModal} size="small">
      <ModalHeader title={MULTI_ACCOUNT_TITLE} subtitle={MULTI_ACCOUNT_SUBTITLE} />
      <ModalBody>
        <MultiAccountBody selected={selected} setSelected={setSelected} />
      </ModalBody>
      <ModalFooter>
        <MultiAccountFooter isLoading={isLoading} handleUserAction={handleUserAction} />
      </ModalFooter>
    </Modal>
  );
};

export default AccountModal;
