import React from 'react';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { LinkButton } from '../styles';
import { LoginCardArray } from '../data';
import LoginCard from './LoginCard';

const DefaultView = () => {
  const handleContactUsClick = () => {
    if (window.rzpQ.push) {
      window.rzpQ.push(
        window.rzpQ.now().onbr().initiated('login.non_login_actions', {
          action: 'click contact us',
        }),
      );
    }
  };

  const LoginCards = LoginCardArray.map((cardData, index) => (
    <LoginCard cardData={cardData} key={cardData.id} cardOrder={index + 1} />
  ));

  return (
    <Flex flexDirection="column">
      <View>
        {LoginCards}
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
    </Flex>
  );
};
export default DefaultView;
