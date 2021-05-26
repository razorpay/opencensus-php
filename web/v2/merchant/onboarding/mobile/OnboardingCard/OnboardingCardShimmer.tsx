import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Button from '@razorpay/blade-old/src/atoms/Button';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import Card from '../../../../components/Card';
import FormIcon from './Icons/FormIcon.svg';

const Separator = styled(View)`
  height: 1px;
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
  margin: 20px 0 28px;
`;

const HeadingContainer = styled(View)`
  width: 100%;
`;

const Container = styled(View)`
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
  border-radius: 2px;
`;

const OnboardingCardShimmer: React.FC = () => {
  return (
    <View role="shimmer">
      <Card padding={[2]} margin={[2]}>
        <Flex flexDirection="row" justifyContent="space-between">
          <View>
            <HeadingContainer>
              <Space margin={[0, 0, 0.5, 0]}>
                <HeadingContainer>
                  <Text size="large" weight="bold">
                    Activate Details
                  </Text>
                </HeadingContainer>
              </Space>
              <Space margin={[0, 2.5, 0, 0]}>
                <Text size="xsmall" color="shade.950">
                  Provide following details to start your activation process.
                </Text>
              </Space>
            </HeadingContainer>
            <img src={FormIcon} alt="fill_activation_form_icon" />
          </View>
        </Flex>
        <Separator />
        <View>
          <Space margin={[0, 0, 1.375, 0]}>
            <Size height="8px" width="48%">
              <Container />
            </Size>
          </Space>
          <Size height="20px">
            <Container />
          </Size>
          <br />
          {/* <Space margin={[1.5, 0, 1.375, 0]}>
            <Size height="8px" width="48%">
              <Container />
            </Size>
          </Space>
          <Size height="20px">
            <Container />
          </Size> */}
          <br />
          <Space margin={[2.5, 0, 0, 0]}>
            <View>
              <Button block size="large" disabled={true}>
                Start Activation
              </Button>
            </View>
          </Space>
        </View>
      </Card>
    </View>
  );
};

export default OnboardingCardShimmer;
