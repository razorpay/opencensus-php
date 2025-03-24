import React, { useRef } from 'react';
import { Text, Link } from '@razorpay/blade/components';
import { BrandColorProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';
import { connect } from 'react-redux';

import { ColorInputBox } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/ColorTextInput/styles';
import { BRAND_COLOR_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';
import { RightChildrenWrapper } from 'merchant/views/Settings/Configuration/components/Configuration/Wrappers';

import track from './track';

const RightChildren = ({ color, onChange, showReset, handleResetBrandColor }) => {
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
      {showReset && (
        <Link variant="button" color="primary" size="small" onClick={handleResetBrandColor}>
          Reset
        </Link>
      )}
    </RightChildrenWrapper>
  );
};

const BrandColor: React.FC<BrandColorProps> = ({ accountConfig }) => {
  const { values, handleBrandColorChange: updateBrandColor } = useCheckoutEditor();

  const handleResetBrandColor = () => {
    handleBrandColorChange(undefined, accountConfig?.brand_color);
  };

  function handleBrandColorChange(
    evt?: React.ChangeEvent<Element> | undefined,
    defaultValue?: string | undefined,
  ) {
    updateBrandColor(evt, defaultValue);
    track.backgroundColorChange();
  }

  return (
    <LineItems
      title={BRAND_COLOR_DEFAULT_VALUE.title}
      subTitle={BRAND_COLOR_DEFAULT_VALUE.subTitle}
      rightChildren={
        <RightChildren
          color={values.color}
          onChange={handleBrandColorChange}
          showReset={accountConfig?.brand_color !== values[CHECKOUT_EDITOR_FIELDS.COLOR]}
          handleResetBrandColor={handleResetBrandColor}
        />
      }
    />
  );
};

const mapStateToProps = (state) => ({
  accountConfig: {
    ...state.config?.config,
  },
});

export default connect(mapStateToProps)(BrandColor);
