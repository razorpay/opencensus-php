import * as React from 'react';
import {
  Box,
  Text,
  TextInput,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import { actionTypes as allActionTypes } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/constants';
import {
  useRule,
  useRuleValidation,
  useRuleMutations,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

type ActionBlockProps = {
  type: 'shipping' | 'payment';
  shippingProfiles: any;
};
export const ActionBlock: React.FC<ActionBlockProps> = ({ type, shippingProfiles }) => {
  const { rule } = useRule();
  const { validationResult } = useRuleValidation();
  const mutations = useRuleMutations();
  const ruleActionType = rule.actions[0].type;
  const actionTypes = allActionTypes[type];

  const shouldShowParamsSelect = /show_specific|hide_specific/.test(ruleActionType.toLowerCase());
  const handleTypeSelect = (type: string) => {
    mutations.actions.update('type', type);

    if (!/hide_specific|show_specific/.test(type.toLowerCase())) {
      mutations.actions.update('params', {});
    } else {
      mutations.actions.update('params', { value: [] });
    }
  };

  return (
    <Box>
      <Box
        backgroundColor="surface.background.gray.moderate"
        borderColor="surface.border.gray.muted"
        padding="spacing.6"
        display="flex"
        flexDirection="column"
        gap="spacing.6"
        borderRadius="large"
      >
        <Box display="flex">
          <Box width="102px">
            <Box paddingX="spacing.4" paddingY="spacing.3">
              <Text size="medium" weight="semibold">
                Then
              </Text>
            </Box>
          </Box>
          <Box flexGrow="1" display="flex" flexDirection="column" gap="spacing.7">
            <Box display="flex" gap="spacing.5" flexDirection="column" alignItems="stretch">
              <Box display="flex" gap="spacing.5" alignItems="center">
                <Box flexGrow="1">
                  <Dropdown selectionType="single">
                    <SelectInput
                      accessibilityLabel="Select action"
                      placeholder="Select action"
                      errorText={validationResult.actions?.type}
                      validationState={validationResult.actions?.type ? 'error' : 'none'}
                      name="type"
                      defaultValue={ruleActionType}
                      onChange={({ values }) => {
                        handleTypeSelect(values[0]);
                      }}
                    />
                    <DropdownOverlay>
                      <ActionList>
                        {actionTypes.map((action) => (
                          <ActionListItem
                            key={action.type}
                            title={action.label}
                            value={action.type}
                          />
                        ))}
                      </ActionList>
                    </DropdownOverlay>
                  </Dropdown>
                </Box>
              </Box>
              {shouldShowParamsSelect &&
                (type === 'shipping' ? (
                  <Box>
                    <Dropdown selectionType="multiple">
                      <SelectInput
                        accessibilityLabel="Select shipping methods"
                        placeholder="Select shipping methods"
                        name="action"
                        errorText={validationResult.actions?.params}
                        validationState={validationResult.actions?.params ? 'error' : 'none'}
                        defaultValue={rule.actions[0].params?.value || []}
                        onChange={({ values }) => {
                          mutations.actions.update('params', { value: values });
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
                ) : (
                  <TextInput
                    placeholder="For multiple methods, enter comma separated values. e.g. Razorpay, COD"
                    accessibilityLabel="Enter payment methods"
                    name="params"
                    errorText={validationResult.actions?.params}
                    validationState={validationResult.actions?.params ? 'error' : 'none'}
                    defaultValue={rule.actions[0].params?.value?.join(',') || ''}
                    onChange={({ name, value = '' }) => {
                      mutations.actions.update(name, {
                        value: value.split(',').map((val) => val.trim()),
                      });
                    }}
                  />
                ))}
            </Box>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};
