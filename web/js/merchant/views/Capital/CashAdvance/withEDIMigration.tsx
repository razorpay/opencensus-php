import React, { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import {
  Box,
  BladeProvider,
  Alert,
  Heading,
  Text,
  Button,
  ExternalLinkIcon,
} from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import styled, { createGlobalStyle } from 'styled-components';

import CoinsImg from 'assets/capital/coins.png';
import { NEW_CASH_ADVANCE_DASHBOARD } from 'merchant/views/Capital/CashAdvanceV2/constants';
import { showNotification } from 'merchant_common/reducers/notifications';
import { analyticsTrack } from 'common/utils/analytics';

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

  return <Text color="surface.text.gray.muted">Redirecting in {time} seconds</Text>;
};

const _RedirectToNewDashboard = (props) => {
  const [didStopTimer, setDidStopTimer] = useState(false);

  const handleClick = () => {
    setDidStopTimer(true);
    analyticsTrack({ objectName: 'Button', actionName: 'Clicked', screen: 'LOC_X_MIGRATION' });
  };

  const onTimeout = () => {
    const windowRef = window.open(NEW_CASH_ADVANCE_DASHBOARD, '_blank');
    setDidStopTimer(true);
    if (!windowRef) {
      props.showNotification({
        type: 'neutral',
        message: 'Unable to auto-open new dashboard. Please click on "Go To New Dashboard."',
      });
    }
    if (windowRef) {
      analyticsTrack({ objectName: 'Button', actionName: 'Clicked', screen: 'LOC_X_MIGRATION' });
    }
  };

  return (
    <BladeProvider colorScheme="dark" themeTokens={bladeTheme}>
      <GlobalStyles />
      <Box
        position="relative"
        width="100%"
        minHeight="94vh"
        backgroundColor="surface.background.gray.subtle"
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
        <Heading marginTop="spacing.8" size="xlarge">
          Cash Advance
        </Heading>
        <Text
          marginTop="spacing.3"
          marginBottom="spacing.4"
          variant="caption"
          color="surface.text.gray.muted"
        >
          Facilitated by{' '}
          <Text variant="caption" as="span" weight="semibold" color="surface.text.gray.muted">
            RTSPL
          </Text>
        </Text>
        <CustomDivider />
        <Box marginTop="spacing.6" marginBottom="spacing.10" maxWidth="440px">
          <Text color="surface.text.gray.muted">
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

const CashAdvanceRedirectToX = connect(null, { showNotification })(_RedirectToNewDashboard);

export default CashAdvanceRedirectToX;
