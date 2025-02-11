import React, { useState, useEffect } from 'react';

import { StyledRadioGroupWrapper, StyledRadioButton, StyledRadioInput } from './styled';
import { RadioButtonGroupProps } from './type';

const RadioButtonGroup: React.FC<RadioButtonGroupProps> = (props) => {
  const {
    testID = 'radio-button-group',
    options,
    isDisabled,
    selectedOption = options?.[0]?.value,
    onChange,
  } = props;

  const [optionState, setOptionState] = useState<string | undefined>(selectedOption);

  useEffect(() => {
    setOptionState(selectedOption);
  }, [selectedOption]);

  // Check if options is an array and each element is an object with 'value' and 'label' keys
  if (
    !Array.isArray(options) ||
    !options.every(
      (option) => option && typeof option === 'object' && 'value' in option && 'label' in option,
    )
  ) {
    if (window.APP_ENV !== 'production') {
      console.error(
        'Invalid options format. Please provide an array of objects with "value" and "label" keys.',
      );
    }
    return null;
  }

  const handleOption = (value: string): void => {
    setOptionState(value);
    if (onChange) {
      onChange(value);
    }
  };

  return (
    <StyledRadioGroupWrapper data-testid={testID}>
      {options.map(({ label, value, disabled }) => (
        <StyledRadioButton key={value} checked={optionState === value} disabled={disabled}>
          <StyledRadioInput
            role="radio"
            name="radio-group"
            id={value}
            value={value}
            checked={optionState === value}
            disabled={disabled || isDisabled}
            aria-checked={optionState === value}
            aria-disabled={disabled || isDisabled}
            aria-hidden="true"
            onChange={() => handleOption(value)}
          />
          {label}
        </StyledRadioButton>
      ))}
    </StyledRadioGroupWrapper>
  );
};

export default RadioButtonGroup;
