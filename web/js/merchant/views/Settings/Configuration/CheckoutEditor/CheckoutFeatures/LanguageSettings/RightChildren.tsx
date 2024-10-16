import React from 'react';

import {
  Dropdown,
  DropdownOverlay,
  DropdownLink,
  ActionList,
  ActionListItem,
  ChevronDownIcon,
} from '@razorpay/blade/components';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { LANGUAGE_OPTIONS } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';

const getLanguageNameByCode = (code: string) => {
  const language = LANGUAGE_OPTIONS.find((lang) => lang.code === code);
  return language?.name;
};
const RightChildren = () => {
  const { values, handleLocaleChange } = useCheckoutEditor();

  const handleSelectChange = (code: string) => {
    handleLocaleChange(code);
  };

  return (
    <Dropdown>
      <DropdownLink
        color="neutral"
        icon={ChevronDownIcon}
        iconPosition="right"
        testID="language-settings-checkout-button"
      >
        {getLanguageNameByCode(values.locale.languageCode)}
      </DropdownLink>
      <DropdownOverlay>
        <ActionList>
          {LANGUAGE_OPTIONS.map((config) => (
            <ActionListItem
              key={config.code}
              title={config.name}
              value={config.code}
              onClick={({ name }) => {
                handleSelectChange(name);
              }}
              testID={`language-settings-checkout-${config.name}`}
            />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};

export default RightChildren;
