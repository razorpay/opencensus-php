import React from 'react';
import {
  Box,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import { useRuleMutations } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import {
  TEXT,
  ACTION_INDEX,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ActionBlock/constants';

type ShippingParamsProps = {
  validationError: string | undefined;
  defaultValue: string[] | undefined;
  shippingProfiles: any;
};
export const ShippingParams: React.FC<ShippingParamsProps> = ({
  defaultValue,
  validationError,
  shippingProfiles,
}) => {
  const mutations = useRuleMutations();

  return (
    <Box>
      <Dropdown selectionType="multiple">
        <SelectInput
          accessibilityLabel={TEXT.SHIPPING_METHODS.A11Y_LABEL}
          placeholder={TEXT.SHIPPING_METHODS.PLACEHOLDER}
          name="params"
          errorText={validationError}
          validationState={validationError ? 'error' : 'none'}
          defaultValue={defaultValue}
          onChange={({ values }) => {
            mutations.actions.update('params', { value: values }, ACTION_INDEX);
          }}
        />
        <DropdownOverlay>
          <ActionList>
            {shippingProfiles.map((profile) => (
              <ActionListItem
                key={profile.id}
                title={`${profile.name} (${profile.shippingZone})`}
                value={`${profile.name}-${Math.floor(profile.fee / 100)}`}
              />
            ))}
          </ActionList>
        </DropdownOverlay>
      </Dropdown>
    </Box>
  );
};
