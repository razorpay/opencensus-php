import React, { useEffect } from 'react';
import Time from 'common/components/Time';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import useActivation from '../hooks/useActivation';
import usePaymentVolume from '../hooks/usePaymentVolume';
import useEscalation from '../hooks/useEscalation';
import { useApp } from 'common/context/App';
import styled from 'styled-components';
import { checkIfDedupe, isUnregisteredBusiness } from '../services/utils';

const StatusIcon = styled(View)`
  position: relative;
  top: 3px;
  margin-right: 1px;
`;

const StatusUpdate: React.FC = () => {
  const { experiments } = useApp();
  const { status: activationQueryStatus, data: activationData } = useActivation();
  const {
    lastUpdated: statusLastUpdated,
    fetchPayment: fetchPaymentInfo,
    transactionAmount: transactionAmountInfo,
  } = usePaymentVolume();
  const { data: escalationsData } = useEscalation();

  useEffect(() => {
    if (activationQueryStatus === 'success' && activationData.activation_form_milestone === 'L1') {
      fetchPaymentInfo();
    }
  }, [activationQueryStatus]);
  const isError = activationQueryStatus === 'error';
  if (isError) {
    return null;
  }
  const isLimitReached =
    escalationsData && escalationsData?.amount >= escalationsData?.limit?.payment;

  const isLatestTransaction = escalationsData?.amount > transactionAmountInfo;

  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;

  const isDedupe = checkIfDedupe({ ...activationData, isInstantActivationEnabled }) === 'blocked';

  if (!activationData) {
    return null;
  }

  if (
    (statusLastUpdated || escalationsData?.updated_at) &&
    (activationData.activated || isLimitReached) &&
    activationData.activation_form_milestone === 'L1' &&
    (activationData.activation_flow === 'whitelist' ||
      isUnregisteredBusiness(activationData.business_type)) &&
    !isDedupe &&
    isInstantActivationEnabled &&
    escalationsData
  ) {
    return (
      <Flex alignItems="flex-start">
        <View>
          <Text size="small" color="shade.960">
            <StatusIcon as="span">
              <Icon name="info" fill="shade.960" size="small" />
            </StatusIcon>
            <Space padding={[0, 0, 0, 0.5]}>
              <span>
                Payment volume last updated{' '}
                <Time
                  value={isLatestTransaction ? escalationsData?.updated_at : statusLastUpdated}
                  relative
                />
              </span>
            </Space>
          </Text>
        </View>
      </Flex>
    );
  }
  return null;
};

export default StatusUpdate;
