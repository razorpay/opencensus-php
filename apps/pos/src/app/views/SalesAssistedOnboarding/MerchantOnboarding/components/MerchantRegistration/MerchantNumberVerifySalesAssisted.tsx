import React from 'react';
import MerchantNumberVerify from './MerchantNumberVerify';
import useMerchantRegistration from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useMerchantRegistration';

const MerchantNumberVerifySalesAssisted = (): JSX.Element => {
  const {
    error,
    isOTPSent,
    merchantOTPSendData,
    isOTPLoading,
    isVerifyOTPLoading,
    isSwitchMerchantLoading,
    handleOnPhoneNumberConfirm,
    handleOnOTPSubmit,
    triggerRemoveError,
  } = useMerchantRegistration();

  return (
    <MerchantNumberVerify
      error={error}
      isOTPSent={isOTPSent}
      OTPToken={merchantOTPSendData?.data?.token}
      isOTPLoading={isOTPLoading}
      isVerifyOTPLoading={isVerifyOTPLoading}
      isSwitchMerchantLoading={isSwitchMerchantLoading}
      handleOnPhoneNumberConfirm={handleOnPhoneNumberConfirm}
      handleOnOTPSubmit={handleOnOTPSubmit}
      onOTPChange={triggerRemoveError}
      onPhoneNumberChange={triggerRemoveError}
    />
  );
};

export default MerchantNumberVerifySalesAssisted;
