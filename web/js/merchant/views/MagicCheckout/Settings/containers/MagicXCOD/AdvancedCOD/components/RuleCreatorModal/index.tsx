import React, { useState, useMemo } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  Button,
  ProgressBar,
  Text,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { convertServerDataToTableData } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/helpers';
import { useRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import { ActionBlock } from './ActionBlock';
import { ConditionBlock } from './ConditionBlock';
import { RuleMeta } from './Meta';

import type { Rule as APIRule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';
import type { ShippingProfile } from 'merchant/reducers/magicCheckout/shippingEngine/types';

type RuleCreatorModalProps = {
  title: string;
  apiRule: APIRule;
  isOpen: boolean;
  isProcessingReq: boolean;
  onDismiss: () => void;
  onSubmit: (rule: APIRule) => void;
  magicShippingEngine: {
    shipping_profiles: Record<string, ShippingProfile>;
    isLoading: { summary: boolean };
  };
};
const RuleCreatorModal: React.FC<RuleCreatorModalProps> = ({
  title,
  isOpen,
  isProcessingReq,
  onDismiss,
  onSubmit,
  apiRule: _apiRule,
  magicShippingEngine: { shipping_profiles },
}) => {
  const [apiRule, setAPIRule] = useState(_apiRule);
  const { ruleSize } = useRule();

  const shippingProfiles = useMemo(() => {
    if (Object.keys(shipping_profiles)?.length) {
      return convertServerDataToTableData(shipping_profiles);
    }
    return [];
  }, [shipping_profiles]);

  const ruleSizeIndicatorColor =
    ruleSize.usage < 70 ? 'information' : ruleSize.usage < 90 ? 'notice' : 'negative';

  // helper methods
  const handleSubmit = () => {
    onSubmit(apiRule);
  };

  const handleAPIRuleChange = (prop: string, value: any) => {
    setAPIRule({ ...apiRule, [prop]: value });
  };

  return (
    <Modal isOpen={isOpen} size="medium" onDismiss={onDismiss}>
      <ModalHeader title={title} />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.8">
          <RuleMeta rule={apiRule} handleChange={handleAPIRuleChange} />
          <ConditionBlock />
          <ActionBlock type={apiRule.type} shippingProfiles={shippingProfiles} />
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="space-between" width="100%">
          <Box display="flex" alignItems="center" justifyContent="center" gap="spacing.4">
            <ProgressBar
              value={ruleSize.usage}
              size="medium"
              variant="circular"
              color={ruleSizeIndicatorColor}
            />
            <Text weight="medium">Allowed Rule Size</Text>
          </Box>
          <Box display="flex" gap="spacing.3" alignItems="center">
            <Button variant="secondary" size="medium" onClick={onDismiss}>
              Cancel
            </Button>
            <Button
              size="medium"
              onClick={handleSubmit}
              isLoading={isProcessingReq}
              isDisabled={ruleSize.usage >= 100 || isProcessingReq}
            >
              Submit
            </Button>
          </Box>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapStateToProps = (state) => ({
  magicShippingEngine: state.magicShippingEngine,
});

export default connect(mapStateToProps)(RuleCreatorModal);
