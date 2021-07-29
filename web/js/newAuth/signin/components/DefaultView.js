import React from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { CustomLinkButton } from '../styles';

const DefaultView = () => {
  return (
    <Flex flexDirection="column">
      <View>
        <View>
          <Space margin={[0, 0, 1, 0]}>
            <Size maxWidth="400px">
              <img src="img/is-login-card.svg" alt="Razorpay" />
            </Size>
          </Space>
          <Space margin={[1, 0, 1, 0]}>
            <Text size="medium" color="shade.700">
              With Settle Now button on your dashboard, transfer your customer payments into your
              account in 10 seconds!
            </Text>
          </Space>
          <Space margin={[1, 0, 3, 0]} padding={[0]}>
            <CustomLinkButton
              as="a"
              href="https://razorpay.com/capital/instant-settlements/?ref=login-cards"
              target="_blank"
            >
              Learn More
              <span>→</span>
            </CustomLinkButton>
          </Space>
        </View>
        <Space margin={[3.5, 0, 0, 0]}>
          <Text size="large" color="shade.700" weight="bold" align="left">
            Payment Buttons
          </Text>
        </Space>
        <Space margin={[1, 0, 0, 0]}>
          <Text size="medium" color="shade.700">
            Start accepting payments on your website or blog in less than 5 minutes. No coding
            needed.
          </Text>
        </Space>
        <Space margin={[1, 0, 3, 0]} padding={[0]}>
          <CustomLinkButton
            as="a"
            href="https://razorpay.com/payment-buttons/?utm_source=signup&utm_medium=banner&utm_campaign=paymentbuttons"
            target="_blank"
          >
            Learn More
            <span>→</span>
          </CustomLinkButton>
        </Space>
      </View>
    </Flex>
  );
};
export default DefaultView;
