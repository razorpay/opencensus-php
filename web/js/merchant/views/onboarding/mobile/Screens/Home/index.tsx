import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import AcceptPaymentsCard from 'merchant/views/onboarding/mobile/AcceptPaymentsCard';
import OnboardingCard from 'merchant/views/onboarding/mobile/OnboardingCard';
import { StatusUpdate } from 'merchant/views/onboarding/mobile/UpdateStatus';
import styled from 'styled-components';

const StatusUpdates = styled(View)`
  margin-top: 10px;
`;

export interface IReferee {
  referral_amount: number;
  status: string;
}
export interface HomePropsT {
  referee: IReferee | undefined;
}

const Home: React.FC<HomePropsT> = ({ referee }) => {
  return (
    <View>
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
      <OnboardingCard referee={referee} />
    </View>
  );
};

export default Home;
