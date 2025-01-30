import {
  useACODContext,
  assertACODContext,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/context';
import {
  useRule,
  useRuleValidation,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';
import { formatRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/formatRule';
import { toString } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/json';
import { useConfirm } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';
import { MODAL_DISMISS_CONFIRM_OPTIONS } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/constants';

import type { Rule as ACODRule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';
import type {
  UseRCMState,
  UseRCMHandlers,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/types';

export const useRCMState: UseRCMState = () => {
  const acodCtx = useACODContext();
  const { ruleSize } = useRule();

  assertACODContext(acodCtx);

  const { activeACODRule } = acodCtx;
  const isEditMode = Boolean(activeACODRule?.id);
  const title = `${isEditMode ? 'Edit' : 'Create'} ${activeACODRule?.type} rule`;
  const ruleSizeIndicatorColor =
    ruleSize.usage < 70 ? 'information' : ruleSize.usage < 90 ? 'notice' : 'negative';

  return {
    title,
    ruleSizeIndicatorColor,
    acodRule: activeACODRule as ACODRule,
    isModalOpen: acodCtx?.isModalOpen,
    isProcessingRequest: acodCtx?.isProcessingRequest,
  };
};

export const useRCMHandlers: UseRCMHandlers = () => {
  const acodCtx = useACODContext();
  const confirm = useConfirm();
  const { rule } = useRule();
  const { validate: validateRule } = useRuleValidation();

  assertACODContext(acodCtx);

  const { activeACODRule, formActions, setIsModalOpen, updateActiveACODRule } = acodCtx;
  const isEditMode = Boolean(activeACODRule?.id);

  // handlers
  const handleDismiss = async () => {
    const didConfirm = await confirm(MODAL_DISMISS_CONFIRM_OPTIONS);

    if (didConfirm) {
      setIsModalOpen(false);
      acodCtx.resetActiveACODRule();
    }
  };

  const handleSubmit = () => {
    const acodRule = activeACODRule;

    if (!acodRule) return;

    const onSubmit = isEditMode ? formActions.updateRule : formActions.createRule;
    const isValid = validateRule() && Boolean(acodRule.name.trim());

    if (!isValid) {
      return;
    }

    acodRule.rule = toString(formatRule(rule));
    onSubmit(acodRule as ACODRule);
  };

  const handleMetaPropChange = (prop: 'name' | 'description', value: string) => {
    updateActiveACODRule({ [prop]: value });
  };

  return {
    handleDismiss,
    handleSubmit,
    handleMetaPropChange,
  };
};
