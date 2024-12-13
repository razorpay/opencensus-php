import React from 'react';
import { connect } from 'react-redux';

import RuleCreatorModal from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal';
import { Facts } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { ruleValidator } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/util';
import { RuleCreator } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator';
import {
  useRule,
  useRuleMutations,
  useRuleValidation,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';
import { formatRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/formatRule';
import {
  toJSON,
  toString,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/json';
import { parseShopifyRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/parse';
import { useConfirm } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';

import type { Rule as APIRule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

type RuleCreatorContainerProps = {
  isOpen: boolean;
  setIsOpen: (isOpen: boolean) => void;
  isProcessingReq: boolean;
  apiRule: APIRule;
  updateRule: (rule: Partial<APIRule>) => void;
  createRule: (rule: Partial<APIRule>) => void;
};
const RuleCreatorContainer: React.FC<RuleCreatorContainerProps> = ({
  isOpen,
  setIsOpen,
  isProcessingReq,
  apiRule,
  createRule,
  updateRule,
}) => {
  const confirm = useConfirm();
  const { rule } = useRule();
  const { validate: validateRule } = useRuleValidation();
  const mutations = useRuleMutations();

  const isEditing = Boolean(apiRule.id);
  const title = `${isEditing ? 'Edit' : 'Create'} ${apiRule.type} rule`;
  const defaultRule = parseShopifyRule(toJSON(apiRule.rule));

  const handleModalDismiss = async () => {
    const didConfirm = await confirm({
      title: 'Are you sure you want to exit?',
      description: 'Conditions configured for the rule may be lost',
      confirmText: 'Exit',
      dismissText: 'Continue configuration',
      confirmColor: 'negative',
    });

    if (didConfirm) {
      mutations.actions.update('params', {});
      mutations.actions.update('type', '');
      setIsOpen(false);
    }
  };

  const handleSubmit = (apiRule: APIRule) => {
    const submitFunc = isEditing ? updateRule : createRule;
    const isValid = validateRule() && Boolean(apiRule.name.trim());

    if (!isValid) {
      return;
    }

    apiRule.rule = toString(formatRule(rule));
    submitFunc(apiRule);
  };

  return (
    <RuleCreator defaultRule={defaultRule} facts={Facts} validator={ruleValidator}>
      <RuleCreatorModal
        title={title}
        apiRule={apiRule}
        isOpen={isOpen}
        isProcessingReq={isProcessingReq}
        onDismiss={handleModalDismiss}
        onSubmit={handleSubmit}
      />
    </RuleCreator>
  );
};

const mapStateToProps = (state) => ({
  magicShippingEngine: state.magicShippingEngine,
});

export default connect(mapStateToProps)(RuleCreatorContainer);
