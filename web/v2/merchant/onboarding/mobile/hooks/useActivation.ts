import { useEffect } from 'react';
import { useQuery, useQueryCache, useMutation } from 'react-query';
import { useActivationFormState, isTabComplete } from '../context/store';
import activationFormatter from '../../../../services/formatters/activation';

const fetchActivationData = async () => {
  const { data } = await fetch('/activation').then((res) => res.json());
  const formattedData = activationFormatter(data);
  return formattedData;
};

const postActivation = (data) =>
  fetch('/activation', {
    method: 'POST',
    body: JSON.stringify(data),
  }).then((res) => res.json());

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
    }
  }, [
    status,
    data,
    setContactDetailsCompleted,
    setBusinessOverviewCompleted,
    setBusinessDetailsCompleted,
    setBankAndCompanyDetailsCompleted,
  ]);

  return { status, data, postData };
}
