import React, { ChangeEvent } from 'react';
import { Text } from '@razorpay/blade/components';

import { ColorInputLabel, ColorInputWrapper, ColorInputBox, ColorInputText } from './styles';

type ColorTextInputProps = {
  label?: string;
  value?: string;
  onChange?: (event: ChangeEvent) => void;
  helpText?: React.ReactNode | string;
  name: string;
};

const ColorTextInput = ({
  label,
  name,
  value,
  helpText,
  onChange,
}: ColorTextInputProps): JSX.Element => {
  return (
    <ColorInputLabel htmlFor={name}>
      {label && (
        <Text
          color="surface.text.gray.subtle"
          size="small"
          weight="semibold"
          marginBottom="spacing.3"
        >
          {label}
        </Text>
      )}

      <ColorInputWrapper>
        <ColorInputBox type="color" value={value} id={name} onChange={onChange} />
        <ColorInputText type="text" value={value} id={`${name}_text`} onChange={onChange} />
      </ColorInputWrapper>

      {helpText && (
        <Text color="surface.text.gray.muted" size="small" marginTop="spacing.3" variant="caption">
          {helpText}
        </Text>
      )}
    </ColorInputLabel>
  );
};

export default ColorTextInput;
