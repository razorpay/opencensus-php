import { useEffect } from 'react';
import axios from 'axios';
import { useQuery, useQueryCache, useMutation } from 'react-query';
import { useActivationFormState, isTabComplete } from '../context/store';
import activationFormatter from '../services/formatters/activation';
import { isUnregisteredBusiness } from '../Constants/OnboardingConstants';

export const fetchActivationData = async () => {
  const data = await axios.get('http://localhost:6006/activation').then((res) => res.data.data);
  const formattedData = activationFormatter(data);
  return formattedData;
};

export const postActivation = (data) =>
  axios
    .post('http://localhost:6006/activation', {
      ...data,
    })
    .then((res) => res.data.data);

export const getRequestData = (prevDetails, updatedDetails) => {
  const filteredFields = Object.keys(updatedDetails).filter(
    (key) =>
      key !== 'undefined' &&
      !updatedDetails[key].error &&
      prevDetails[key].value !== updatedDetails[key].value,
  );
  const reqData = filteredFields.reduce((prev, cur) => {
    delete updatedDetails[cur].error;
    return {
      ...prev,
      [cur]: updatedDetails[cur],
    };
  }, {});
  return reqData;
};

export default function useActivation() {
  const { status, data } = useQuery('activation', fetchActivationData, {
    staleTime: Infinity,
  });

  const queryCache = useQueryCache();
  const [postData] = useMutation(postActivation, {
    onSuccess: () => queryCache.invalidateQueries('activation'),
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
  const setHasWebsite = useActivationFormState((state) => state.setHasWebsite);
  const setSameAddress = useActivationFormState((state) => state.setSameAddress);
  const setHasGSTIN = useActivationFormState((state) => state.setHasGSTIN);

  useEffect(() => {
    if (status === 'success') {
      const isContactDetailsTabComplete = isTabComplete(data, 'contact_details');
      const isBusinessOverviewTabComplete = isTabComplete(data, 'business_overview');
      const isBusinessDetailsTabComplete = isTabComplete(data, 'business_details');
      const isBankAndCompanyDetailsTabComplete = isTabComplete(data, 'bank_and_company_details');
      setContactDetailsCompleted(isContactDetailsTabComplete);
      setBusinessOverviewCompleted(isBusinessOverviewTabComplete);
      setBusinessDetailsCompleted(isBusinessDetailsTabComplete);
      setBankAndCompanyDetailsCompleted(isBankAndCompanyDetailsTabComplete);
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
    setHasWebsite,
    setSameAddress,
    setHasGSTIN,
  ]);

  return { status, data, postData };
}
