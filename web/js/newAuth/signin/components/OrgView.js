import React from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { LinkButton } from '../styles';

const OrgView = ({ orgData }) => {
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
    <View>
      <Space margin={[1, 0, 0, 0]}>
        <Text size="large" color="shade.700" weight="bold">
          Powered by Razorpay and {orgData.businessName}
        </Text>
      </Space>
      <Space margin={[2, 0, 0, 0]}>
        <Text size="medium" color="shade.700" _lineHeight="large">
          This joint initiative between {orgData.businessName} and Razorpay aims to make accepting
          payments a seamless experience for fast-growing businesses.
        </Text>
      </Space>
      <Space margin={[3, 0, 0, 0]}>
        <Text size="medium" color="shade.700">
          Let’s simplify payments together!
        </Text>
      </Space>
      <Space margin={[3, 0, 0, 0]}>
        <Size maxWidth="160px">
          <img src="img/branding/powered-by-razorpay-login.png" alt="Powered by Razorpay" />
        </Size>
      </Space>
      <Space margin={[7, 0]}>
        <Flex>
          <View>
            <Text size="xsmall">Need help?</Text>
            <Space margin={[0, 0.5]}>
              <LinkButton
                as="a"
                href="https://razorpay.com/support/#request/merchant"
                target="_blank"
                onClick={handleContactUsClick}
              >
                <Text size="xsmall" color="primary.900">
                  Contact Us
                </Text>
              </LinkButton>
            </Space>
          </View>
        </Flex>
      </Space>
    </View>
  );
};
export default OrgView;
