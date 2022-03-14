import React from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { DesktopOnlyView } from '../../commonStyles';
import { BorderView, CustomLink } from '../styles';

const InfoContainer = ({ handleContactUsClick }) => {
  return (
    <DesktopOnlyView>
      <Space padding={[8, 5.5, 4, 0]}>
        <View>
          <Flex flexDirection="column" justifyContent="center" alignItems="center">
            <View>
              <Space margin={[0, 0, 1, 0]}>
                <Text size="large" weight="bold">
                  Why choose Razorpay?
                </Text>
              </Space>
              <Text size="small" color="shade.960">
                50,00,000+ businesses trust their payments with Razorpay
              </Text>
            </View>
          </Flex>
          <Space margin={[3.5, 0, 3.75, 0]}>
            <Size maxWidth="100%">
              <img src="/img/client-logos.png" alt="Clients" />
            </Size>
          </Space>
          <BorderView />
          <Space margin={[2.5, 0, 0, 0]}>
            <Flex justifyContent="center">
              <View>
                <Space margin={[0, 0.5, 0, 0]}>
                  <Text as="span" size="xsmall" color="shade.960">
                    Need help? We are just a click away.
                  </Text>
                </Space>
                <CustomLink
                  size="xsmall"
                  href="https://razorpay.com/support/#request/merchant"
                  target="_blank"
                  rel="noopener noreferrer"
                  onClick={handleContactUsClick}
                >
                  Contact Us
                </CustomLink>
              </View>
            </Flex>
          </Space>
        </View>
      </Space>
    </DesktopOnlyView>
  );
};
export default InfoContainer;
