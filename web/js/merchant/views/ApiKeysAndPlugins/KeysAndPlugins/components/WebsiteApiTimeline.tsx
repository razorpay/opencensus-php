import React, { ReactElement } from 'react';
import { CheckIcon, AlertOnlyIcon, Box, Skeleton, Link } from '@razorpay/blade/components';
import styled from 'styled-components';

import {
  Status as WebsiteStatusEnum,
  NullableStatus as WebsiteStatusT,
  getUnderReviewETA,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils';
import { getHoursOffset } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/utils';

// Using Styled.div to get interactive color theme
const IconBackground = styled.div<{ color: string; colorStatus: string }>`
  height: ${({ theme }) => `${theme?.spacing[5]}px`};
  width: ${({ theme }) => `${theme?.spacing[5]}px`};
  position: relative;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: ${({ theme, color, colorStatus }) =>
    `${theme?.colors?.interactive?.background?.[color]?.[colorStatus]}`};
`;

const getActiveWebsiteStatus = (
  status: WebsiteStatusT,
  handleNavigateToWebsite: (navigateParams?: Record<string, string>) => void,
) => {
  let activeStatusColor: string,
    activeStatusComponent: ReactElement,
    activeStatusColorHighlight = 'default',
    activeStatusIcon = <CheckIcon color="surface.icon.staticWhite.normal" size="small" />;
  switch (status) {
    case WebsiteStatusEnum.Success:
      activeStatusComponent = <>Website review completed.</>;
      activeStatusColor = 'positive';
      break;
    case WebsiteStatusEnum.BvsInProgress:
      activeStatusComponent = <>Website in review. Expect an update within 10mins.</>;
      activeStatusColor = 'notice';
      break;
    case WebsiteStatusEnum.WorkflowInReview:
      activeStatusComponent = (
        <>
          Website in review. Expect an update by{' '}
          {getUnderReviewETA({
            offset: getHoursOffset(48),
          })}
          .
        </>
      );
      activeStatusColor = 'notice';
      break;
    case WebsiteStatusEnum.BvsNeedsClarification:
      activeStatusComponent = (
        <>
          Please update your website details.
          <Link
            variant="button"
            onClick={() => handleNavigateToWebsite({ bvsModalVisible: 'true' })}
          >
            Take Action
          </Link>
        </>
      );
      activeStatusColor = 'negative';
      activeStatusIcon = <AlertOnlyIcon color="surface.icon.staticWhite.normal" size="small" />;
      break;
    case WebsiteStatusEnum.WorkflowNeedsClarification:
      activeStatusComponent = (
        <>
          Website review needs clarification.
          <Link
            variant="button"
            onClick={() => handleNavigateToWebsite({ clarificationModalVisible: 'true' })}
          >
            Resolve now
          </Link>
        </>
      );
      activeStatusColor = 'negative';
      activeStatusIcon = <AlertOnlyIcon color="surface.icon.staticWhite.normal" size="small" />;
      break;
    default:
      activeStatusComponent = (
        <Link variant="button" onClick={() => handleNavigateToWebsite()}>
          Add Website
        </Link>
      );
      activeStatusColor = 'neutral';
      activeStatusColorHighlight = 'fadedHighlighted';
      break;
  }

  return { activeStatusColor, activeStatusComponent, activeStatusColorHighlight, activeStatusIcon };
};

export const WebsiteApiTimeline = ({
  status,
  latestKey,
  handleNavigateToWebsite,
  loading,
}: {
  status: WebsiteStatusT;
  latestKey?: string;
  handleNavigateToWebsite: (navigateParams?: Record<string, string>) => void;
  loading?: boolean;
}) => {
  const { activeStatusColor, activeStatusComponent, activeStatusColorHighlight, activeStatusIcon } =
    getActiveWebsiteStatus(status, handleNavigateToWebsite);

  return (
    <Box flex="1" paddingLeft="spacing.2">
      {loading ? (
        <Box marginY="spacing.4" display="flex" flexDirection="column" gap="spacing.2">
          <Skeleton width="60%" height="24px" borderRadius="medium" />
          <Skeleton width="50%" height="20px" borderRadius="medium" />
        </Box>
      ) : (
        <Box display="flex" flexDirection="column">
          <Box display="flex" flexDirection="column" position="relative" marginTop="30px">
            <Box position="absolute" top="-23px" left="-7px">
              <IconBackground color={activeStatusColor} colorStatus={activeStatusColorHighlight}>
                {activeStatusIcon}
              </IconBackground>
            </Box>
            <Box
              position="absolute"
              paddingLeft="spacing.6"
              top="-26px"
              display="flex"
              flexDirection="row"
              width="100%"
            >
              <Box
                width="100%"
                display="flex"
                flexDirection="row"
                alignItems="center"
                justifyContent="space-between"
                flexWrap="wrap"
              >
                {activeStatusComponent}
              </Box>
            </Box>
            <Box
              minHeight="spacing.6"
              top="spacing.5"
              left="-20px"
              borderLeftColor="surface.border.gray.muted"
              paddingLeft="spacing.6"
            />
          </Box>
          <Box display="flex" flexDirection="column" position="relative" marginTop="30px">
            <Box position="absolute" top="-23px" left="-7px">
              <IconBackground
                color={status === WebsiteStatusEnum.Success && latestKey ? 'positive' : 'neutral'}
                colorStatus={
                  status === WebsiteStatusEnum.Success && latestKey ? 'default' : 'fadedHighlighted'
                }
              >
                <CheckIcon color="surface.icon.staticWhite.normal" size="small" />
              </IconBackground>
            </Box>
            <Box
              position="absolute"
              paddingX="spacing.6"
              top="-26px"
              display="flex"
              flexDirection="row"
              width="100%"
            >
              <Box>Generate Live API keys</Box>
            </Box>
          </Box>
        </Box>
      )}
    </Box>
  );
};

export const WebsiteApiModeSwitchFooter = ({
  switchMode,
}: {
  switchMode: (mode: string) => void;
}) => {
  return (
    <>
      <Box>If your website is not ready, use test mode to continue building payment gateway.</Box>
      <Box>
        <Link variant="button" onClick={() => switchMode('test')}>
          Turn on Test mode
        </Link>
      </Box>
    </>
  );
};
