import React, { useEffect } from 'react';
import { Heading, Text, Box, Button, Link, BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import CoinsImg from 'assets/capital/coins.png';
import AbcfImg from 'assets/capital/loans/ABCF.png';
import Card1Img from 'assets/capital/loans/card-1.png';
import Card2Img from 'assets/capital/loans/card-2.png';
import CircleImg from 'assets/capital/loans/circle.png';
import ClockImg from 'assets/capital/loans/clock.png';
import LendingKartImg from 'assets/capital/loans/lendingkart.png';
import ProfectusImg from 'assets/capital/loans/profectus.png';
import StockUpImg from 'assets/capital/loans/stock-up.png';
import TeamImg from 'assets/capital/loans/team.png';
import { connect } from 'react-redux';
import styled, { createGlobalStyle } from 'styled-components';

import { analyticsTrack } from 'common/utils/analytics';

/** Temp workaround to hide footer and padding */
const GlobalStyles = createGlobalStyle`
  .layout.rzp {
    padding-bottom: 0;
    background-color: ${(props) => props.theme.colors.surface.background.gray.subtle};
  }
  .pagefooter {
    display: none !important;
  }
`;

const Img = styled.img`
  width: 100%;
  height: 100%;
`;

const Border = styled.div`
  width: 30px;
  height: 4px;
  background-color: ${(props) => props.theme.colors.interactive.border.positive.default};
`;

const CONTENT_CONFIG = {
  BUSINESS_LOAN: {
    apply: 'https://razorpay.typeform.com/to/kdCTwe1w?typeform-source=pg_dashboard',
    title: 'Business Loans',
    description:
      'Working capital needs solved for our customers. Business financing up to Rs. 2 crores at zero collateral.',
    poweredBy: [
      [LendingKartImg, '12px'],
      [AbcfImg, '24px'],
      [ProfectusImg, '24px'],
    ],
    benefitsTitle: 'Our fast and reliable online process gets you the funds you need to:',
    benefits: [
      {
        icon: TeamImg,
        size: ['44px', '38px'],
        text: 'Expand your team with top talent',
        floatIcon: {
          top: '80%',
          left: '70%',
        },
      },
      {
        icon: ClockImg,
        size: ['34px', '34px'],
        text: 'Meet critical payments on time, every time',
        floatIcon: {
          top: '-16%',
          left: '70%',
          transform: 'rotate(180deg)',
        },
      },
      {
        icon: Card1Img,
        size: ['40px', '38px'],
        text: 'Gain control of your cash flow',
        floatIcon: null,
      },
      {
        icon: StockUpImg,
        size: ['36px', '32px'],
        text: 'Fuel your growth initiatives and reach new heights',
        floatIcon: {
          top: '-30%',
          left: '60%',
          transform: 'scale(2) rotate(45deg)',
        },
      },
    ],
  },
  PERSONAL_LOAN: {
    apply:
      'https://oneapp.abfldirect.com/b2c/login?dsa_hash=a03effa23e4a5bc7c48a68660058cf4b8b93d8d913108ca99f82cb0c4b088485',
    title: 'Insta Loans',
    description: 'Secure instant funds up to Rs. 5,00,000 at zero collateral.',
    poweredBy: [[AbcfImg, '24px']],
    benefitsTitle: 'Unlock instant funds for your needs with Insta Loans:',
    benefits: [
      {
        icon: Card1Img,
        size: ['40px', '38px'],
        text: 'Get flexible repayment options',
        floatIcon: {
          top: '80%',
          left: '70%',
        },
      },
      {
        icon: ClockImg,
        size: ['34px', '34px'],
        text: 'Solve cash flow gaps with ease',
        floatIcon: {
          top: '-16%',
          left: '70%',
          transform: 'rotate(180deg)',
        },
      },
      {
        icon: Card2Img,
        size: ['37px', '36px'],
        text: 'Address unforeseen expenses immediately',
        floatIcon: null,
      },
      {
        icon: StockUpImg,
        size: ['36px', '32px'],
        text: 'Seize new growth opportunities',
        floatIcon: {
          top: '-30%',
          left: '60%',
          transform: 'scale(2) rotate(45deg)',
        },
      },
    ],
  },
} as const;

const BUSINESS_TYPE = {
  INDIVIDUAL: 2,
  UNREGISTERED: 11,
};

const LoansV2 = ({ user }) => {
  const isUnregisteredBusiness =
    user.business_type == BUSINESS_TYPE.INDIVIDUAL ||
    user.business_type == BUSINESS_TYPE.UNREGISTERED;

  useEffect(() => {
    analyticsTrack({
      objectName: 'Screen',
      actionName: 'Rendered',
      screen: 'CAPITAL_LOANS_V2',
      properties: {
        business_type: user.business_type,
      },
    });
  }, []);

  const handleApply = () => {
    analyticsTrack({
      objectName: 'Button',
      actionName: 'Clicked',
      screen: 'CAPITAL_LOANS_V2',
      properties: {
        business_type: user.business_type,
      },
    });
  };

  const content = isUnregisteredBusiness
    ? CONTENT_CONFIG.PERSONAL_LOAN
    : CONTENT_CONFIG.BUSINESS_LOAN;

  return (
    <BladeProvider themeTokens={bladeTheme} colorScheme="dark">
      <GlobalStyles />
      <Box
        backgroundColor="surface.background.gray.subtle"
        paddingY="spacing.11"
        paddingX={{ base: 'spacing.6', m: 'spacing.8' }}
        position="relative"
        overflow="hidden"
      >
        {/* Illustrations section */}
        <Box
          maxWidth="734px"
          opacity={{ base: '0.2', m: '0.8' }}
          position="absolute"
          top="-142px"
          right="0px"
          pointerEvents="none"
        >
          <Img src={CoinsImg} />
        </Box>
        {/* Content section */}
        <Box maxWidth={{ m: '468px' }}>
          <Heading size="2xlarge">{content.title}</Heading>
          <Box
            display="flex"
            flexWrap={{ base: 'wrap', m: 'nowrap' }}
            marginTop="spacing.5"
            marginBottom="spacing.3"
            gap="spacing.5"
          >
            <Box whiteSpace="nowrap">
              <Text marginBottom="spacing.3" color="surface.text.gray.muted" size="medium">
                Powered by
              </Text>
              <Border />
            </Box>
            {content.poweredBy.map((logo, i) => (
              <Box maxWidth="120px" minWidth="100px" maxHeight={logo[1]} key={i}>
                <Img src={logo[0]} />
              </Box>
            ))}
          </Box>
          <Text marginTop="spacing.5" marginBottom="spacing.8" size="large">
            {content.description}
          </Text>
          {/* Apply Now section */}
          <Box display="flex" alignItems="center">
            <Box flexShrink="0">
              <Button
                testID="apply"
                marginRight="spacing.4"
                size="large"
                href={content.apply}
                onClick={handleApply}
              >
                Apply Now!
              </Button>
            </Box>
            <Text display="inline" size="small">
              By applying, you agree to our{' '}
              <Link size="small" href="https://razorpay.com/privacy/capital/" target="_blank">
                privacy policy
              </Link>{' '}
              and{' '}
              <Link size="small" href="https://razorpay.com/terms/capital/" target="_blank">
                terms of use
              </Link>
            </Text>
          </Box>
        </Box>
        {/* Benefits section */}
        <Box marginTop="18vh">
          <Text
            size="large"
            marginBottom="spacing.6"
            color="surface.text.gray.subtle"
            weight="semibold"
          >
            {content.benefitsTitle}
          </Text>
          <Box
            display="grid"
            gridTemplateColumns="repeat(auto-fill, minmax(272px, 1fr))"
            gap="spacing.5"
          >
            {content.benefits.map((benefit, i) => (
              <Box key={i} position="relative">
                {benefit.floatIcon ? (
                  <Box
                    width="59px"
                    height="61px"
                    position="absolute"
                    top={benefit.floatIcon.top}
                    left={benefit.floatIcon.left}
                    transform={benefit.floatIcon.transform}
                    pointerEvents="none"
                  >
                    <Img src={CircleImg} />
                  </Box>
                ) : null}
                <Box
                  position="relative"
                  backgroundColor="surface.background.gray.moderate"
                  borderRadius="xlarge"
                  borderWidth="thin"
                  borderColor="surface.border.gray.muted"
                  padding="spacing.7"
                  height="100%"
                >
                  <Box width={benefit.size[0]} height={benefit.size[0]}>
                    <Img src={benefit.icon} />
                  </Box>
                  <Text marginTop="spacing.4" size="medium" weight="semibold">
                    {benefit.text}
                  </Text>
                </Box>
              </Box>
            ))}
          </Box>
        </Box>
        {/* Footer */}
        <Text marginTop="spacing.11" color="surface.text.gray.muted" variant="caption">
          RTSPL shall act as an LSP/DSA for facilitation of the loan. Loan shall be provided by
          lending partners.
        </Text>
      </Box>
    </BladeProvider>
  );
};

const mapStateToProps = (state: any) => ({
  user: state.session.user,
});
export default connect(mapStateToProps)(LoansV2);
