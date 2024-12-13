import React, { useState, useEffect, useRef, useMemo } from 'react';
import {
  Box,
  Text,
  TextInput,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  RupeeIcon,
  Button,
  CloseIcon,
} from '@razorpay/blade/components';

import debounce from 'common/utils/debounce';
import {
  booleanSelectInputOptions,
  LIMITS,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/constants';
import { ConditionTreeBranch } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/styled';
import {
  useRuleFacts,
  useRuleMutations,
  useRuleValidation,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';
import { operators } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/rulefacts/operators';

type ConditionProps = {
  block: any;
  condition: any;
  indexPosition: number;
  totalConditionGroups: number;
};
export const Condition: React.FC<ConditionProps> = ({
  block,
  condition,
  totalConditionGroups,
  indexPosition,
}) => {
  const valueInputRef = useRef<any>();
  const [isConditionHovered, setIsConditionHovered] = useState(false);
  const [hasFactError, setHasFactError] = useState(false);
  const [hasOpError, setHasOpError] = useState(false);
  const { validationResult } = useRuleValidation();
  const mutations = useRuleMutations();
  const { ruleFacts } = useRuleFacts();

  const hasSiblingConditions = block.conditions.length > LIMITS.MIN_CONDITIONS_IN_GROUP;
  const isParentGroupRemovable = totalConditionGroups > LIMITS.MIN_GROUPS_IN_BLOCK;

  const ruleFactUsed = ruleFacts.find((fact) => fact.name === condition.fact);
  const groupErrors = validationResult.conditions[block.id] || {};
  const groupErrorsCount = Object.keys(groupErrors).length;
  const conditionErrors = useMemo(() => groupErrors[condition.id] || {}, [groupErrors]);

  // debounced rule update for performance
  const setValueToRuleState = debounce((value: any) => {
    let formattedValue = value;
    if (ruleFactUsed?.type === 'number') {
      try {
        formattedValue = parseFloat(value);
      } catch (_) {
        //
      }
    }
    mutations.conditions.update('value', formattedValue, condition.path);
  }, 800);

  const handleFactChange = (value: string) => {
    const fact = ruleFacts.find((fact) => fact.name === value);
    if (value) {
      setHasFactError(false);
    }
    mutations.conditions.update('fact', value, condition.path);
    mutations.conditions.update('operator', fact?.defaultOperator?.value, condition.path);
    mutations.conditions.update('value', '', condition.path);
    if (valueInputRef.current) {
      valueInputRef.current.value = '';
    }
  };

  useEffect(() => {
    setHasFactError(conditionErrors?.fact ?? false);
  }, [conditionErrors]);

  return (
    <Box
      position="relative"
      display="flex"
      gap="spacing.5"
      flexDirection="column"
      alignItems="stretch"
      onMouseEnter={() => {
        setIsConditionHovered(true);
      }}
      onMouseLeave={() => {
        setIsConditionHovered(false);
      }}
    >
      {isConditionHovered && (hasSiblingConditions || isParentGroupRemovable) && (
        <Box
          position="absolute"
          top="spacing.0"
          right="100%"
          bottom="spacing.0"
          left="-50px"
          zIndex={10}
        >
          <Box position="absolute" bottom="spacing.0">
            <Button
              accessibilityLabel="Remove condition"
              variant="tertiary"
              icon={CloseIcon}
              onClick={() => {
                if (hasSiblingConditions) {
                  mutations.conditions.remove(condition.path);
                }

                if (!hasSiblingConditions && isParentGroupRemovable) {
                  mutations.conditions.remove(block.path);
                }
              }}
            />
          </Box>
        </Box>
      )}

      <Box display="flex" gap="spacing.5" alignItems="center" justifyContent="stretch">
        <Box position="relative" width="250px">
          {indexPosition > 0 && (
            <ConditionTreeBranch
              drawVertical={indexPosition > 0 && indexPosition === block.conditions.length - 1}
              errorCount={groupErrorsCount}
              heightMultiplier={indexPosition >= 2 ? indexPosition - 1 : 0}
            />
          )}
          <Dropdown selectionType="single">
            <SelectInput
              accessibilityLabel="Select condition"
              placeholder="Select condition"
              name="fact"
              validationState={hasFactError ? 'error' : 'none'}
              defaultValue={condition.fact}
              onBlur={({ value }) => {
                if (!value) {
                  setHasFactError(true);
                }
              }}
              onChange={({ values }) => {
                handleFactChange(values[0]);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {ruleFacts.map((fact) => (
                  <ActionListItem key={fact.name} title={fact.label} value={fact.name} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
        <Text>op</Text>
        <Box flexGrow="1">
          <Dropdown selectionType="single">
            <SelectInput
              accessibilityLabel="Select operator"
              placeholder="Select operator"
              name="operator"
              defaultValue={condition.operator}
              validationState={hasOpError ? 'error' : 'none'}
              onBlur={({ value }) => {
                if (!value) {
                  setHasOpError(true);
                }
              }}
              onChange={({ values }) => {
                mutations.conditions.update('operator', values[0], condition.path);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {(
                  ruleFacts.find((fact) => fact.name === condition.fact)?.operators || [
                    operators.eq,
                  ]
                ).map((operator: any) => (
                  <ActionListItem
                    key={operator.name}
                    title={operator.label}
                    value={operator.value}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      </Box>
      <Box>
        {ruleFactUsed?.type !== 'boolean' ? (
          <TextInput
            ref={valueInputRef}
            accessibilityLabel={`Enter ${ruleFactUsed?.label || 'value'}`}
            placeholder={ruleFactUsed?.placeholder || `Enter ${ruleFactUsed?.label || 'value'}`}
            errorText={conditionErrors.value || ''}
            validationState={conditionErrors.value ? 'error' : 'none'}
            suffix={condition.fact === 'weight' ? 'grams' : undefined}
            leadingIcon={
              condition.fact === 'subtotal' || condition.fact === 'orderAmount'
                ? RupeeIcon
                : undefined
            }
            defaultValue={condition.value}
            onChange={({ value }) => {
              setValueToRuleState(value);
            }}
          />
        ) : (
          <Dropdown selectionType="single">
            <SelectInput
              accessibilityLabel="Select value"
              placeholder="Select value"
              defaultValue={condition.value}
              validationState={conditionErrors.value ? 'error' : 'none'}
              onChange={({ values }) => {
                setValueToRuleState(values[0] === 'true');
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {booleanSelectInputOptions.map((option) => (
                  <ActionListItem key={option.name} title={option.label} value={option.value} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        )}
      </Box>
    </Box>
  );
};
