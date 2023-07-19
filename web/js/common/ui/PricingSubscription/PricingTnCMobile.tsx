import React from 'react';
import { BottomSheet, BottomSheetHeader, BottomSheetBody } from '@razorpay/blade/components';
import { TncModal } from './PricingSubscriptionProps.type';
import { TncContentMemo } from './PricingTnC';

const TncMobile = ({ isOpenTncModal, toggleTncModal }: TncModal): JSX.Element => {
  return (
    <BottomSheet isOpen={isOpenTncModal} onDismiss={toggleTncModal} snapPoints={[1, 1, 1]}>
      <BottomSheetHeader title="" />
      <BottomSheetBody>
        <TncContentMemo />
      </BottomSheetBody>
    </BottomSheet>
  );
};

export default TncMobile;
