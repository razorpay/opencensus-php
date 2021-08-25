import React from 'react';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import { useApp } from 'common/context/App';
import AcceptPaymentsCard from '../../AcceptPaymentsCard';
import OnboardingCard from '../../OnboardingCard';
import { StatusUpdate } from '../../UpdateStatus';
import styled from 'styled-components';

const StatusUpdates = styled(View)`
  margin-top: -10px;
`;

const Home: React.FC = () => {
  const { user } = useApp();
  return (
    <View>
      <Space padding={[2.5, 1, 0, 2]}>
        <Text size="large" weight="bold" color="shade.950">
          Welcome to your dashboard, {user.contact_name}!
        </Text>
      </Space>
      <Space margin={[1, 2, 2, 2]}>
        <View>
          <AcceptPaymentsCard />
        </View>
      </Space>
      <Space margin={[1, 2, 2, 2]}>
        <View>
          <StatusUpdates>
            <StatusUpdate />
          </StatusUpdates>
        </View>
      </Space>
      <OnboardingCard />
    </View>
  );
};

export default Home;
