import { useEffect } from 'react';
import { useQuery, useQueryCache, useMutation } from 'react-query';
import { fetch } from 'v2/services/rest/rest-fetch';
import { useActivationFormState, isTabComplete } from '../context/store';
import activationFormatter from '../services/formatters/activation';
import {
  isUnregisteredBusiness,
  isDocmentTabComplete,
  getDefaultSelectedDocs,
} from '../services/utils';

export const fetchActivationData = async () => {
  const data = await fetch<any>({ url: 'merchant/activation' });
  const formattedData = activationFormatter(data);
  return formattedData;
};

export const postActivation = (data) =>
  fetch<any>({ url: 'merchant/activation', method: 'POST', data });

export const getRequestData = (prevDetails, updatedDetails) => {
  const filteredFields = Object.keys(updatedDetails).filter(
    (key) =>
      key !== 'undefined' &&
      !updatedDetails[key].error &&
      prevDetails[key].value !== updatedDetails[key].value,
  );
  const reqData = filteredFields.reduce((prev, cur) => {
    return {
      ...prev,
      [cur]: updatedDetails[cur].value,
    };
  }, {});
  return reqData;
};

export const saveFile = ({ formData, progressTracker }) =>
  fetch<any>({
    url: 'merchant/documents/upload',
    method: 'POST',
    data: formData,
    onUploadProgress: progressTracker,
  });

export const deleteFile = (curDoc) =>
  fetch<any>({ url: `merchant/documents/doc_${curDoc.id}`, method: 'DELETE' });

export default function useActivation() {
  const { status, data } = useQuery('activation', fetchActivationData, {
    staleTime: Infinity,
  });

  const queryCache = useQueryCache();
  const [postData] = useMutation(postActivation, {
    onSuccess: (result) => {
      const formattedData = activationFormatter(result);
      queryCache.setQueryData('activation', formattedData);
    },
  });

  const [documentUpload] = useMutation(saveFile, {
    onSuccess: (result) => {
      const formattedData = activationFormatter(result);
      queryCache.setQueryData('activation', formattedData);
    },
  });

  const [documentDelete] = useMutation(deleteFile, {
    onSuccess: (result) => {
      const formattedData = activationFormatter(result);
      queryCache.setQueryData('activation', formattedData);
    },
  });

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
  const setSameAddress = useActivationFormState((state) => state.setSameAddress);
  const setHasGSTIN = useActivationFormState((state) => state.setHasGSTIN);
  const hasGSTIN = useActivationFormState((state) => state.has_gstin);

  useEffect(() => {
    if (status === 'success') {
      // get default selected state of documents
      const addressDoc = getDefaultSelectedDocs(data, 'address');
      const bankDoc = getDefaultSelectedDocs(data, 'bank');
      const businessDoc = getDefaultSelectedDocs(data, 'business');
      const additionalDoc = getDefaultSelectedDocs(data, 'additional');

      const isContactDetailsTabComplete = isTabComplete(data, 'contact_details');
      const isBusinessOverviewTabComplete = isTabComplete(data, 'business_overview');
      const isBusinessDetailsTabComplete = isTabComplete(data, 'business_details');
      const isBankAndCompanyDetailsTabComplete = isTabComplete(
        { ...data, hasGSTIN },
        'bank_and_company_details',
      );
      const isDocumentsUploadTabComplete = isDocmentTabComplete({
        ...data,
        addressDoc,
        bankDoc,
        businessDoc,
        additionalDoc,
      });
      setContactDetailsCompleted(isContactDetailsTabComplete);
      setBusinessOverviewCompleted(isBusinessOverviewTabComplete);
      setBusinessDetailsCompleted(isBusinessDetailsTabComplete);
      setBankAndCompanyDetailsCompleted(isBankAndCompanyDetailsTabComplete);
      setDocumentUploadCompleted(isDocumentsUploadTabComplete);
      if (data.business_overview.business_website.value) {
        setHasWebsite(true);
      }
      if (
        isUnregisteredBusiness(data.business_overview.business_type.value) ||
        (data.business_details.business_registered_pin.value &&
          data.business_details.business_registered_pin.value ===
            data.business_details.business_operation_pin.value)
      ) {
        setSameAddress(true);
      }
      if (isUnregisteredBusiness(data.business_overview.business_type.value)) {
        setHasGSTIN(true); // setting as true to hide the GSTIN Input in bank and company details screen
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
    setSameAddress,
    setHasGSTIN,
  ]);

  return { status, data, postData, documentUpload, documentDelete };
}
