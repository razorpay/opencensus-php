import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import Card from '../../../../components/Card';

const Container = styled(View)`
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
  border-radius: 2px;
`;

const AcceptPaymentCardShimmer: React.FC = () => {
  return (
    <View role="payment_card">
      <Card padding={[2]}>
        <Size height="20px">
          <Container />
        </Size>
      </Card>
    </View>
  );
};

export default AcceptPaymentCardShimmer;
