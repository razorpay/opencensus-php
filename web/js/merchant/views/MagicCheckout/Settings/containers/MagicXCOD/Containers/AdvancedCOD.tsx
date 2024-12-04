import React, { useEffect, useMemo } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  fetchACODRules,
  removeACODRule,
} from 'merchant/reducers/magicCheckout/magicxACODRules/actions';
import { GetStarted } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/GetStarted';
import { AdvancedCODTable } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table';

const AdvancedCOD = ({ magicxACODRules, fetchACODRules, removeACODRule }) => {
  const { isLoading, rules } = magicxACODRules;
  const paymentRules = useMemo(() => {
    return rules.filter((rule) => rule.type === 'payment');
  }, [rules]);
  const shippingRules = useMemo(() => {
    return rules.filter((rule) => rule.type === 'shipping');
  }, [rules]);
  const hasRules = rules.length > 0;

  /**
   * TODO: the implementations here are left pending
   * as backend needs to decide on implementation for rules
   * and the API contracts.
   */
  const createRule = () => {};
  const editRule = () => {};
  const deleteRule = removeACODRule;
  const tableActions = { createRule, editRule, deleteRule };

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
    <Box paddingY="spacing.8">
      <Box display="flex" gap="spacing.8" flexDirection="column">
        {!hasRules ? (
          <GetStarted onCreatePaymentRule={createRule} onCreateShippingRule={createRule} />
        ) : (
          <>
            <AdvancedCODTable type="shipping" rules={shippingRules} {...tableActions} />
            <AdvancedCODTable type="payment" rules={paymentRules} {...tableActions} />
          </>
        )}
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  magicSettings: state.magic_settings,
  magicxACODRules: state.magicxACODRules,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchACODRules,
      removeACODRule,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(AdvancedCOD);
