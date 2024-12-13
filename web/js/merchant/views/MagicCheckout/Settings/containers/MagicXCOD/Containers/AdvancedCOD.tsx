import React, { useEffect, useMemo, useState } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  fetchACODRules,
  addACODRule,
  updateACODRule,
  removeACODRule,
} from 'merchant/reducers/magicCheckout/magicxACODRules/actions';
import { api } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/api';
import { GetStarted } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/GetStarted';
import AdvancedCODTable from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table';
import { createNewRuleState } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/util';
import RuleCreator from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Containers/RuleCreatorContainer';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import { showNotification } from 'merchant_common/reducers/notifications';

import type { Rule, RuleType } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

const AdvancedCOD = ({
  magicxACODRules,
  fetchACODRules,
  removeACODRule,
  addACODRule,
  updateACODRule,
  showNotification,
  merchantId,
}) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [ruleToEdit, setRuleToEdit] = useState<Partial<Rule> | undefined>();
  const [isProcessingReq, setIsProcessingReq] = useState(false);

  const { isLoading, rules, ruleLimits } = magicxACODRules;
  const paymentRules = useMemo(() => {
    return rules.filter((rule) => rule.type === 'payment');
  }, [rules]);
  const shippingRules = useMemo(() => {
    return rules.filter((rule) => rule.type === 'shipping');
  }, [rules]);
  const hasRules = rules.length > 0;
  const isAppUpdateRequired = ruleLimits.shipping === 0 && ruleLimits.payment === 0;

  // helper functions
  const notify = (type, message) =>
    showNotification({ type, message: <DisplayNotificationTxt notificationTxt={message} /> });

  function handleOpenRuleCreator(type: RuleType, rule?: Rule) {
    if (rule) {
      setRuleToEdit(rule);
    } else {
      const isShippingType = type === 'shipping';
      const rulesCount = isShippingType ? shippingRules.length : paymentRules.length;
      setRuleToEdit(createNewRuleState({ rulesCount, type, merchant_id: merchantId }));
    }

    setIsModalOpen(true);
  }

  const formActions = {
    createRule: async (rule: Rule) => {
      setIsProcessingReq(true);

      await api
        .createRule(rule, merchantId)
        .then((res) => {
          if (res.success && res.data?.id) {
            addACODRule(res.data);
            setIsModalOpen(false);
            notify('success', `Successfully created new ${rule.type} rule`);
          }
        })
        .finally(() => {
          setIsProcessingReq(false);
        });
    },
    updateRule: async (rule: Rule) => {
      setIsProcessingReq(true);

      await api
        .updateRule(rule, merchantId)
        .then((res) => {
          if (res.success && res.data?.id) {
            updateACODRule(res.data);
            setIsModalOpen(false);
            notify('success', `Successfully updated ${rule.type} rule ${rule.name}`);
          }
        })
        .finally(() => {
          setIsProcessingReq(false);
        });
    },
  };
  const tableActions = {
    deleteRule: (rule: Rule) => {
      removeACODRule(rule);
      notify('success', `Successfully removed ${rule.type} rule ${rule.name}`);
    },
    makeCreateRule: (type: RuleType) => () => {
      handleOpenRuleCreator(type);
    },
    makeEditRule: (type: RuleType) => (rule?: Rule) => {
      handleOpenRuleCreator(type, rule);
    },
  };

  // effects
  useEffect(() => {
    fetchACODRules();
  }, [fetchACODRules]);

  if (isLoading.rules) {
    return (
      <Box minHeight="spacing.11" display="flex" alignItems="center" justifyContent="center">
        <Spinner
          accessibilityLabel="Loading shipping and payment rules"
          testID="rules-loading-spinner"
        />
      </Box>
    );
  }

  return (
    <>
      <Box paddingY="spacing.8">
        <Box display="flex" gap="spacing.8" flexDirection="column">
          {!hasRules ? (
            <GetStarted
              onCreateRule={handleOpenRuleCreator}
              isAppUpdateRequired={isAppUpdateRequired}
            />
          ) : (
            <>
              <AdvancedCODTable
                type="shipping"
                rules={shippingRules}
                createRule={tableActions.makeCreateRule('shipping')}
                editRule={tableActions.makeEditRule('shipping')}
                deleteRule={tableActions.deleteRule}
                ruleLimit={ruleLimits.shipping}
              />
              <AdvancedCODTable
                type="payment"
                rules={paymentRules}
                createRule={tableActions.makeCreateRule('payment')}
                editRule={tableActions.makeEditRule('payment')}
                deleteRule={tableActions.deleteRule}
                ruleLimit={ruleLimits.payment}
              />
            </>
          )}
        </Box>
      </Box>
      {isModalOpen && (
        <RuleCreator
          isOpen={isModalOpen}
          setIsOpen={setIsModalOpen}
          isProcessingReq={isProcessingReq}
          apiRule={ruleToEdit}
          createRule={formActions.createRule}
          updateRule={formActions.updateRule}
        />
      )}
    </>
  );
};

const mapStateToProps = (state) => ({
  merchantId: state.config?.config?.id || '',
  magicSettings: state.magic_settings,
  magicxACODRules: state.magicxACODRules,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchACODRules,
      removeACODRule,
      addACODRule,
      updateACODRule,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(AdvancedCOD);
