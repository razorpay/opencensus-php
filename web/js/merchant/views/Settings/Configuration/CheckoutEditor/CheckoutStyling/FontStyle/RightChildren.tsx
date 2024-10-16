import React from 'react';
import {
  Dropdown,
  DropdownOverlay,
  DropdownLink,
  ActionList,
  ActionListItem,
  ChevronDownIcon,
} from '@razorpay/blade/components';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

import {
  FONT_OPTIONS,
  getFontNameByCode,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

const RightChildren = () => {
  const { values, handleFontStyleChange } = useCheckoutEditor();

  const handleSelectChange = (code: string) => {
    handleFontStyleChange(code);
  };

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
                handleSelectChange(name);
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
