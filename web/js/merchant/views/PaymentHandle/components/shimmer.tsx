import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import { ShimmerContainer } from 'merchant/views/PaymentHandle/style';

const Shimmer = (): JSX.Element => {
  return (
    <View>
      <ShimmerContainer size="large" />
      <ShimmerContainer size="medium" />
      <ShimmerContainer size="small" />
    </View>
  );
};

export default Shimmer;
