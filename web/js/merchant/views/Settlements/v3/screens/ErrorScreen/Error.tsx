import {
  Box,
  Link,
  RotateCounterClockWiseIcon,
  Text,
  XCircleIcon,
} from '@razorpay/blade/components';
import { withRouter } from 'common/deprecated/withRouter';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { ERROR_TYPE, ErrorScreenPropsInterface } from 'merchant/views/Settlements/v3/typings';
import React, { useEffect } from 'react';
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
      backgroundColor="surface.background.gray.intense"
    >
      <Box display="flex" flexDirection="column" alignItems="center" gap="spacing.7">
        <IconWrapper>
          <XCircleIcon size="2xlarge" color="interactive.icon.staticWhite.normal" />
        </IconWrapper>
        <Box display="flex" flexDirection="column" alignItems="center" gap="spacing.3">
          {type === ERROR_TYPE.INVALID_ID ? (
            <>
              <Text size="large">Enter valid settlement ID</Text>
              <Box display="flex" alignItems="center" gap="spacing.2">
                <Link variant="button" onClick={handleClick}>
                  Go back
                </Link>
                <Text color="surface.text.gray.subtle">to view all settlements</Text>
              </Box>
            </>
          ) : (
            <>
              <Text size="large">Settlement details could not be loaded</Text>
              <Box display="flex" alignItems="center" gap="spacing.2">
                <Link
                  variant="button"
                  onClick={handleClick}
                  iconPosition="right"
                  icon={RotateCounterClockWiseIcon}
                >
                  Refresh
                </Link>
                <Text color="surface.text.gray.subtle">this page or try again later</Text>
              </Box>
            </>
          )}
        </Box>
      </Box>
    </Box>
  );
};

export default withRouter(ErrorScreen);
