import React from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { CustomLinkButton, ContactUsLinkButton } from '../styles';

const DefaultView = () => {
  const handleInstantSettlementCtaClick = () => {
    if (window.rzpQ.push) {
      window.rzpQ.push(
        window.rzpQ.now().onbr().initiated('login.non_login_actions', {
          action: 'click Promotion 1 CTA',
          promotion_title: 'instant settlements',
        }),
      );
    }
  };

  const handlePaymentButtonCtaClick = () => {
    if (window.rzpQ.push) {
      window.rzpQ.push(
        window.rzpQ.now().onbr().initiated('login.non_login_actions', {
          action: 'click Promotion 2 CTA',
          promotion_title: 'payment buttons',
        }),
      );
    }
  };

  const handleContactUsClick = () => {
    if (window.rzpQ.push) {
      window.rzpQ.push(
        window.rzpQ.now().onbr().initiated('login.non_login_actions', {
          action: 'click contact us',
        }),
      );
    }
  };

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
              onClick={handleInstantSettlementCtaClick}
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
            onClick={handlePaymentButtonCtaClick}
          >
            Learn More
            <span>→</span>
          </CustomLinkButton>
        </Space>
        <Space margin={[7, 0]}>
          <Flex>
            <View>
              <Text size="xsmall">Need help?</Text>
              <Space margin={[0, 0.5]}>
                <ContactUsLinkButton
                  as="a"
                  href="https://razorpay.com/support/#request/merchant"
                  target="_blank"
                  onClick={handleContactUsClick}
                >
                  <Text size="xsmall" color="primary.900">
                    Contact Us
                  </Text>
                </ContactUsLinkButton>
              </Space>
            </View>
          </Flex>
        </Space>
      </View>
    </Flex>
  );
};
export default DefaultView;
