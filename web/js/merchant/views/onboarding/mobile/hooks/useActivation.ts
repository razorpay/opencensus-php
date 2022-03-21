import { useEffect } from 'react';
import { useQuery, useQueryCache, useMutation } from 'react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';
import { useApp } from 'common/context/App';
import { useActivationFormState, isTabComplete } from '../context/store';
import activationFormatter from '../services/formatters/activation';
import {
  isUnregisteredBusiness,
  isDocumentTabComplete,
  getDefaultSelectedDocs,
} from '../services/utils';

export const postActivation = (data, accountId) =>
  fetch<any>({
    url: 'merchant/activation',
    method: 'POST',
    data,
    mode: 'live',
    headers: {
      'X-Razorpay-Account': accountId,
      'Content-Type': 'application/json',
    },
  });

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

export const saveFile = ({ formData, progressTracker, accountId }) =>
  fetch<any>({
    url: 'merchant/documents/upload',
    method: 'POST',
    mode: 'live',
    data: formData,
    onUploadProgress: progressTracker,
    headers: {
      'X-Razorpay-Account': accountId,
    },
  });

export const deleteFile = (curDoc, accountId) =>
  fetch<any>({
    url: `merchant/documents/doc_${curDoc.id}`,
    method: 'DELETE',
    mode: 'live',
    headers: {
      'X-Razorpay-Account': accountId,
    },
  });

