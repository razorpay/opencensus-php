import React from 'react';
import { Box, Text, BankIcon } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import {
  Topbar,
  Logo,
  MerchantIconWrapper,
} from 'merchant/views/OnboardingConfigs/components/Header/styled';

const Header = ({ user }) => {
  const { merchants, current } = user;
  const merchant = merchants?.[current];
  return (
    <Topbar>
      <Logo
        src="https://cdn.razorpay.com/logo.svg"
        role="img"
        aria-label="brand-logo"
        alt="brand-logo"
      />
      <Box display="flex" alignItems="center" paddingLeft="spacing.1">
        <MerchantIconWrapper>
          <BankIcon size="large" color="surface.icon.gray.subtle" />
        </MerchantIconWrapper>
        <Box paddingRight="spacing.6">
          <Text as="p" size="large" variant="body" weight="regular">
            {merchant?.name}
          </Text>
        </Box>
      </Box>
    </Topbar>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state?.session?.user,
  };
};

export default connect(mapStateToProps, null)(Header);
