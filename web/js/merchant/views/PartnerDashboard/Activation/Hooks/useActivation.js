import { useEffect } from 'react';
import { useQuery, useQueryClient, useMutation } from '@tanstack/react-query';

import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';
import { merchantFetch } from 'merchant/utils/ajax';
import activationFormatter from 'merchant/views/PartnerDashboard/Activation/utils/ActivationFormatter';

import { useActivationFormState, isTabComplete } from './store';

export const fetchActivationData = async () => {
  const data = await merchantFetch({ url: 'partner/activation', mode: 'live' });
  if (data.data) {
    const formattedData = activationFormatter(data.data);
    return formattedData;
  }
  return {};
};

export const postActivation = (data) =>
  merchantFetch({ url: 'partner/activation', method: 'POST', data, mode: 'live' });

export const getRequestData = (prevDetails, updatedDetails) => {
  const filteredFields = Object.keys(updatedDetails).filter(
    (key) =>
      key !== 'undefined' &&
      !updatedDetails[key].error &&
      prevDetails[key]?.value !== updatedDetails[key]?.value,
  );
  const reqData = filteredFields.reduce((prev, cur) => {
    return {
      ...prev,
      [cur]: updatedDetails[cur].value,
    };
  }, {});
  return reqData;
};

export const uploadFileData = (data) =>
  merchantFetch({ url: 'merchant/documents/upload', method: 'POST', data, mode: 'live' });

export const deleteFileData = (fileId) =>
  merchantFetch({ url: `merchant/documents/doc_${fileId}`, method: 'DELETE', mode: 'live' });

export default function useActivation() {
  const snackbar = useSnackbar();
  const { status, data } = useQuery({
    queryKey: ['partner_activation'],
    queryFn: fetchActivationData,
    refetchOnMount: 'false',
    staleTime: Infinity,
    onError: (err) => {
      if (err?.errors) snackbar.error(err.errors[0]);
    },
  });

  const queryCache = useQueryClient();
  const { mutate: postData } = useMutation({
    mutationFn: postActivation,
    onSuccess: (result) => {
      if (result.data) {
        const formattedData = activationFormatter(result.data);
        queryCache.setQueryData(['partner_activation'], formattedData);
      }
    },
    onError: (err) => {
      if (err?.errors) snackbar.error(err.errors[0]);
    },
  });

  const setContactDetailsCompleted = useActivationFormState(
    (state) => state.setContactDetailsCompleted,
  );
  const setBusinessDetailsCompleted = useActivationFormState(
    (state) => state.setBusinessDetailsCompleted,
  );
  const setAddressDetailsCompleted = useActivationFormState(
    (state) => state.setAddressDetailsCompleted,
  );
  const setHasGSTIN = useActivationFormState((state) => state.setHasGSTIN);

  useEffect(() => {
    if (status === 'success') {
      const isContactDetailsTabComplete = isTabComplete(data, 'contact_details');
      const isBusinessDetailsTabComplete = isTabComplete({ ...data }, 'business_details');
      const isAddressDetailsTabComplete = isTabComplete({ ...data }, 'address_details');
      setContactDetailsCompleted(isContactDetailsTabComplete);
      setBusinessDetailsCompleted(isBusinessDetailsTabComplete);
      setAddressDetailsCompleted(isAddressDetailsTabComplete);
      if (data.gstin && data.gstin === '') {
        setHasGSTIN(true);
      } else {
        setHasGSTIN(false);
      }
    }
  }, [
    status,
    data,
    setContactDetailsCompleted,
    setBusinessDetailsCompleted,
    setAddressDetailsCompleted,
    setHasGSTIN,
  ]);

  return { status, data, postData };
}
