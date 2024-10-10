import React, { useRef } from 'react';

import { Link, Text } from '@razorpay/blade/components';
import { ColorInputBox, ColorInputWrapper } from './styled';

import { ChangeColorInputProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';

import { CUSTOM_MESSAGE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';

const ChangeColorInput: React.FC<ChangeColorInputProps> = ({ label, value, onChange }) => {
  const colorInputRef = useRef<HTMLInputElement>(null);

  const handleEditClick = () => {
    if (colorInputRef.current) {
      colorInputRef.current.click();
    }
  };
  return (
    <ColorInputWrapper>
      {label?.length && (
        <Text
          weight="semibold"
          size="small"
          color="surface.text.gray.muted"
          marginBottom={'spacing.2'}
        >
          {label}
        </Text>
      )}

      <ColorInputBox
        type="color"
        name="brand_color"
        value={value}
        onChange={onChange}
        ref={colorInputRef}
      />
      <Text weight="medium" color="surface.text.gray.subtle" variant="body" size="small">
        {value}
      </Text>
      {value === CUSTOM_MESSAGE_DEFAULT_VALUE.bannerColor && (
        <Text weight="regular" color="surface.text.gray.subtle" variant="body" size="small">
          (recommended)
        </Text>
      )}
      <Link variant="button" color="primary" size="small" onClick={handleEditClick}>
        Edit
      </Link>
    </ColorInputWrapper>
  );
};

export default ChangeColorInput;
