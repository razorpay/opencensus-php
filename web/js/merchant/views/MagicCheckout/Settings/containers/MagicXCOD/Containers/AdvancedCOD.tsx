import React, { useEffect } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import {
  fetchACODRules,
  addACODRule,
  updateACODRule,
  removeACODRule,
} from 'merchant/reducers/magicCheckout/magicxACODRules/actions';
import { AdvancedCOD } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/AdvancedCOD';
import { ACODProvider } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/context';

import type { Rule as ACODRule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

const ACODContainer = ({ magicxACODRules, fetchACODRules, storeActions, notify, merchantId }) => {
  const { isLoading, rules, ruleLimits } = magicxACODRules;
  const acodProviderProps = {
    merchantId,
    notify,
    storeActions,
  };

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
    <ACODProvider {...acodProviderProps}>
      <AdvancedCOD rules={rules} ruleLimits={ruleLimits} />
    </ACODProvider>
  );
};

const mapStateToProps = (state) => ({
  merchantId: state.config?.config?.id || '',
  magicSettings: state.magic_settings,
  magicxACODRules: state.magicxACODRules,
});

const mapDispatchToProps = (dispatch) => ({
  storeActions: {
    addRule: (rule: ACODRule) => dispatch(addACODRule(rule)),
    updateRule: (rule: ACODRule) => dispatch(updateACODRule(rule)),
    removeRule: (rule: ACODRule) => dispatch(removeACODRule(rule)),
  },
  fetchACODRules: () => dispatch(fetchACODRules()),
});

export default connect(mapStateToProps, mapDispatchToProps)(ACODContainer);
