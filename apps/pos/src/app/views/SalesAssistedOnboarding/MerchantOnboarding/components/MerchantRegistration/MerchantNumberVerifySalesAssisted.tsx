import React from 'react';
import MerchantNumberVerify from './MerchantNumberVerify';
import { Box } from '@razorpay/blade/components';
import useMerchantRegistration from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useMerchantRegistration';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import errorService from '@razorpay/universe-cli/errorService';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

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
    <ErrorBoundary
      sentryHub={sentryHub?.sentryHub}
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.MERCHANT_REGISTRATION }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
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
    </ErrorBoundary>
  );
};

export default MerchantNumberVerifySalesAssisted;
