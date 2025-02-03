import { useEffect, useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import debounce from 'lodash/debounce';

import { useActivationState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';
import { track } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/analytics';

import { DEFAULT_VALIDATION, STEPS } from './constants';
import { getPurposeCode } from './helpers';
import { PurposeCodes } from './types';

export const usePurposeCode = () => {
  const [selectedGroup, setSelectedGroup] = useState('');
  const [searchValue, setSearchValue] = useState('');

  const purposeCode = useActivationState(
    (state) => state.steps[STEPS.PURPOSE_CODE].fields.purposeCode,
  );
  const validationState = useActivationState(
    (state) => state.steps[STEPS.PURPOSE_CODE].validationState,
  );
  const isReadOnly = useActivationState((state) => state.steps[STEPS.PURPOSE_CODE].isReadOnly);
  const setStepFields = useActivationState((state) => state.setStepFields);
  const setStepValidationState = useActivationState((state) => state.setStepValidationState);

  const { data, isLoading } = useQuery<PurposeCodes>({
    queryKey: ['purpose_codes'],
    queryFn: getPurposeCode,
    refetchOnWindowFocus: false,
  });

  const { groups, codes } = data ?? {};

  const handleSearch = useMemo(
    () =>
      debounce(({ value }: { value?: string }) => {
        setSearchValue(value ?? '');
        setSelectedGroup('');
      }, 300),
    [],
  );

  const selectedGroupCodes = useMemo(
    () => groups?.find((group) => group.purposeGroup === selectedGroup)?.codes,
    [groups, selectedGroup],
  );

  const filteredCodes = useMemo(() => {
    if (searchValue && codes) {
      const searchValueLower = searchValue.toLowerCase();
      return codes.filter(
        (code) =>
          code.purposeCode.toLowerCase().includes(searchValueLower) ||
          code.description.toLowerCase().includes(searchValueLower) ||
          code.helpText.toLowerCase().includes(searchValueLower),
      );
    }

    return codes;
  }, [codes, searchValue]);

  const handleSelectedGroupChange = ({ values }: { values: string[] }): void => {
    setSelectedGroup(values[0]);
    track('clicked', { objectName: 'purpose code group selected', value: values[0] });
  };

  const handleValueChange = ({ value }: { value: string }): void => {
    setStepFields(STEPS.PURPOSE_CODE, {
      purposeCode: value,
      purposeCodeDesc: codes?.find((code) => code.purposeCode === value)?.description ?? '',
    });

    setStepValidationState(STEPS.PURPOSE_CODE, 'purposeCode', DEFAULT_VALIDATION);

    track('clicked', { objectName: 'purpose code selected', value });
  };

  const handleClearSearch = () => handleSearch({ value: '' });

  useEffect(() => {
    track('render', { objectName: 'purpose code step' });
  }, []);

  return {
    groups,
    isReadOnly,
    purposeCode,
    isLoading,
    searchValue,
    handleSearch,
    filteredCodes,
    selectedGroup,
    validationState,
    selectedGroupCodes,
    handleClearSearch,
    handleValueChange,
    handleSelectedGroupChange,
  };
};
