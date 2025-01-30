import React, { useMemo } from 'react';
import { Box } from '@razorpay/blade/components';

import { GetStarted } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/GetStarted';
import RuleCreatorModal from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal';
import AdvancedCODTable from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table';
import { Facts } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { useACODContext } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/context';
import { ruleValidator } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/util';
import { RuleCreatorEngine } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator';

import type { Rule as ACODRule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

type Props = {
  rules: Array<ACODRule>;
  ruleLimits: {
    shipping: number;
    payment: number;
  };
};
export const AdvancedCOD: React.FC<Props> = ({ rules, ruleLimits }) => {
  const acodCtx = useACODContext();
  const paymentRules = useMemo(() => {
    return rules.filter((rule) => rule.type === 'payment');
  }, [rules]);
  const shippingRules = useMemo(() => {
    return rules.filter((rule) => rule.type === 'shipping');
  }, [rules]);
  const hasRules = rules.length > 0;
  const isAppUpdateRequired = ruleLimits.shipping === 0 && ruleLimits.payment === 0;

  if (!acodCtx) {
    return null;
  }

  return (
    <>
      <Box paddingY="spacing.8">
        <Box display="flex" gap="spacing.8" flexDirection="column">
          {!hasRules ? (
            <GetStarted
              onCreateRule={acodCtx.openModalWithActiveRule}
              isAppUpdateRequired={isAppUpdateRequired}
            />
          ) : (
            <>
              <AdvancedCODTable
                type="shipping"
                rules={shippingRules}
                ruleLimit={ruleLimits.shipping}
              />
              <AdvancedCODTable
                type="payment"
                rules={paymentRules}
                ruleLimit={ruleLimits.payment}
              />
            </>
          )}
        </Box>
      </Box>
      {acodCtx.isModalOpen && (
        <RuleCreatorEngine
          defaultRule={acodCtx.defaultRCERule}
          facts={Facts}
          sizeLimit="40kb"
          validator={ruleValidator}
        >
          <RuleCreatorModal />
        </RuleCreatorEngine>
      )}
    </>
  );
};
