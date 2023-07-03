import React, { useEffect, useState } from 'react';
import styled from 'styled-components';
import { Text, Link, Box, Button, Theme } from '@razorpay/blade/components';
import { FadeTransition } from 'common/components/Transition';
import { hideFtux, isVisible } from './ftuxVisibility';
import { STEPS } from './constants';
const { ACCEPTED_INVITES, ALL_INVITES, HIDDEN } = STEPS;

const StyledFtuxTooltip = styled.div(
  ({ theme, tooltipPos }: { theme: Theme; tooltipPos: Array<number> }) => `
    position: absolute;
    z-index: 1;
    left: ${tooltipPos[0]}px;
    top: ${tooltipPos[1]}px;
    width: 240px;
    background-color: ${theme.colors.brand.primary[500]};
    padding: ${theme.spacing[4]}px ${theme.spacing[5]}px;

    &:before {
      position: absolute;
      top: 55px;
      width: 0;
      z-index: 1;
      border-bottom: 10px solid transparent;
      border-left: 0;
      border-right: 15px solid ${theme.colors.brand.primary[500]};
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
  background-color: ${theme.colors.brand.primary[500]};
  color: ${theme.colors.surface.text.normal.lowContrast};
  min-height: ${theme.spacing[6]}px;
  &:hover,&:focus {
    border: ${theme.border.width.thin}px solid white;
    background-color: ${theme.colors.brand.primary[500]};
  }
`,
);

const tooltipPos = {
  [ACCEPTED_INVITES]: [140, -35],
  [ALL_INVITES]: [225, -35],
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
        <Text type="subtle" contrast="high">
          {navlinkStep === ACCEPTED_INVITES ? (
            <Text type="subtle" size="small" contrast="high">
              All Affiliate Accounts that have accepted your invite.{' '}
              <Link
                target="_blank"
                href="https://razorpay.com/docs/partners/resellers/perform-kyc/"
              >
                {/* @ts-ignore Link only accepts string children */}
                <Text weight="bold" size="small" type="subtle" contrast="high">
                  Perform KYC
                </Text>
              </Link>{' '}
              for your sub-merchants and accelerate their onboarding.
            </Text>
          ) : null}
          {navlinkStep === ALL_INVITES ? (
            <Text type="subtle" size="small" contrast="high">
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
      </StyledFtuxTooltip>
    </FadeTransition>
  );
};

export default FtuxTooltip;
