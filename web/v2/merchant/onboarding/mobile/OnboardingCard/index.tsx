import React from 'react';
import styled from 'styled-components';
import { useQuery } from 'react-query';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Text from '@razorpay/blade/src/atoms/Text';
import Space from '@razorpay/blade/src/atoms/Space';
import { fetch } from 'v2/services/rest/rest-fetch';
import { CenterLoader } from 'v2/components/Loader';
import Card from '../../../../components/Card';
import useActivation from '../hooks/useActivation';
import BusinessModelDetails from './BusinessModelDetails';
import CurrentActivationProgress from './CurrentActivationProgress';
import FormIcon from './Icons/FormIcon.svg';

const fetchPayments = async () => {
  const data = await fetch<any>({ url: '/payments' });
  return data;
};

const Separator = styled(View)`
  border: 1px solid rgba(224, 228, 249, 0.38);
  margin: ${(props) => (props.$onboardingMilestone === null ? '16px 0 24px 0' : '16px 0')};
`;

const OnboardingCard: React.FC = () => {
  const { status: activationQueryStatus, data: activationData } = useActivation();
  const { status: paymentsQueryStatus, data: paymentsData } = useQuery('payments', fetchPayments);

  if (activationQueryStatus === 'loading' || paymentsQueryStatus === 'loading') {
    return <CenterLoader />;
  }

  if (activationQueryStatus === 'error' || paymentsQueryStatus === 'error') {
    return <div>Something went wrong</div>;
  }

  return (
    <View>
      <Card padding={[2]} margin={[2]}>
        <Flex>
          <View>
            <View>
              <Space margin={[0, 0, 0.5, 0]}>
                <Text size="large" weight="bold">
                  Activate Your Account
                </Text>
              </Space>
              <Text size="xsmall" color="shade.950">
                Provide following details to start your activation process.
              </Text>
            </View>
            <img src={FormIcon} alt="fill_activation_form_icon" />
          </View>
        </Flex>

        <Separator $onboardingMilestone={activationData.onboarding_milestone} />

        <BusinessModelDetails />

        <CurrentActivationProgress data={activationData} payments={paymentsData} />
      </Card>
    </View>
  );
};

export default OnboardingCard;
