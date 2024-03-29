import React, { useMemo } from 'react';
import { connect } from 'react-redux';

import { Box, Checkbox, Text } from '@razorpay/blade/components';
import {
  ActionSelector,
  Label,
  SelectorItem,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/styled';

import {
  findRulesInRange,
  formatFeeRuleRange,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/utils';

const FeeRuleSelector = ({
  selectedRuleIds,
  setSelectedRuleIds,
  cod_engine_config,
  isCODBlocked,
}) => {
  const { fee_rules } = cod_engine_config;

  const handleChange = (e) => {
    const { isChecked, value: id } = e;
    if (isChecked) {
      setSelectedRuleIds([...selectedRuleIds, id]);
    } else {
      setSelectedRuleIds(selectedRuleIds.filter((sid) => sid !== id));
    }
  };

  const disabledRuleIds = useMemo(() => {
    const disabledRules: Record<string, unknown>[] = [];
    selectedRuleIds.forEach((id) => {
      const rule = fee_rules.find((rule) => rule.id === id);
      const inRangeRules = findRulesInRange(rule, fee_rules);
      if (inRangeRules.length) {
        disabledRules.push(...inRangeRules);
      }
    });
    return disabledRules.map((r) => r?.id);
  }, [selectedRuleIds]);

  return (
    <ActionSelector>
      <Box display="flex" justifyContent="space-between" marginBottom="spacing.2">
        <Text weight="semibold" color="surface.text.gray.muted">
          Order Range
        </Text>
        <Box width="35px">
          <Text weight="semibold" color="surface.text.gray.muted">
            Fee
          </Text>
        </Box>
      </Box>
      {fee_rules?.map((rule) => {
        const { order_range, fee } = formatFeeRuleRange(rule);
        const isDisabled = isCODBlocked || disabledRuleIds.includes(rule.id);
        return (
          <SelectorItem key={rule.id} className={`${isDisabled ? 'disabled-item' : ''}`}>
            <Box display="flex" alignItems="center">
              <Checkbox
                value={rule.id}
                isChecked={selectedRuleIds.includes(rule.id)}
                onChange={handleChange}
              >
                <Label>{order_range}</Label>
              </Checkbox>
            </Box>
            <Box width="35px">
              <Text>{fee}</Text>
            </Box>
          </SelectorItem>
        );
      })}
    </ActionSelector>
  );
};

const mapStateToProps = (state) => ({
  cod_engine_config: state.magicCODEngine,
});

export default connect(mapStateToProps, null)(FeeRuleSelector);
