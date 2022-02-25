import React, { useState } from 'react';
import styled from 'styled-components';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Link from '@razorpay/commander-shield/src/shared/Link';
import SaveAndExitModal from '../../SaveAndExitModal';
import HeaderBackground from './images/header_background.svg';
import useActivation from '../../hooks/useActivation';
import { checkIfDedupe } from '../../services/utils';
import { useApp } from 'common/context/App';

const StyledActivationProgressHeader = styled(View)`
  box-shadow: 0px 4px 5px rgba(11, 112, 231, 0.05);
  background: url('${HeaderBackground}') right bottom -10px no-repeat;
  background-color: ${({ theme }) => theme.colors.background['200']};
`;
const ActivationProgressHeader: React.FC<RouteComponentProps & { progress: number }> = ({
  progress,
  history,
}) => {
  const [isSaveAndExitModalOpen, setIsSaveAndExitModalOpen] = useState(false);
  const { data } = useActivation();
  const { experiments, submerchantId } = useApp();
  const isDedupe =
    checkIfDedupe({
      ...data,
      isInstantActivationEnabled: experiments.isInstantActivationEnabled,
    }) === 'blocked';

  return (
    <>
      <Space padding={[2, 2.5, 2, 2.5]}>
        <Flex justifyContent="space-between">
          <StyledActivationProgressHeader>
            <View>
              <Text size="large" weight="bold">
                Account Activation
              </Text>
              <Text size="xsmall" weight="bold" color="positive.960">
                {progress}% complete
              </Text>
            </View>
            {!(!data.activation_form_milestone && experiments.isActivationFormFullView) && (
              <Space padding={[0.5, 0]}>
                <Link
                  onClick={() => {
                    if (
                      !data.submitted &&
                      !isDedupe &&
                      (data.poi_verification_status !== 'initiated' ||
                        experiments.isL2AllowedForPoiInitiated)
                    ) {
                      setIsSaveAndExitModalOpen(true);
                    } else if (submerchantId) {
                      history.push('/partners/submerchants');
                    } else {
                      history.push('/dashboard');
                    }
                  }}
                  size="xsmall"
                  weight="bold"
                  color="primary.800"
                >
                  Save and Exit
                </Link>
              </Space>
            )}
          </StyledActivationProgressHeader>
        </Flex>
      </Space>
      <SaveAndExitModal
        isOpen={isSaveAndExitModalOpen}
        onClose={() => setIsSaveAndExitModalOpen(false)}
      />
    </>
  );
};

export default withRouter(ActivationProgressHeader);
