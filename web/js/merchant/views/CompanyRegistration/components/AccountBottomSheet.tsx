import React, { useEffect } from 'react';
import {
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
} from '@razorpay/blade/components';
import {
  MULTI_ACCOUNT_TITLE,
  MULTI_ACCOUNT_SUBTITLE,
} from 'merchant/views/CompanyRegistration/constant';
import { MultiAccountBody, MultiAccountFooter } from './MultiAccountBodyFooter';
import { trackEventOnCreateAccountPageView } from '../analytics';
import { AccountT } from '../types';

export interface AccountBottomSheetType {
  isOpen: boolean;
  closeModal: () => void;
  selected: string;
  setSelected: (value: AccountT) => void;
  isLoading: boolean;
  handleUserAction: () => void;
}

const AccountBottomSheet = ({
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
    <BottomSheet isOpen={isOpen} onDismiss={closeModal} snapPoints={[1, 1, 1]}>
      <BottomSheetHeader title={MULTI_ACCOUNT_TITLE} subtitle={MULTI_ACCOUNT_SUBTITLE} />
      <BottomSheetBody>
        <MultiAccountBody selected={selected} setSelected={setSelected} />
      </BottomSheetBody>
      <BottomSheetFooter>
        <MultiAccountFooter isLoading={isLoading} handleUserAction={handleUserAction} />
      </BottomSheetFooter>
    </BottomSheet>
  );
};
export default AccountBottomSheet;
