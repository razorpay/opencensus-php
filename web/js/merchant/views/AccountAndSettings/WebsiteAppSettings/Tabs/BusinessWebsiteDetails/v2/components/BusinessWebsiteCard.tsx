import React from 'react';
import {
  Box,
  Text,
  Badge,
  GlobeIcon,
  TabletIcon,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import DotSeparator from './DotSeparator';
import { badgeColorMap, isAppstorePlaystoreUrl } from './utils';
import { BusinessWebsiteCardData } from '../types';

interface BusinessWebsiteCardProps {
  websiteData: BusinessWebsiteCardData;
  editCta?: React.ReactNode;
  isMobile: boolean;
}

const BusinessWebsiteCard: React.FC<BusinessWebsiteCardProps> = ({
  websiteData,
  editCta,
  isMobile,
}) => {
  const { isPrimary, platform, status, url } = websiteData;
  return (
    <Box
      marginRight="spacing.7"
      marginBottom="spacing.7"
      borderWidth="thin"
      borderColor="surface.border.gray.subtle"
      padding="spacing.4"
      width={isMobile ? '100%' : 'min(362px, 100%)'}
      borderRadius="medium"
    >
      <Box display="flex" justifyContent="space-between" marginBottom="spacing.5">
        <Box
          backgroundColor="surface.background.gray.subtle"
          padding="spacing.3"
          display="flex"
          justifyContent="center"
          alignItems="center"
          borderRadius="medium"
        >
          {isAppstorePlaystoreUrl(url) ? (
            <TabletIcon color="surface.icon.gray.normal" size="medium" />
          ) : (
            <GlobeIcon color="surface.icon.gray.normal" size="medium" />
          )}
        </Box>
        {editCta}
      </Box>
      <Text marginBottom="spacing.3" weight="semibold" size="medium" wordBreak="break-all">
        {url}
      </Text>
      <Box display="flex" gap="spacing.2" alignItems="center">
        {isPrimary ? (
          <Tooltip
            title="Primary"
            content="This is a verified website/app integrated with Payment Gateway and can be edited by you"
            placement="bottom"
          >
            <TooltipInteractiveWrapper>
              <Badge color={badgeColorMap[platform]} size="large">
                Primary {platform}
              </Badge>
            </TooltipInteractiveWrapper>
          </Tooltip>
        ) : (
          <Badge color={badgeColorMap[platform]} size="large">
            {platform}
          </Badge>
        )}
        <DotSeparator />
        <Badge color={badgeColorMap[status]} size="large">
          {status}
        </Badge>
      </Box>
    </Box>
  );
};

export default BusinessWebsiteCard;
