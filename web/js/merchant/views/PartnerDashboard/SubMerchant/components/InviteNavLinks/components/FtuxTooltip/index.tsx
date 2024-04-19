import React, { useEffect, useState } from 'react';
import { Text, Link, Box, Button, Theme, BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import styled from 'styled-components';

import { FadeTransition } from 'common/components/Transition';
import { PARTNERSHIPS_WEBSITE_LINKS } from 'merchant/views/PartnerDashboard/constants';

import { STEPS } from './constants';
import { hideFtux, isVisible } from './ftuxVisibility';
const { ACCEPTED_INVITES, ALL_INVITES, HIDDEN } = STEPS;

const StyledFtuxTooltip = styled.div(
  ({ theme, tooltipPos }: { theme: Theme; tooltipPos: Array<number> }) => `
    position: absolute;
    z-index: 1;
    left: ${tooltipPos[0]}px;
    top: ${tooltipPos[1]}px;
    width: 240px;
    background-color: ${theme.colors.surface.background.primary.intense};
    padding: ${theme.spacing[4]}px ${theme.spacing[5]}px;

    &:before {
      position: absolute;
      top: 55px;
      width: 0;
      z-index: 1;
      border-bottom: 10px solid transparent;
      border-left: 0;
      border-right: 15px solid ${theme.colors.surface.background.primary.intense};
      border-style: solid;
      border-top: 10px solid transparent;
      content: '';
      height: 0;
      left: -10px;
    }
  `,
);

const FtuxAction = styled(Button)(
  ({ theme }) => `
  border: ${theme.border.width.thin}px solid white;
  background-color: ${theme.colors.surface.background.primary.intense};
  color: ${theme.colors.surface.text.gray.subtle};
  min-height: ${theme.spacing[6]}px;
  &:hover,&:focus {
    border: ${theme.border.width.thin}px solid white;
    background-color: ${theme.colors.surface.background.primary.intense};
  }
`,
);

const tooltipPos = {
  [ACCEPTED_INVITES]: [150, -35],
  [ALL_INVITES]: [235, -35],
};

const FtuxTooltip = (): JSX.Element | null => {
  const [navlinkStep, setNavlinkStep] = useState<string>(HIDDEN);

  const handleClick = () => {
    if (navlinkStep === ACCEPTED_INVITES) {
      setNavlinkStep(ALL_INVITES);
    } else {
      hideFtux();
      setNavlinkStep(HIDDEN);
    }
  };

  useEffect(() => {
    const isShowFTUX = isVisible();
    if (isShowFTUX) setNavlinkStep(ACCEPTED_INVITES);
  }, []);

  if (navlinkStep === HIDDEN) {
    return null;
  }

  return (
    <FadeTransition duration={500} in appear>
      <StyledFtuxTooltip tooltipPos={tooltipPos[navlinkStep]}>
        <BladeProvider colorScheme="dark" themeTokens={bladeTheme}>
          <Text color="surface.text.gray.subtle">
            {navlinkStep === ACCEPTED_INVITES ? (
              <Text size="small" color="surface.text.gray.subtle">
                All Affiliate Accounts that have accepted your invite.{' '}
                <Link target="_blank" href={PARTNERSHIPS_WEBSITE_LINKS.PERFORM_KYC_DOCS_LINK}>
                  {/* eslint-disable-next-line @typescript-eslint/ban-ts-comment */}
                  {/* @ts-ignore Link only accepts string children */}
                  <Text weight="semibold" size="small" color="surface.text.gray.subtle">
                    Perform KYC
                  </Text>
                </Link>{' '}
                for your sub-merchants and accelerate their onboarding.
              </Text>
            ) : null}
            {navlinkStep === ALL_INVITES ? (
              <Text size="small" color="surface.text.gray.subtle">
                List of all invites you have sent out. Switch to Accepted Invites tab for Affiliates
                that have accepted your invite.
              </Text>
            ) : null}
          </Text>
          <Box marginTop="spacing.4">
            <FtuxAction onClick={handleClick} variant="primary" size="xsmall">
              GOT IT
            </FtuxAction>
          </Box>
        </BladeProvider>
      </StyledFtuxTooltip>
    </FadeTransition>
  );
};

export default FtuxTooltip;
