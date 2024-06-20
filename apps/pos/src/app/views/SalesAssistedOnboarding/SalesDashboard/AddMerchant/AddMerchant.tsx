import React from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  PlusIcon,
  PhoneNumberInput,
  ModalFooter,
  ArrowRightIcon,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetFooter,
  BottomSheetBody,
  OTPInput,
  Text,
  Link,
  Alert,
  Spinner,
} from '@razorpay/blade/components';
import { useMerchantRegistration } from './useMerchantRegistration';

import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';

const AddMerchant = (): JSX.Element => {
  const {
    state,
    isOTPLoading,
    isVerifyOTPLoading,
    handleAddMerchantClick,
    handleAddMerchantDismiss,
    handleOnClearButtonClick,
    handleOnOTPChange,
    handleOnPhoneNumberChange,
    handleOnPhoneNumberConfirm,
    handleOnOTPSubmit,
    handleOnBackClick,
    handleResendOTPClick,
  } = useMerchantRegistration();
  const {
    isAddMerchantModalOpen,
    merchantToken,
    phoneNumber,
    merchantOTP,
    error,
    registerationError,
    isRedirectingToEasyDashboard,
  } = state;
  const { isMobile } = useScreen();
  const isOTPGenerated = !!merchantToken;

  const renderBody = (): JSX.Element => {
    if (registerationError) {
      return (
        <Alert
          color="negative"
          title={registerationError.title}
          description={registerationError.description}
          isDismissible={false}
          isFullWidth
        />
      );
    }

    if (isRedirectingToEasyDashboard) {
      return (
        <Box display="flex" flexDirection="column" alignItems="center" justifyContent="center">
          <Spinner size="medium" accessibilityLabel="easy-dashboard-redirection-spinner" />
          <Text size="medium" marginTop="spacing.3">
            Redirecting you to the onboarding journey....
          </Text>
        </Box>
      );
    }

    return (
      <Box minHeight="75px">
        {isOTPGenerated ? (
          <React.Fragment>
            <OTPInput
              name="register-merchant-otp"
              value={merchantOTP}
              label={`Enter OTP sent to ${phoneNumber?.dialCode}${phoneNumber?.value}`}
              onChange={({ value }) => handleOnOTPChange(value ?? '')}
              validationState={error ? 'error' : 'none'}
              errorText={error}
              autoFocus
            />
            <Box display="flex" marginTop="spacing.4">
              <Text color="interactive.text.gray.subtle" size="small" marginRight="spacing.2">
                {"Didn't receive OTP?"}
              </Text>
              <Link variant="button" size="small" onClick={handleResendOTPClick}>
                Resend OTP
              </Link>
            </Box>
          </React.Fragment>
        ) : (
          <PhoneNumberInput
            value={phoneNumber?.value ?? ''}
            showCountrySelector={false}
            label="Let's get merchant's mobile number verified"
            onChange={({ country, dialCode, value }) =>
              handleOnPhoneNumberChange({ country, dialCode, value })
            }
            validationState={error ? 'error' : 'none'}
            errorText={error}
            onClearButtonClick={handleOnClearButtonClick}
          />
        )}
      </Box>
    );
  };

  const renderSubmitOTP = (): JSX.Element | null => {
    if (registerationError || isRedirectingToEasyDashboard) return null;
    return (
      <Box display="flex" justifyContent="flex-end">
        {isOTPGenerated ? (
          <React.Fragment>
            <Button
              onClick={handleOnBackClick}
              variant="tertiary"
              marginRight="spacing.3"
              isDisabled={isVerifyOTPLoading}
              isFullWidth={isMobile}
            >
              Back
            </Button>
            <Button
              onClick={handleOnOTPSubmit}
              isDisabled={merchantOTP.length < 6 || !!error}
              isLoading={isVerifyOTPLoading || isOTPLoading}
              isFullWidth={isMobile}
            >
              Validate OTP
            </Button>
          </React.Fragment>
        ) : (
          <Button
            onClick={handleOnPhoneNumberConfirm}
            icon={ArrowRightIcon}
            iconPosition="right"
            isFullWidth={isMobile as boolean}
            isLoading={isOTPLoading}
            isDisabled={!!error}
          >
            Verify & Send OTP
          </Button>
        )}
      </Box>
    );
  };

  const renderAddMerchantButton = (): JSX.Element => (
    <Button icon={PlusIcon} size="large" onClick={handleAddMerchantClick} isFullWidth>
      Add Merchant
    </Button>
  );

  return (
    <React.Fragment>
      {isMobile ? (
        <Box
          display="flex"
          justifyContent="center"
          position="fixed"
          bottom="0px"
          padding="spacing.4"
          backgroundColor="surface.background.gray.intense"
          left="0px"
          right="0px"
          zIndex="1"
        >
          {renderAddMerchantButton()}
        </Box>
      ) : (
        renderAddMerchantButton()
      )}
      {isMobile ? (
        <BottomSheet isOpen={isAddMerchantModalOpen} onDismiss={handleAddMerchantDismiss}>
          <BottomSheetHeader title="Create a New Merchant" />
          <BottomSheetBody>{renderBody()}</BottomSheetBody>
          <BottomSheetFooter>{renderSubmitOTP()}</BottomSheetFooter>
        </BottomSheet>
      ) : (
        <Modal isOpen={isAddMerchantModalOpen} onDismiss={handleAddMerchantDismiss}>
          <ModalHeader title="Create a New Merchant" />
          <ModalBody>{renderBody()}</ModalBody>
          <ModalFooter>{renderSubmitOTP()}</ModalFooter>
        </Modal>
      )}
    </React.Fragment>
  );
};

export default AddMerchant;
