import React from 'react';
import {
  Dropdown,
  DropdownOverlay,
  DropdownLink,
  ActionList,
  ActionListItem,
  ChevronDownIcon,
} from '@razorpay/blade/components';

import {
  FONT_OPTIONS,
  getFontNameByCode,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

import track from './track';

const RightChildren = () => {
  const { values, handleFontStyleChange } = useCheckoutEditor();

  function handleFontChange(fontFamilyCode: string) {
    handleFontStyleChange(fontFamilyCode);
    track.fontChange({
      heading: fontFamilyCode,
    });
  }

  return (
    <Dropdown>
      <DropdownLink
        color="neutral"
        icon={ChevronDownIcon}
        iconPosition="right"
        testID="font-style-checkout-button"
      >
        {getFontNameByCode(values[CHECKOUT_EDITOR_FIELDS.FONT_FAMILY])}
      </DropdownLink>
      <DropdownOverlay>
        <ActionList>
          {FONT_OPTIONS.map((config) => (
            <ActionListItem
              key={config.code}
              title={config.name}
              value={config.code}
              onClick={({ name }) => {
                handleFontChange(name);
              }}
              testID={`font-style-checkout-${config.name}`}
            />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};

export default RightChildren;
