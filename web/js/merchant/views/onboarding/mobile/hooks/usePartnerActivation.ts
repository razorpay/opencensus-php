import { useQuery } from 'react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { useApp } from 'common/context/App';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';
import useActivation from './useActivation';

export default function usePartnerActivation(): any {
  const snackbar = useSnackbar();
  const {
    user: { isIndependentPartnerKYCEnabled },
  } = useApp();
  let partnerActivationStatus: null | string = null;
  const { data: merchantActivationData } = useActivation();
  const commonLockedFields = merchantActivationData?.lock_common_fields || [];

  const { data }: any = useQuery(
    `partnerActivationDetails`,
    async () => {
      if (!isIndependentPartnerKYCEnabled) {
        return null;
      }
      const fetchPartnerActivationDetails = await fetch({
        url: `partner/activation`,
      });
      return fetchPartnerActivationDetails;
    },
    {
      retry: false,
      refetchOnWindowFocus: false,
      staleTime: Infinity,
      onError: (err: any) => {
        if (err?.response?.errors) snackbar.error(err.response.errors[0]);
      },
    },
  );
  if (data && data.partner_activation) {
    partnerActivationStatus = data.partner_activation.activation_status;
  }

  const getFieldStatus = (field: string): { isDisabled: boolean; description: string } => {
    let isDisabled = false;
    let description = ``;
    if (
      commonLockedFields.includes(field) &&
      ['activated', 'under_review'].includes(partnerActivationStatus || '')
    ) {
      isDisabled = true;
      switch (partnerActivationStatus) {
        case 'activated':
          description = 'Verified under Partner KYC';
          break;
        case 'under_review':
          description = `Under review in Partner KYC`;
          break;
        default:
          description = `Under review in Partner KYC`;
          break;
      }
    }
    return {
      isDisabled,
      description,
    };
  };

  const shouldBlockMerchantKYC = (): boolean => {
    let shouldBlock = false;
    // block the merchant KYC if Partner KYC is in NC and Merchant KYC form is not submitted
    if (
      isIndependentPartnerKYCEnabled &&
      partnerActivationStatus === 'needs_clarification' &&
      data?.submitted === false
    ) {
      shouldBlock = true;
    }
    return shouldBlock;
  };

  return { getFieldStatus, partnerActivationStatus, shouldBlockMerchantKYC };
}
