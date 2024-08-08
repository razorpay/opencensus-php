import React, { useState } from 'react';
import { Box, Button, Link, OTPInput, Text, TextInput } from '@razorpay/blade/components';
import KYCRedirectionLoader from './KYCRedirectionLoader';

interface MerchantNumberVerifyProps {
  isOTPSent: boolean;
  error?: string | null;
  OTPToken: string | undefined;
  isOTPLoading: boolean;
  isVerifyOTPLoading: boolean;
  isSwitchMerchantLoading: boolean;
  handleOnPhoneNumberConfirm: (phoneNumber: string) => void;
  handleOnOTPSubmit: (payload) => void;
  onPhoneNumberChange?: (value: string) => void;
  onOTPChange?: (otp: string) => void;
}

const MerchantNumberVerify = ({
  error,
  isOTPSent,
  isOTPLoading,
  isSwitchMerchantLoading,
  isVerifyOTPLoading,
  handleOnOTPSubmit,
  handleOnPhoneNumberConfirm,
  onPhoneNumberChange,
  onOTPChange,
  OTPToken,
}: MerchantNumberVerifyProps): JSX.Element => {
  const [phoneNumber, setPhoneNumber] = useState<string>('');
  const [OTP, setOTP] = useState<string>('');

  const handleOnPhoneNumberChange = (value: string): void => {
    if (isNaN(Number(value))) return;
    setPhoneNumber(value.trim());
    onPhoneNumberChange?.(value);
  };

  const handleOnClearButtonClick = (): void => {
    setPhoneNumber('');
  };

  const handleOnSubmitPhoneNumber = (): void => {
    handleOnPhoneNumberConfirm?.(phoneNumber);
  };

  const isSubmitDisabled = phoneNumber.length !== 10 || !!error;

  const handleOnOTPChange = (value: string): void => {
    setOTP(value);
    onOTPChange?.(value);
  };

  const handleResendOTPClick = (): void => {
    setOTP('');
    handleOnPhoneNumberConfirm(phoneNumber);
  };

  const handleOnOTPSubmitClick = (): void => {
    const payload = {
      otp: OTP,
      token: OTPToken,
      contactMobile: phoneNumber,
    };
    handleOnOTPSubmit(payload);
  };

  return (
    <Box padding="spacing.5">
      {isOTPSent ? (
        <React.Fragment>
          <OTPInput
            name="register-merchant-otp"
            value={OTP}
            // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
            label={`Enter OTP sent to +91${phoneNumber}`}
            onChange={({ value }) => handleOnOTPChange(value ?? '')}
            validationState={!!error ? 'error' : 'none'}
            errorText={error ?? ''}
            autoFocus
          />
          <Box display="flex" marginTop="spacing.4" marginBottom="spacing.8">
            <Text color="interactive.text.gray.subtle" size="small" marginRight="spacing.2">
              {"Didn't receive OTP?"}
            </Text>
            <Link
              variant="button"
              size="small"
              onClick={handleResendOTPClick}
              isDisabled={isVerifyOTPLoading || isSwitchMerchantLoading}
            >
              Resend OTP
            </Link>
          </Box>
          <Button
            size="large"
            onClick={handleOnOTPSubmitClick}
            isDisabled={!!error}
            isLoading={isVerifyOTPLoading || isSwitchMerchantLoading}
            isFullWidth
          >
            Submit OTP
          </Button>
        </React.Fragment>
      ) : (
        <React.Fragment>
          <TextInput
            value={phoneNumber}
            label="Let's get merchant's mobile number verified"
            onChange={({ value }) => handleOnPhoneNumberChange(value ?? '')}
            validationState={!!error ? 'error' : 'none'}
            errorText={error ?? ''}
            onClearButtonClick={handleOnClearButtonClick}
            marginBottom="spacing.8"
            testID="phone-number-input"
          />
          <Button
            size="large"
            onClick={handleOnSubmitPhoneNumber}
            isDisabled={isSubmitDisabled}
            isLoading={isOTPLoading}
            isFullWidth
          >
            Verify
          </Button>
        </React.Fragment>
      )}
      <KYCRedirectionLoader isOpen={isSwitchMerchantLoading} />
    </Box>
  );
};

export default MerchantNumberVerify;