export default function useActivation() {
  const snackbar = useSnackbar();
  const { experiments, submerchantId: accountId } = useApp();
  // passing account id in headers to load submerchant's activation form in partner's dashboard account
  const cacheKey = accountId ? `activation_${accountId}` : `activation`;
  const { status, data, refetch } = useQuery(
    cacheKey,
    async () => {
      const response = await fetch<any>({
        url: 'merchant/activation',
        mode: 'live',
        headers: {
          'X-Razorpay-Account': accountId,
        },
      });
      const formattedData = activationFormatter(response, experiments);
      return formattedData;
    },
    {
      refetchOnMount: 'always',
      staleTime: Infinity,
      onError: (err: any) => {
        if (err?.response?.errors) snackbar.error(err.response.errors[0]);
      },
    },
  );

  const queryCache = useQueryCache();
  const [postData] = useMutation(
    (formData: any) => {
      return postActivation(formData, accountId);
    },
    {
      onSuccess: (result) => {
        const formattedData = activationFormatter(result, experiments);
        queryCache.setQueryData(cacheKey, formattedData);
      },
      onError: (err: any) => {
        if (err?.response?.errors) snackbar.error(err.response.errors[0]);
      },
    },
  );

  const [documentUpload] = useMutation(
    ({ formData, progressTracker }: any) => {
      return saveFile({
        formData,
        progressTracker,
        accountId,
      });
    },
    {
      onSuccess: (result) => {
        const formattedData = activationFormatter(result, experiments);
        queryCache.setQueryData(cacheKey, formattedData);
      },
      onError: (err: any) => {
        if (err?.response?.errors) snackbar.error(err.response.errors[0]);
      },
    },
  );

  const [documentDelete] = useMutation(
    (curDoc: any) => {
      return deleteFile(curDoc, accountId);
    },
    {
      onSuccess: (result) => {
        const formattedData = activationFormatter(result, experiments);
        queryCache.setQueryData(cacheKey, formattedData);
      },
      onError: (err: any) => {
        if (err?.response?.errors) snackbar.error(err.response.errors[0]);
      },
    },
  );

  const setContactDetailsCompleted = useActivationFormState(
    (state) => state.setContactDetailsCompleted,
  );
  const setBusinessOverviewCompleted = useActivationFormState(
    (state) => state.setBusinessOverviewCompleted,
  );
  const setBusinessDetailsCompleted = useActivationFormState(
    (state) => state.setBusinessDetailsCompleted,
  );
  const setBankAndCompanyDetailsCompleted = useActivationFormState(
    (state) => state.setBankAndCompanyDetailsCompleted,
  );
  const setDocumentUploadCompleted = useActivationFormState(
    (state) => state.setDocumentUploadCompleted,
  );
  const setHasWebsite = useActivationFormState((state) => state.setHasWebsite);
  const setHasApp = useActivationFormState((state) => state.setHasApp);
  const setHasWebsiteOrApp = useActivationFormState((state) => state.setHasWebsiteOrApp);
  const activeTabId = useActivationFormState((state) => state.active_tab_id);
  const setSameAddress = useActivationFormState((state) => state.setSameAddress);
  const setHasGSTIN = useActivationFormState((state) => state.setHasGSTIN);
  const hasNonMandatoryEmail = useActivationFormState((state) => state.has_non_mandatory_email);

  useEffect(() => {
    if (status === 'success') {
      // get default selected state of documents
      const addressDoc = getDefaultSelectedDocs(data, 'address');
      const bankDoc = getDefaultSelectedDocs(data, 'bank');
      const businessDoc = getDefaultSelectedDocs(data, 'business');
      const additionalDoc = getDefaultSelectedDocs(data, 'additional');

      const isContactDetailsTabComplete = isTabComplete(
        { ...data, isEmailNonMandatoryOnL1: experiments.isEmailNonMandatoryOnL1 },
        'contact_details',
      );
      const isBusinessOverviewTabComplete = isTabComplete(data, 'business_overview');
      const isBusinessDetailsTabComplete = isTabComplete(
        { ...data, isInstantActivationEnabled: experiments.isInstantActivationEnabled },
        'business_details',
      );
      const isBankAndCompanyDetailsTabComplete = isTabComplete(
        data,
        'bank_and_company_details',
        experiments.isLiteOnboarding,
      );
      const isDocumentsUploadTabComplete = isDocumentTabComplete(
        {
          ...data,
          addressDoc,
          bankDoc,
          businessDoc,
          additionalDoc,
          hasNonMandatoryEmail,
        },
        experiments.isGstinMandatory,
        experiments.isLiteOnboarding,
      );
      setContactDetailsCompleted(isContactDetailsTabComplete);
      setBusinessOverviewCompleted(isBusinessOverviewTabComplete);
      setBusinessDetailsCompleted(isBusinessDetailsTabComplete);
      setBankAndCompanyDetailsCompleted(isBankAndCompanyDetailsTabComplete);
      setDocumentUploadCompleted(isDocumentsUploadTabComplete);
      if (data.business_overview.business_website.value) {
        setHasWebsite(true);
      }
      if (data.playstore_url) {
        setHasApp(true);
      }
      if (
        !data.business_overview.business_website.value &&
        !data.playstore_url &&
        activeTabId !== 'business_overview'
      ) {
        setHasWebsiteOrApp(false);
        setHasApp(false);
        setHasWebsite(false);
      }
      if (data.business_overview.business_website.value || data.playstore_url) {
        setHasWebsiteOrApp(true);
      }
      if (
        isUnregisteredBusiness(data.business_overview.business_type.value) ||
        (data.business_details.business_registered_pin.value &&
          data.business_details.business_registered_pin.value ===
            data.business_details.business_operation_pin.value)
      ) {
        setSameAddress(true);
      } else if (
        data.business_details.business_registered_pin.value &&
        data.business_details.business_registered_pin.value !==
          data.business_details.business_operation_pin.value
      ) {
        setSameAddress(false);
      }

      if (data.gstin === '') {
        setHasGSTIN(true);
      } else {
        setHasGSTIN(false);
      }
    }
  }, [
    status,
    data,
    setContactDetailsCompleted,
    setBusinessOverviewCompleted,
    setBusinessDetailsCompleted,
    setBankAndCompanyDetailsCompleted,
    setDocumentUploadCompleted,
    setHasWebsite,
    setHasApp,
    setHasWebsiteOrApp,
    setSameAddress,
    setHasGSTIN,
  ]);

  return { status, data, postData, documentUpload, documentDelete, refetch };
}
