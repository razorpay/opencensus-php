import React from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import Button from 'newAuth/@deprecated/common/components/Button';
import {
  HeaderView,
  DesktopOnlyView,
  MobileOnlyView,
  FullHeightFlex,
  CustomSecondaryButton,
} from 'newAuth/commonStyles';

const Header = ({ handleOnClick, isSignUpFromWebsite = false }) => {
  return (
    <>
      <DesktopOnlyView>
        <Flex justifyContent="space-between">
          <HeaderView>
            <Size maxWidth="150px">
              <img src="/img/logo_full.png" alt="Razorpay" />
            </Size>
            <Flex alignItems="center">
              <View>
                <Text color="background.100" weight="bold">
                  Already a user?
                </Text>
                <Space margin={[0, 0, 0, 2.25]}>
                  <Button onClick={handleOnClick}>Log In</Button>
                </Space>
              </View>
            </Flex>
          </HeaderView>
        </Flex>
      </DesktopOnlyView>

      {!isSignUpFromWebsite && (
        <MobileOnlyView>
          <HeaderView>
            <FullHeightFlex justifyContent="space-around" alignItems="center">
              <View>
                <Size maxWidth="120px">
                  <img src="/img/logo_full.png" alt="Razorpay" />
                </Size>
                <Space margin={[3, 0, 0, 8]}>
                  <CustomSecondaryButton size="small" onClick={handleOnClick}>
                    Log in
                  </CustomSecondaryButton>
                </Space>
              </View>
            </FullHeightFlex>
          </HeaderView>
        </MobileOnlyView>
      )}
    </>
  );
};
export default Header;
