import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { isValidPhoneNumber } from '@razorpay/i18nify-js';
import { registerMerchant, verifyMerchantOTP } from 'apps/pos/src/app/apis/SalesAssistedOnboarding';
import { APIResponse } from 'apps/pos/src/app/types/common';
import {
  MerchantRegisterApiResponse,
  AddMerchantErrorResponse,
  MerchantOTPVerifyAPIResponse,
  MerchantOTPVerifyErrorResponse,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import useEnv from 'apps/pos/src/app/utils/hooks/useEnv';
import useMerchantSwitch from 'apps/pos/src/app/utils/hooks/useMerchantSwitch';
import redirectToEasyOnboarding from 'apps/pos/src/app/utils/redirectToEasyOnboarding';
import { MERCHANT_REGISTRATION_ERRORS } from 'apps/pos/src/app/constants/SalesAssistedOnboarding';

interface UseMerchantRegistration {
  isOTPSent: boolean;
  error: string | null;
  merchantOTPSendData: MerchantRegisterApiResponse | undefined;
  isOTPLoading: boolean;
  isVerifyOTPLoading: boolean;
  isSwitchMerchantLoading: boolean;
  handleOnPhoneNumberConfirm: (phoneNumber: string) => void;
  handleOnOTPSubmit: (props: OTPsubmitprops) => void;
  handleSwitchMerchant: (merchantId: string) => void;
  triggerRemoveError: () => void;
}

interface OTPsubmitprops {
  otp: string;
  token: string;
  contactMobile: string;
}

const useMerchantRegistration = (): UseMerchantRegistration => {
  const { isProduction } = useEnv();
  const [isOTPSent, setIsOTPSent] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  const triggerRemoveError = () => {
    setError(null);
  };

  const { isLoading: isSwitchMerchantLoading, handleSwitchMerchant } = useMerchantSwitch({
    onSuccess: () => {
      redirectToEasyOnboarding();
    },
    onError: () => {
      setError('Error while switching merchant');
    },
  });

  const {
    data: merchantOTPSendData,
    mutate: sendMobileOtp,
    isLoading: isOTPLoading,
  } = useMutation<MerchantRegisterApiResponse, APIResponse<null, AddMerchantErrorResponse>, string>(
    {
      mutationFn: async (phoneNumber: string) => {
        triggerRemoveError();
        const response = await registerMerchant({
          contactMobile: phoneNumber,
          ...(!isProduction ? { mockSend: true } : {}),
        });
        return response;
      },
      onSuccess: () => {
        setIsOTPSent(true);
      },
      onError: (data) => {
        const error = data?.errors?.[0];
        const errorCode = error?.internal_error_code;
        const errorObj = MERCHANT_REGISTRATION_ERRORS?.[errorCode as string]?.description as string;
        const genericError = MERCHANT_REGISTRATION_ERRORS.GENERIC_ERROR?.description as string;
        setError(errorObj ?? genericError);
      },
    },
  );

  const { mutate: initiateVerifyOTP, isLoading: isVerifyOTPLoading } = useMutation<
    MerchantOTPVerifyAPIResponse,
    APIResponse<null, MerchantOTPVerifyErrorResponse>,
    OTPsubmitprops
  >({
    mutationFn: async (variables) => {
      triggerRemoveError();
      const response = await verifyMerchantOTP({
        ...variables,
        ...(!isProduction ? { mockSend: true } : {}),
      });
      return response;
    },
    onSuccess: (response) => {
      handleSwitchMerchant(response?.data?.merchants?.[0].id as string);
    },
    onError: (data) => {
      const error = data?.errors?.[0];
      setError(error?.description as string);
    },
  });

  const handleOnPhoneNumberConfirm = (phoneNumber: string): void => {
    if (!phoneNumber) return;
    // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
    const isValid = isValidPhoneNumber(phoneNumber, 'IN');

    if (isValid) sendMobileOtp(phoneNumber);
    else setError('Invalid Phone Number');
  };

  const handleOnOTPSubmit = (variables: OTPsubmitprops): void => {
    initiateVerifyOTP(variables);
  };

  return {
    error,
    isOTPSent,
    merchantOTPSendData,
    isOTPLoading,
    isVerifyOTPLoading,
    isSwitchMerchantLoading,
    triggerRemoveError,
    handleOnPhoneNumberConfirm,
    handleOnOTPSubmit,
    handleSwitchMerchant,
  };
};

export default useMerchantRegistration;
