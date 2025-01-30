import { useMemo } from 'react';

import { api } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/api';
import {
  useACODContext,
  assertACODContext,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/context';
import { toJSON } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/json';
import { parseShopifyRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/parse';
import { useConfirm } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';
import { actionTypesToText } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table/constants';

import type {
  Rule as ACODRule,
  RuleType as ACODRuleType,
} from 'merchant/reducers/magicCheckout/magicxACODRules/types';
import type { TableRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table/types';

export const useACODTable = (type: ACODRuleType, rules: ACODRule[]) => {
  const acodCtx = useACODContext();
  const confirm = useConfirm();

  assertACODContext(acodCtx);

  const nodes: TableRule<typeof type>[] = useMemo(() => {
    return rules.map((rule) => {
      const node = { ...rule, about: '' };
      try {
        const parsedRule = parseShopifyRule(toJSON(rule?.rule));
        const actionType = parsedRule.actions[0].type;
        node.about = actionTypesToText[actionType];
      } catch (_) {
        node.about = '';
      }
      return node;
    });
  }, [rules]);

  const createRule = () => {
    acodCtx.openModalWithActiveRule(type, nodes.length as any);
  };

  const editRule = (rule: ACODRule) => {
    acodCtx.openModalWithActiveRule(type, rule);
  };

  const deleteRule = async (rule: ACODRule) => {
    await confirm.promise(() => api.deleteRule(rule, acodCtx.merchantId), {
      title: `Delete ${rule.name}`,
      description: `Are you sure you want to delete this ${rule.type} rule?`,
      confirmText: 'Delete',
      confirmColor: 'negative',
      dismissText: 'Cancel',
      onSuccess: (res: unknown) => {
        if (res === null || typeof res !== 'object' || !('success' in res)) {
          return;
        }

        if (res?.success === true) {
          acodCtx.storeActions.removeRule(rule);
          acodCtx.notify('success', `Successfully removed ${rule.type} rule ${rule.name}`);
        }
      },
    });
  };

  return {
    nodes,
    createRule,
    editRule,
    deleteRule,
  };
};
