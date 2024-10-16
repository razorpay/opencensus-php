import React from 'react';
import {
  ActionList,
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  SelectInput,
} from '@razorpay/blade/components';

import { ListFiltersContextType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/context';
import { activationStatusMenu } from 'merchant/views/PartnerDashboard/SubMerchant/components/ActivationStatusFilter';
type ActivationStatusFilterProps = {
  value: string;
  onChange: ListFiltersContextType['handleChange'];
};
const ActivationStatusFilter = ({ value, onChange }: ActivationStatusFilterProps): JSX.Element => {
  return (
    <Dropdown selectionType="single">
      <SelectInput
        label="Activation Status"
        labelPosition="top"
        name="activation_status"
        onChange={({ name, values }) => {
          onChange({ name, value: values[0] });
        }}
        placeholder="Select Option"
        validationState="none"
        value={value}
      />
      <DropdownOverlay>
        <ActionList>
          {activationStatusMenu.map(({ name, label }, index) => (
            <ActionListItem title={label} value={name} key={`${name}-${index}`} />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};

export default ActivationStatusFilter;
