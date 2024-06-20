import { useCallback, useReducer } from 'react';
import { useMutation } from '@tanstack/react-query';
import { CountryCodeType, isValidPhoneNumber } from '@razorpay/i18nify-js';
import { useToast } from '@razorpay/blade/components';

import { registerMerchant, verifyMerchantOTP } from 'apps/pos/src/app/apis/SalesAssistedOnboarding';
import { MERCHANT_REGISTRATION_ERRORS } from 'apps/pos/src/app/constants/SalesAssistedOnboarding';
import { APIResponse } from 'apps/pos/src/app/typings/common';
import {
  AddMerchantErrorResponse,
  MerchantOTPVerifyAPIResponse,
  MerchantOTPVerifyErrorResponse,
  MerchantRegisterApiResponse,
  MerchantRegistrationError,
  MerchantRegistrationPhoneNumber,
} from 'apps/pos/src/app/typings/SalesAssistedOnboarding';
import useEnv from 'apps/pos/src/app/utils/hooks/useEnv';
import useMerchantSwitch from 'apps/pos/src/app/utils/hooks/useMerchantSwitch';
import redirectToEasyOnboarding from 'apps/pos/src/app/utils/redirectToEasyOnboarding';

interface AddMerchantStateTypes {
  isAddMerchantModalOpen: boolean;
  merchantToken: string | null;
  phoneNumber: MerchantRegistrationPhoneNumber | null;
  merchantOTP: string;
  error: string;
  registerationError: MerchantRegistrationError | null;
  isOTPLoading: boolean;
  isRedirectingToEasyDashboard: boolean;
}

interface Action {
  type:
    | 'SET_IS_ADD_MERCHANT_MODAL_OPEN'
    | 'SET_MERCHANT_TOKEN'
    | 'SET_PHONE_NUMBER'
    | 'SET_MERCHANT_OTP'
    | 'SET_ERROR'
    | 'SET_REGISTERATION_ERROR'
    | 'SET_IS_OTP_LOADING'
    | 'RESET'
    | 'RESTART_OTP_FILL'
    | 'NAVIGATING_TO_EASY_DASHBOARD'
    | 'RESEND_MERCHANT_OTP';
  payload: boolean | MerchantRegistrationPhoneNumber | string | MerchantRegistrationError | null;
}

interface UseMerchantRegistration {
  state: AddMerchantStateTypes;
  isOTPLoading: boolean;
  isVerifyOTPLoading: boolean;
  isRedirectingToEasyDashboard: boolean;
  handleAddMerchantClick: () => void;
  handleAddMerchantDismiss: () => void;
  handleOnPhoneNumberChange: (phoneNumber: MerchantRegistrationPhoneNumber) => void;
  handleOnPhoneNumberConfirm: () => void;
  handleOnClearButtonClick: () => void;
  handleOnOTPChange: (otp: string) => void;
  handleOnOTPSubmit: () => void;
  handleOnBackClick: () => void;
  handleResendOTPClick: () => void;
}

export const initalState: AddMerchantStateTypes = {
  isAddMerchantModalOpen: false,
  merchantToken: null,
  phoneNumber: null,
  merchantOTP: '',
  error: '',
  registerationError: null,
  isOTPLoading: false,
  isRedirectingToEasyDashboard: false,
};

export const reducer = (state: AddMerchantStateTypes, action: Action) => {
  switch (action.type) {
    case 'SET_IS_ADD_MERCHANT_MODAL_OPEN':
      return { ...state, isAddMerchantModalOpen: action.payload as boolean };
    case 'SET_MERCHANT_TOKEN':
      return { ...state, merchantToken: action.payload as string };
    case 'SET_PHONE_NUMBER':
      return {
        ...state,
        phoneNumber: action.payload as MerchantRegistrationPhoneNumber,
        error: '',
      };
    case 'SET_MERCHANT_OTP':
      return { ...state, merchantOTP: action.payload as string, error: '' };
    case 'SET_ERROR':
      return { ...state, error: action.payload as string };
    case 'SET_REGISTERATION_ERROR':
      return { ...state, registerationError: action.payload as MerchantRegistrationError };
    case 'SET_IS_OTP_LOADING':
      return { ...state, isOTPLoading: action.payload as boolean };
    case 'RESTART_OTP_FILL':
      return { ...state, merchantOTP: '', merchantToken: '', error: '' };
    case 'NAVIGATING_TO_EASY_DASHBOARD':
      return { ...state, isRedirectingToEasyDashboard: action.payload as boolean };
    case 'RESEND_MERCHANT_OTP':
      return { ...state, error: '', merchantOTP: '', merchantToken: '' };
    case 'RESET':
      return initalState;
    default:
      return state;
  }
};

