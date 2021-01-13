import React from 'react';
import Text from '@razorpay/blade/src/atoms/Text';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import { useApp } from 'v2/context/App';
import AcceptPaymentsCard from '../../AcceptPaymentsCard';
import OnboardingCard from '../../OnboardingCard';

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
      <OnboardingCard />
    </View>
  );
};

export default Home;
