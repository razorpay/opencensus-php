import { useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';

import { track } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/analytics';
import { useVerificationStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/verificationStates';
import {
  ACCOUNTS_STATUS,
  VERIFICATION_STATUS_ACTIVATION_MODAL_STEP,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/verificationStates/constants';
import { VerificationStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/verificationStates/types';
import { IntlBankTransferProps } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/types';

import {
  getInternationalVirtualAccounts,
  postInternationalVirtualAccountActivate,
  postInternationalVirtualAccountToggle,
} from './helpers';

export const useIntlBankTransfer = ({
  user,
  showNotification,
}: Omit<IntlBankTransferProps, 'item'>) => {
  const [isActivationModalOpen, setActivationModalOpen] = useState(false);
  const [currencyForActivation, setCurrencyForActivation] = useState('');
  const [activationModalCurrentStep, setActivationModalCurrentStep] = useState<1 | 2 | 3 | 4>(1);

  // Get promoter pan name from user reducer
  const hasPromoterPanName = !!user?.promoter_pan_name;

  const {
    data: virtualAccountData,
    isLoading: isVirtualAccountLoading,
    refetch: refetchVirtualAccounts,
  } = useQuery({
    queryKey: ['international_virtual_accounts'],
    queryFn: getInternationalVirtualAccounts,
    refetchOnWindowFocus: false,
  });

  const { mutate: toggleActivation, isLoading: isToggleLoading } = useMutation({
    mutationFn: (action: 'activate' | 'deactivate') =>
      postInternationalVirtualAccountToggle(action),
    onSuccess: () => {
      refetchVirtualAccounts();
    },
  });

  const { mutate: activateAllAccounts, isLoading: isActivating } = useMutation({
    mutationFn: (currency?: string) =>
      postInternationalVirtualAccountActivate(currency || currencyForActivation),
    onError: () => {
      track('response', {
        objectName: 'Failed to activate virtual account',
      });
      showNotification({
        type: 'error',
        message: 'Failed to activate virtual account(s). Please try again',
      });
    },
    onSuccess: () => {
      track('response', {
        objectName: 'Virtual account activated',
      });
      setCurrencyForActivation('');
      refetchVirtualAccounts();
      showNotification({
        type: 'success',
        message: 'Virtual account(s) activated successfully',
      });
    },
  });

  const { accounts, status } = virtualAccountData?.data ?? {};
  const isActivated = status === ACCOUNTS_STATUS.ACTIVATED;
  const isDeactivated = status === ACCOUNTS_STATUS.DEACTIVATED;

  const {
    iecCode,
    purposeCode,
    purposeCodeDesc,
    shouldShowActivate,
    isEddVerified,
    isLoading: isVerificationLoading,
    refetchEddDetails,
    refetchPurposeCode,
  } = useVerificationStatus({ user });

  const isLoading = isVirtualAccountLoading || isVerificationLoading;

  /**
   * Allow merchant to request activation for all the virtual accounts
   * Conditions:
   *  1. No virtual accounts are present
   *  2. Promoter PAN name is available
   *  3. VKYC is not requested i.e statue is in {typeof V_KYC_STATUS_FOR_ACTIVATION}
   */
  const canRequestActivation = !status && accounts?.length === 0 && shouldShowActivate;

  /**
   * Method to toggle virtual account status
   */
  const handleAccountStatusToggle = () => {
    track('clicked', {
      objectName: 'Toggle virtual account status',
      isActivated,
    });
    toggleActivation(isActivated ? 'deactivate' : 'activate');
  };

  const handleActivationModalOpen = (triggerActivation = false, step?: 1 | 2 | 3 | 4) => {
    track('clicked', {
      objectName: 'Activation modal open',
      step: step as number,
    });

    if (triggerActivation) {
      activateAllAccounts('');
    } else if (isActivationModalOpen) {
      // if modal was previously opened then refetch the data
      refetchEddDetails();
      refetchVirtualAccounts();
      refetchPurposeCode();
    }

    const currentStep = purposeCode && iecCode ? 3 : 1;

    setActivationModalCurrentStep(step ?? currentStep);
    setActivationModalOpen((prev) => !prev);
  };

  /**
   * Call this method when merchant wants to activate all the accounts
   */
  const handleActivate = (currency?: string) => {
    track('clicked', {
      objectName: 'Activate MoneySaver',
      currency: currency as string,
    });
    if (!isEddVerified || !purposeCode || !iecCode) {
      handleActivationModalOpen();
      return;
    }
    activateAllAccounts(currency);
  };

  /**
   * Call this method when merchant wants to activate a single account
   * @param {string} currency - Currency for which account activation is requested
   */
  const handleSingleAccountActivation = (currency: string) => {
    track('clicked', {
      objectName: 'Activate a single account',
      currency,
    });
    setCurrencyForActivation(currency);
    handleActivate(currency);
  };

  const handleFailureRetry = (reason: VerificationStatus[keyof VerificationStatus]) => {
    track('clicked', {
      objectName: 'Retry activation',
      reason,
    });
    handleActivationModalOpen(false, VERIFICATION_STATUS_ACTIVATION_MODAL_STEP[reason]);
  };

  return {
    status,
    isLoading: isLoading || isActivating,
    isActivated,
    isActivating,
    isDeactivated,
    isEddVerified,
    iecCode,
    purposeCode,
    purposeCodeDesc,
    isToggleLoading,
    hasPromoterPanName,
    canRequestActivation,
    isActivationModalOpen,
    activationModalCurrentStep,
    handleActivate,
    handleFailureRetry,
    handleAccountStatusToggle,
    handleActivationModalOpen,
    handleSingleAccountActivation,
  };
};
