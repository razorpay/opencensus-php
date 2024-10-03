import React, { useRef } from 'react';
import { Text, Link } from '@razorpay/blade/components';

import { ColorInputBox } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/ColorTextInput/styles';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';
import { RightChildrenWrapper } from 'merchant/views/Settings/Configuration/components/Configuration/styled';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

import { BRAND_COLOR_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';

const RightChildren = ({ color, onChange }) => {
  const colorInputRef = useRef<HTMLInputElement>(null);

  const handleEditClick = () => {
    if (colorInputRef.current) {
      colorInputRef.current.click();
    }
  };
  return (
    <RightChildrenWrapper>
      <ColorInputBox
        type="color"
        name="brand_color"
        value={color}
        onChange={onChange}
        ref={colorInputRef}
      />
      <Text weight="medium" color="surface.text.gray.subtle" variant="body" size="small">
        {color}
      </Text>
      {color === BRAND_COLOR_DEFAULT_VALUE.color && (
        <Text weight="regular" color="surface.text.gray.subtle" variant="body" size="small">
          (default)
        </Text>
      )}
      <Link variant="button" color="primary" size="small" onClick={handleEditClick}>
        Edit
      </Link>
      {color !== BRAND_COLOR_DEFAULT_VALUE.color && (
        <Link
          variant="button"
          color="primary"
          size="small"
          onClick={() => onChange({ target: { value: BRAND_COLOR_DEFAULT_VALUE.color } })}
        >
          Reset
        </Link>
      )}
    </RightChildrenWrapper>
  );
};

const BrandColor = () => {
  const { values, handleBrandColorChange } = useCheckoutEditor();

  return (
    <LineItems
      title={BRAND_COLOR_DEFAULT_VALUE.title}
      subTitle={BRAND_COLOR_DEFAULT_VALUE.subTitle}
      rightChildren={<RightChildren color={values.color} onChange={handleBrandColorChange} />}
    />
  );
};

export default BrandColor;
