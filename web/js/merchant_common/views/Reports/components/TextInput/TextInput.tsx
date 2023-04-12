import React, { useRef, useState } from 'react';
import { Input } from './styled';
import { Text } from 'merchant_common/views/Reports/components';
import { TextInputProps } from './types';
import { useClickOutSide, useTheme } from 'merchant_common/views/Reports/hooks';

export const TextInput = ({
  label,
  helpText,
  value,
  validate = () => true,
  onChange,
  placeHolder,
  ariaLabel,
}: TextInputProps): JSX.Element => {
  const { theme } = useTheme();
  const isValidated = validate();
  const ref = useRef<HTMLDivElement | null>(null);
  const [isFocused, setFocused] = useState<boolean>(false);

  useClickOutSide([ref], () => {
    if (isFocused) setFocused(false);
  });

  return (
    <div ref={ref}>
      {Boolean(label) && (
        <Text variant="body" type="normal" weight="bold">
          {label}
        </Text>
      )}
      <Input
        onFocus={() => setFocused(true)}
        theme={theme}
        placeholder={placeHolder}
        type="text"
        value={value}
        validation={isValidated}
        onChange={(e) => onChange(e.target.value)}
        label={label}
        focused={isFocused}
        aria-label={ariaLabel ?? 'Text Input'}
      />
      {Boolean(helpText) && (
        <Text
          variant="caption"
          type="subdued"
          weight="regular"
          color={
            isValidated ? 'surface.text.subdued.lowContrast' : 'feedback.text.negative.lowContrast'
          }
        >
          {isValidated ? helpText : `Mandatory Field: ${helpText}`}
        </Text>
      )}
    </div>
  );
};
