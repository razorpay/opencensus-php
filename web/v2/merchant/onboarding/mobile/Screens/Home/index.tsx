import React from 'react';
import Text from '@razorpay/blade/src/atoms/Text';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import AcceptPaymentsCard from '../../AcceptPaymentsCard';
import OnboardingCard from '../../OnboardingCard';
import useActivation from '../../hooks/useActivation';

const Home: React.FC = () => {
  const { status, data } = useActivation();
  if (status === 'loading') {
    return <View>Loading...</View>;
  }

  if (status === 'error') {
    return <View>Oops! Something went wrong.</View>;
  }

  return (
    <View>
      <Space margin={[0, 0, 1, 0]}>
        <Text size="large" weight="bold" color="shade.950">
          Welcome to your dashboard, {data.contact_name}!
        </Text>
      </Space>
      <Space margin={[0, 0, 2, 0]}>
        <View>
          <AcceptPaymentsCard />
        </View>
      </Space>
      <OnboardingCard />
    </View>
  );
};

export default Home;
