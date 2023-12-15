import React, { useEffect, useRef, useState } from 'react';
import {
  Box,
  BladeProvider,
  Alert,
  Title,
  Text,
  Button,
  ExternalLinkIcon,
} from '@razorpay/blade/components';
import { bankingTheme } from '@razorpay/blade/tokens';
import styled, { createGlobalStyle } from 'styled-components';

import CoinsImg from 'assets/capital/coins.png';
import { useSplitzService } from 'common/splitz';
import { NEW_CASH_ADVANCE_DASHBOARD } from 'merchant/views/Capital/CashAdvanceV2/constants';

/** Temp workaround to hide footer and padding in capital EDI page */
const GlobalStyles = createGlobalStyle`
  .layout.rzp {
    padding-bottom: 0;
  }
  .pagefooter {
    display: none;
  }
`;

const CustomDivider = styled.div`
  width: 50px;
  height: 4px;
  background-color: #2cca74;
`;

const AutoRedirection = ({ onTimeout }: { onTimeout: VoidFunction }) => {
  const [time, setTime] = useState(5);
  const timerRef = useRef<number>();

  useEffect(() => {
    const timer = window.setInterval(() => {
      setTime((t) => t - 1);
    }, 1000);
    timerRef.current = timer;
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    if (time <= 0) {
      clearInterval(timerRef.current);
      onTimeout();
    }
  }, [time]);

  return <Text type="muted">Redirecting in {time} seconds</Text>;
};

const RedirectToNewDashboard = () => {
  const [didStopTimer, setDidStopTimer] = useState(false);

  const handleClick = () => {
    setDidStopTimer(true);
  };

  const onTimeout = () => {
    window.open(NEW_CASH_ADVANCE_DASHBOARD, '_blank');
    setDidStopTimer(true);
  };

  return (
    <BladeProvider colorScheme="dark" themeTokens={bankingTheme}>
      <GlobalStyles />
      <Box
        position="relative"
        width="100%"
        minHeight="94vh"
        backgroundColor="surface.background.level1.lowContrast"
        padding={{ base: 'spacing.9', m: 'spacing.11' }}
      >
        <Box
          pointerEvents="none"
          position="absolute"
          maxWidth={{ base: '300px', m: '500px' }}
          top="spacing.0"
          right="spacing.0"
        >
          <img src={CoinsImg} width="100%" alt="coin decor" />
        </Box>
        <Alert
          isFullWidth
          isDismissible={false}
          color="information"
          description="Cash Advance has been moved to a new dashboard"
        />
        <Title marginTop="spacing.8" size="large">
          Cash Advance
        </Title>
        <Text marginTop="spacing.3" marginBottom="spacing.4" type="muted" variant="caption">
          Facilitated by{' '}
          <Text type="muted" variant="caption" as="span" weight="bold">
            RTSPL
          </Text>
        </Text>
        <CustomDivider />
        <Box marginTop="spacing.6" marginBottom="spacing.10" maxWidth="440px">
          <Text type="muted">
            Get additional money whenever required, repay and borrow again up to your limit any
            number of times.
          </Text>
        </Box>
        <Button
          href={NEW_CASH_ADVANCE_DASHBOARD}
          onClick={handleClick}
          target="_blank"
          iconPosition="right"
          icon={ExternalLinkIcon}
        >
          Go To New Dashboard
        </Button>
        {!didStopTimer ? (
          <Box marginTop="spacing.4" marginLeft="spacing.7">
            <AutoRedirection onTimeout={onTimeout} />
          </Box>
        ) : null}
      </Box>
    </BladeProvider>
  );
};

export default function withEDIMigration<T extends JSX.IntrinsicAttributes>(
  Component: React.ComponentType<T>,
) {
  return function Validate(props: T) {
    const { abExperiments } = useSplitzService();

    const shouldUseNewDashboard =
      abExperiments?.capital_edi_dashboard_migration?.variables?.result === 'on';

    if (shouldUseNewDashboard) {
      return <RedirectToNewDashboard />;
    }

    return <Component {...props} />;
  };
}
