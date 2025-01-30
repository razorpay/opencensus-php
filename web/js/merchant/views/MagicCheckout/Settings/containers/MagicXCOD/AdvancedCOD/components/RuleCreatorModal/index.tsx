import React, { useMemo } from 'react';
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
import { useRCMHandlers, useRCMState } from './hooks';

import type { ShippingProfile } from 'merchant/reducers/magicCheckout/shippingEngine/types';

type RuleCreatorModalProps = {
  magicShippingEngine: {
    shipping_profiles: Record<string, ShippingProfile>;
    isLoading: { summary: boolean };
  };
};
const RuleCreatorModal: React.FC<RuleCreatorModalProps> = ({
  magicShippingEngine: { shipping_profiles },
}) => {
  const rcmState = useRCMState();
  const rcmHandlers = useRCMHandlers();
  const { ruleSize } = useRule();

  const shippingProfiles = useMemo(() => {
    if (Object.keys(shipping_profiles)?.length) {
      return convertServerDataToTableData(shipping_profiles);
    }
    return [];
  }, [shipping_profiles]);

  return (
    <Modal isOpen={rcmState.isModalOpen} size="medium" onDismiss={rcmHandlers.handleDismiss}>
      <ModalHeader title={rcmState.title} />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.8">
          <RuleMeta rule={rcmState.acodRule} handleChange={rcmHandlers.handleMetaPropChange} />
          <ConditionBlock />
          <ActionBlock type={rcmState.acodRule?.type} shippingProfiles={shippingProfiles} />
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="space-between" width="100%">
          <Box display="flex" alignItems="center" justifyContent="center" gap="spacing.4">
            <ProgressBar
              value={ruleSize.usage}
              size="medium"
              variant="circular"
              color={rcmState.ruleSizeIndicatorColor}
            />
            <Text weight="medium">Allowed Rule Size</Text>
          </Box>
          <Box display="flex" gap="spacing.3" alignItems="center">
            <Button variant="secondary" size="medium" onClick={rcmHandlers.handleDismiss}>
              Cancel
            </Button>
            <Button
              size="medium"
              onClick={rcmHandlers.handleSubmit}
              isLoading={rcmState.isProcessingRequest}
              isDisabled={ruleSize.usage >= 100 || rcmState.isProcessingRequest}
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
