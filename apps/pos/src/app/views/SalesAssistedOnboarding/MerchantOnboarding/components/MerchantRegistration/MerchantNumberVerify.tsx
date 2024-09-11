import React, { useEffect, useState } from 'react';
import { Box, Button, Link, OTPInput, TextInput, Text } from '@razorpay/blade/components';
import KYCRedirectionLoader from './KYCRedirectionLoader';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

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
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_SIGNUP,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CTR,
        section: 'merchant_signup',
        subSection: 'merchant_number_entry',
        label: 'Verify',
      },
    });
    handleOnPhoneNumberConfirm?.(phoneNumber);
  };

  const isSubmitDisabled = phoneNumber.length !== 10 || !!error;

  const handleOnOTPChange = (value: string): void => {
    setOTP(value);
    onOTPChange?.(value);
  };

  const handleResendOTPClick = (): void => {
    setOTP('');
    console.log('Resend OTP');
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_SIGNUP,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.MOBILE_OTP_VERIFICATION,
        section: 'merchant_signup',
        subSection: 'mobile_otp_verification',
        label: 'Resend OTP',
      },
    });
    handleOnPhoneNumberConfirm(phoneNumber);
  };

  const handleOnOTPSubmitClick = (): void => {
    const payload = {
      otp: OTP,
      token: OTPToken,
      contactMobile: phoneNumber,
    };

    console.log('Submit OTP');
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_SIGNUP,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.MOBILE_OTP_VERIFICATION,
        section: 'merchant_signup',
        subSection: 'mobile_otp_verification',
        label: 'Submit OTP',
      },
    });

    handleOnOTPSubmit(payload);
  };

  useEffect(() => {
    if (isOTPSent) {
      // Track OTP verification UI //
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_PAGE,
        action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
        properties: {
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_SIGNUP,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.MOBILE_NUMBER_ENTRY,
          section: 'merchant_signup',
          subSection: 'mobile_number_entry',
          formName: 'merchant_details_signup',
        },
      });
    } else {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_PAGE,
        action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
        properties: {
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_SIGNUP,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CONTACT_DETAILS_VERIFICATION,
          section: 'merchant_signup',
          subSection: 'contact_details_verification',
          formName: 'merchant_details_signup',
        },
      });
    }
  }, [isOTPSent]);

  const onOTPFilled = (): void => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_FIELD_FILL,
      action: analyticsTypes.ANALYTICS_ACTIONS.INITIATED,
      properties: {
        formName: 'merchant_details_signup',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_SIGNUP,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.MOBILE_NUMBER_ENTRY,
        fieldType: analyticsTypes.FIELD_TYPES.TEXTBOX,
        fieldName: 'mobile_otp',
      },
    });
  };

  const onPhoneNumberFocused = (): void => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_FIELD_FILL,
      action: analyticsTypes.ANALYTICS_ACTIONS.INITIATED,
      properties: {
        formName: 'merchant_details_signup',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_SIGNUP,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.MOBILE_NUMBER_ENTRY,
        fieldType: analyticsTypes.FIELD_TYPES.TEXTBOX,
        fieldName: 'mobile_number',
      },
    });
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
            onOTPFilled={onOTPFilled}
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
            onFocus={onPhoneNumberFocused}
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
