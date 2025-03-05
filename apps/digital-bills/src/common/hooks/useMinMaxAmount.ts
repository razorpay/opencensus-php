import React from 'react';

type ValidationState = 'none' | 'success' | 'error';
type AmountType = 'min' | 'max';

const useMinMaxAmount = (
  minAmount: number | null,
  maxAmount: number | null,
  setFilterMinAmount: (min: number | null) => void,
  setFilterMaxAmount: (max: number | null) => void,
) => {
  const [minAmountValidationState, setMinAmountValidationState] =
    React.useState<ValidationState>('none');
  const [maxAmountValidationState, setMaxAmountValidationState] =
    React.useState<ValidationState>('none');
  const [minAmountErrorMsg, setMinAmountErrorMsg] = React.useState('');
  const [maxAmountErrorMsg, setMaxAmountErrorMsg] = React.useState('');

  const amountChangeHandler = (value: string | undefined, type: AmountType) => {
    let min: number | null = minAmount;
    let max: number | null = maxAmount;
    if (type === 'min') min = value === '' ? null : Number(value);
    if (type === 'max') max = value === '' ? null : Number(value);

    if ((min !== null && isNaN(min)) || (max !== null && isNaN(max))) return;
    if (min !== null && max === null) {
      setMaxAmountValidationState('error');
      setMaxAmountErrorMsg('Max amount is required');
    } else if (max !== null && min === null) {
      setMinAmountValidationState('error');
      setMinAmountErrorMsg('Min amount is required');
    } else if (min !== null && max !== null && min > max) {
      setMinAmountValidationState('error');
      setMaxAmountValidationState('error');
      setMinAmountErrorMsg('Min amount should be less than max amount');
      setMaxAmountErrorMsg('Max amount should be greater than min amount');
    } else {
      setMinAmountValidationState('none');
      setMaxAmountValidationState('none');
      setMinAmountErrorMsg('');
      setMaxAmountErrorMsg('');
    }
    if (type === 'min') {
      setFilterMinAmount(min);
    } else {
      setFilterMaxAmount(max);
    }
  };

  return {
    minAmountValidationState,
    maxAmountValidationState,
    minAmountErrorMsg,
    maxAmountErrorMsg,
    amountChangeHandler,
  };
};

export default useMinMaxAmount;
