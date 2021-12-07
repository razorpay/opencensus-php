import React from 'react';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import {
  HeaderView,
  DesktopOnlyView,
  MobileOnlyView,
  CustomSecondaryButton,
  FullHeightFlex,
} from '../../commonStyles';

const Logo = ({ src, width, height }) => {
  return (
    <div style={{ width, height }}>
      <img style={{ width: '100%' }} src={src} alt="Company Logo" />
    </div>
  );
};

const Header = ({ handleOnClick, orgData }) => {
  const SignupButtonDesktopView = (
    <Flex alignItems="center">
      <View>
        <Text color="background.100" size="medium">
          New to Razorpay?
        </Text>
        <Space margin={[0, 0, 0, 2.25]}>
          <CustomSecondaryButton onClick={handleOnClick}>Sign Up</CustomSecondaryButton>
        </Space>
      </View>
    </Flex>
  );

  const SignupButtonMobileView = (
    <Space margin={[3, 0, 0, 8]}>
      <CustomSecondaryButton size="small" onClick={handleOnClick}>
        Sign up
      </CustomSecondaryButton>
    </Space>
  );

  return (
    <>
      <DesktopOnlyView>
        <Flex justifyContent="space-between">
          <HeaderView>
            <Logo src={orgData.logo} width={150} height={40} />
            {orgData.isSignupAllowed && SignupButtonDesktopView}
          </HeaderView>
        </Flex>
      </DesktopOnlyView>

      <MobileOnlyView>
        <HeaderView>
          <FullHeightFlex justifyContent="space-around" alignItems="center">
            <View>
              <Logo src={orgData.logo} width={120} height={55} />
              {orgData.isSignupAllowed && SignupButtonMobileView}
            </View>
          </FullHeightFlex>
        </HeaderView>
      </MobileOnlyView>
    </>
  );
};
export default Header;
