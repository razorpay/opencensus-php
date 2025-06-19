import React from 'react';
import {
  Chip,
  ChipGroup,
  TopLeftRoundedCornerIcon,
  TopLeftSharpCornerIcon,
} from '@razorpay/blade/components';

import { AVAILABLE_BORDER_STYLE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';

import { RightChildrenWrapper } from 'merchant/views/Settings/Configuration/components/Configuration/Wrappers';
import track from './track';

const BORDER_TITLE = 'Border style';

const RightChildren = () => {
  const { values, handleButtonStyleChange } = useCheckoutEditor();

  const selectedButton = values[CHECKOUT_EDITOR_FIELDS.BORDER_STYLE] ?? AVAILABLE_BORDER_STYLE.ROUNDED;

  function handleButtonStyleClick(name: string) {
    handleButtonStyleChange(name);
    track.borderStyleClicked(name);
  }

  return (
    <RightChildrenWrapper>
      <ChipGroup
        accessibilityLabel="choose one border style from options below"
        selectionType="single"
        onChange={({ values }) => {
          handleButtonStyleClick(values?.[0]);
        }}
        value={selectedButton}
        marginBottom="none"
        marginTop="spacing.3"
      >
        <Chip
          value={AVAILABLE_BORDER_STYLE.ROUNDED}
          icon={TopLeftRoundedCornerIcon}
          marginBottom="none"
        >
          rounded
        </Chip>
        <Chip
          value={AVAILABLE_BORDER_STYLE.SHARP}
          icon={TopLeftSharpCornerIcon}
          marginBottom="none"
        >
          sharp
        </Chip>
      </ChipGroup>
    </RightChildrenWrapper>
  );
};

const ButtonStyle = () => {
  return <LineItems title={BORDER_TITLE} rightChildren={<RightChildren />} />;
};

export default ButtonStyle;
