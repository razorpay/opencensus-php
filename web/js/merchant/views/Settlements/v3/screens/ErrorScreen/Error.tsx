import {
  Box,
  Heading,
  Link,
  RotateCounterClockWiseIcon,
  Text,
  XCircleIcon,
} from '@razorpay/blade/components';
import { ERROR_TYPE, ErrorScreenPropsInterface } from 'merchant/views/Settlements/v3/typings';
import React, { useEffect } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { IconWrapper } from './styled';

const ErrorScreen = ({
  history,
  type,
  handleRefresh,
  isDetailsRevampFlow,
}: ErrorScreenPropsInterface): JSX.Element => {
  useEffect(() => {
    analyticsTrackWithUserInfo({
      objectName: 'Settlements Details Page Error Displayed',
      actionName: 'Error Displayed',
      screen: 'Settlements',
      properties: {
        page: 'Details View',
        settlements_experiment_name: 'v2',
        sessionId: window?.session_id ? window.session_id : undefined,
        errorMessage: type,
        isDetailsRevampFlow,
      },
    });
  }, []);

  const handleClick = (): void => {
    if (type === ERROR_TYPE.INVALID_ID) {
      history.push('/settlements');
    } else {
      handleRefresh();
    }
  };

  return (
    <Box
      display="flex"
      alignItems="center"
      justifyContent="center"
      height="360px"
      backgroundColor="surface.background.level2.lowContrast"
    >
      <Box display="flex" flexDirection="column" alignItems="center" gap="spacing.7">
        <IconWrapper>
          <XCircleIcon
            size="2xlarge"
            color="feedback.negative.action.icon.primary.disabled.highContrast"
          />
        </IconWrapper>
        <Box display="flex" flexDirection="column" alignItems="center" gap="spacing.3">
          {type === ERROR_TYPE.INVALID_ID ? (
            <>
              <Heading>Enter valid settlement ID</Heading>
              <Box display="flex" alignItems="center" gap="spacing.2">
                <Link variant="button" onClick={handleClick}>
                  Go back
                </Link>
                <Text type="subtle">to view all settlements</Text>
              </Box>
            </>
          ) : (
            <>
              <Heading>Settlement details could not be loaded</Heading>
              <Box display="flex" alignItems="center" gap="spacing.2">
                <Link
                  variant="button"
                  onClick={handleClick}
                  iconPosition="right"
                  icon={RotateCounterClockWiseIcon}
                >
                  Refresh
                </Link>
                <Text type="subtle">this page or try again later</Text>
              </Box>
            </>
          )}
        </Box>
      </Box>
    </Box>
  );
};

export default withRouter(ErrorScreen);
