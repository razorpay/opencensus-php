import React, { useState } from 'react';
import { Box, Link, OTPInput, Text } from '@razorpay/blade/components';

import { OtpInputProps } from './types';

const OtpInput = ({ otp, setOtp, onResendOtp }: OtpInputProps): JSX.Element => {
  const [isLoading, setIsLoading] = useState(false);

  const onChange = ({ value }: { value?: string }) => {
    if (value !== undefined) setOtp(value);
  };

  const onResend = async () => {
    setIsLoading(true);
    await onResendOtp();
    setIsLoading(false);
  };

  return (
    <Box display="flex" flexDirection="column">
      <OTPInput
        label="Enter OTP"
        labelPosition="top"
        helpText="OTP sent to registered number on GSTIN portal"
        marginBottom="spacing.5"
        value={otp}
        onChange={onChange}
      />
      <Box display="flex" flexDirection="row">
        <Text size="small" marginRight="spacing.2">
          Didn’t receive OTP?
        </Text>
        <Link size="small" variant="button" onClick={onResend} isDisabled={isLoading}>
          Resend
        </Link>
      </Box>
    </Box>
  );
};

export default OtpInput;
