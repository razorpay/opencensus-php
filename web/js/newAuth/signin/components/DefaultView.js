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

  const handleSupercheckoutWebinarButtonCtaClick = () => {
    if (window.rzpQ.push) {
      window.rzpQ.push(
        window.rzpQ.now().onbr().initiated('login.non_login_actions', {
          action: 'click Promotion 2 CTA',
          promotion_title: '1ccSuperCheckout-Webinar-October',
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
            Webinar & 50k Free credits
          </Text>
        </Space>
        <Space margin={[1, 0, 0, 0]}>
          <Text size="medium" color="shade.700">
            Join Razorpay experts to learn how you can offer your customers a world class shopping
            experience and grow your ecommerce business.
          </Text>
        </Space>
        <Space margin={[1, 0, 3, 0]} padding={[0]}>
          <CustomLinkButton
            as="a"
            href="https://lp.razorpay.com/links/ecommerce-business-growth-webinar-0-0-0?__hstc=123703508.fb5e48a6bfc64824f793da2dc9cd78f0.1631528914743.1632898070818.1634728653658.3&__hssc=123703508.1.1634728653658&__hsfp=2491448087"
            target="_blank"
            onClick={handleSupercheckoutWebinarButtonCtaClick}
          >
            Register for free
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
