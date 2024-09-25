import React from 'react';

import {
  Dropdown,
  DropdownOverlay,
  DropdownLink,
  ActionList,
  ActionListItem,
  ChevronDownIcon,
} from '@razorpay/blade/components';

import { useCheckoutFeatures } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/index';

import { COMMON_Z_INDEX } from 'common/constant';
import { LANGUAGE_OPTIONS } from 'merchant/views/Settings/Configuration/CheckoutFeatures/constants/DefaultValue';

const getLanguageNameByCode = (code: string) => {
  const language = LANGUAGE_OPTIONS.find((lang) => lang.code === code);
  return language?.name;
};
const RightChildren = () => {
  const { values, handleLocaleChange } = useCheckoutFeatures();

  const handleSelectChange = (code: string) => {
    handleLocaleChange(code);
  };

  return (
    <Dropdown>
      <DropdownLink color="neutral" icon={ChevronDownIcon} iconPosition="right">
        {getLanguageNameByCode(values.locale.languageCode)}
      </DropdownLink>
      <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
        <ActionList>
          {LANGUAGE_OPTIONS.map((config) => (
            <ActionListItem
              key={config.code}
              title={config.name}
              value={config.code}
              onClick={({ name }) => {
                handleSelectChange(name);
              }}
            />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};

export default RightChildren;