export const useMerchantRegistration = (): UseMerchantRegistration => {
  const [state, dispatch] = useReducer(reducer, initalState);
  const { phoneNumber, merchantOTP, merchantToken, isRedirectingToEasyDashboard } = state;
  const { isProduction } = useEnv();
  const toast = useToast();

  const { handleSwitchMerchant } = useMerchantSwitch({
    onSuccess: () => {
      redirectToEasyOnboarding();
    },
    onError: () => {
      toast.show({ content: 'Something went wrong!', color: 'negative' });
    },
  });

  const { mutate: sendMobileOtp, isLoading: isOTPLoading } = useMutation<
    MerchantRegisterApiResponse,
    APIResponse<null, AddMerchantErrorResponse>
  >({
    mutationFn: async () => {
      const response = await registerMerchant({
        contactMobile: phoneNumber?.value as string,
        ...(!isProduction ? { mockSend: true } : {}),
      });
      return response;
    },
    onSuccess: (response) => {
      dispatch({ type: 'SET_MERCHANT_TOKEN', payload: response?.data?.token ?? '' });
    },
    onError: (data) => {
      const error = data?.errors?.[0];
      const errorCode = error?.internal_error_code;
      const errorObj = MERCHANT_REGISTRATION_ERRORS?.[errorCode as string];
      if (!MERCHANT_REGISTRATION_ERRORS?.[errorCode as string]) {
        dispatch({
          type: 'SET_ERROR',
          payload: error?.description
            ? error.description
            : MERCHANT_REGISTRATION_ERRORS.GENERIC_ERROR.description,
        });
      }
      dispatch({
        type: 'SET_REGISTERATION_ERROR',
        payload: errorObj,
      });
    },
  });

  const { mutate: initiateVerifyOTP, isLoading: isVerifyOTPLoading } = useMutation<
    MerchantOTPVerifyAPIResponse,
    APIResponse<null, MerchantOTPVerifyErrorResponse>
  >({
    mutationFn: async () => {
      const response = await verifyMerchantOTP({
        otp: merchantOTP,
        token: merchantToken as string,
        contactMobile: phoneNumber?.value as string,
        ...(!isProduction ? { mockSend: true } : {}),
      });
      return response;
    },
    onSuccess: (response) => {
      if (response?.data?.merchants?.[0]?.id) {
        const { merchants } = response.data;
        dispatch({ type: 'NAVIGATING_TO_EASY_DASHBOARD', payload: true });
        handleSwitchMerchant(merchants[0].id);
      }
    },
    onError: (data) => {
      const error = data?.errors?.[0];
      dispatch({
        type: 'SET_ERROR',
        payload: error?.description
          ? error.description
          : MERCHANT_REGISTRATION_ERRORS.GENERIC_ERROR.description,
      });
    },
  });

  const handleAddMerchantClick = useCallback((): void => {
    dispatch({ type: 'SET_IS_ADD_MERCHANT_MODAL_OPEN', payload: true });
  }, []);

  const handleAddMerchantDismiss = useCallback((): void => {
    if (isRedirectingToEasyDashboard) return;
    dispatch({ type: 'RESET', payload: null });
  }, [isRedirectingToEasyDashboard]);

  const handleOnPhoneNumberChange = (phoneNumber: MerchantRegistrationPhoneNumber): void => {
    if (isNaN(Number(phoneNumber.value))) return;

    dispatch({ type: 'SET_PHONE_NUMBER', payload: phoneNumber });
  };

  const handleOnPhoneNumberConfirm = (): void => {
    if (!phoneNumber?.value) return;
    const isValid = isValidPhoneNumber(
      phoneNumber?.value as string,
      phoneNumber.country as CountryCodeType,
    );

    if (!isValid) {
      dispatch({
        type: 'SET_ERROR',
        payload: MERCHANT_REGISTRATION_ERRORS.INVALID_PHONE_NUMBER.description,
      });
      return;
    }
    sendMobileOtp();
  };

  const handleOnClearButtonClick = useCallback((): void => {
    dispatch({ type: 'SET_PHONE_NUMBER', payload: '' });
  }, []);

  const handleOnOTPChange = (otp: string): void => {
    dispatch({ type: 'SET_MERCHANT_OTP', payload: otp });
  };

  const handleOnOTPSubmit = (): void => {
    initiateVerifyOTP();
  };

  const handleOnBackClick = (): void => {
    dispatch({ type: 'RESTART_OTP_FILL', payload: '' });
  };

  const handleResendOTPClick = (): void => {
    dispatch({ type: 'RESEND_MERCHANT_OTP', payload: '' });
    sendMobileOtp();
  };

  return {
    state,
    isOTPLoading,
    isVerifyOTPLoading,
    isRedirectingToEasyDashboard,
    handleAddMerchantClick,
    handleAddMerchantDismiss,
    handleOnPhoneNumberChange,
    handleOnPhoneNumberConfirm,
    handleOnClearButtonClick,
    handleOnOTPChange,
    handleOnOTPSubmit,
    handleOnBackClick,
    handleResendOTPClick,
  };
};
