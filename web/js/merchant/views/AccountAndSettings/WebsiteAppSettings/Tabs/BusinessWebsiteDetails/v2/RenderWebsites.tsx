import React from 'react';
import {
  Box,
  Text,
  Link,
  EditIcon,
  LinkIcon,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import { User } from 'common/typings';

import BusinessWebsiteCard from './components/BusinessWebsiteCard';
import { AddWebsiteClickArgs, WebsiteUpdateApiData, WebsiteUpdateActionOn } from './types';
import { getBusinessWebsitesToShow } from './utils';
import WebsiteCardLoadingState from './components/WebsiteCardLoadingState';

interface RenderWebsitesProps {
  isMobile: boolean;
  user: User;
  onClickAddWebsite: (args: AddWebsiteClickArgs) => void;
  isMainWebsiteEditActionAllowed: boolean;
  businessWebsiteWorkflow: Record<string, any>;
  websiteUpdateData: WebsiteUpdateApiData | undefined;
  ctaDisabledReason: string;
  isWebsiteDetailsFetching: boolean;
}

const RenderWebsites: React.FC<RenderWebsitesProps> = ({
  isMobile,
  user,
  onClickAddWebsite,
  isMainWebsiteEditActionAllowed,
  businessWebsiteWorkflow,
  websiteUpdateData,
  ctaDisabledReason,
  isWebsiteDetailsFetching,
}) => {
  const businessWebsitesToShow = getBusinessWebsitesToShow({
    user,
    websiteUpdateData,
    businessWebsiteWorkflow,
  });

  const EditCta = (
    <Box>
      {isMainWebsiteEditActionAllowed ? (
        <Link
          variant="button"
          onClick={() =>
            onClickAddWebsite({
              actionOn: WebsiteUpdateActionOn.MAIN_WEBSITE,
              isEdit: true,
            })
          }
          icon={EditIcon}
          isDisabled={false}
        >
          Edit
        </Link>
      ) : (
        <Tooltip content={ctaDisabledReason} placement="bottom">
          <TooltipInteractiveWrapper>
            <Link variant="button" icon={EditIcon} isDisabled={true}>
              Edit
            </Link>
          </TooltipInteractiveWrapper>
        </Tooltip>
      )}
    </Box>
  );

  if (isWebsiteDetailsFetching) {
    const cardsNumber = businessWebsitesToShow.length === 0 ? 1 : businessWebsitesToShow.length;
    return <WebsiteCardLoadingState isMobile={isMobile} cardsNumber={cardsNumber} />;
  }

  return businessWebsitesToShow.length === 0 ? (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.5"
      alignItems="center"
      justifyContent="center"
      width="100%"
      minHeight="400px"
    >
      <LinkIcon size="2xlarge" color="surface.icon.gray.muted" />
      <Box>
        <Text size="medium" weight="semibold" color="surface.text.gray.normal" textAlign="center">
          Verified websites/apps appear here
        </Text>
        <Text color="surface.text.gray.muted">
          Our team takes 1-2 days to verify your website/app
        </Text>
      </Box>
    </Box>
  ) : (
    <Box display="flex" flexDirection={isMobile ? 'column' : 'row'} flexWrap="wrap">
      {businessWebsitesToShow.map((websiteData, idx) => (
        <BusinessWebsiteCard
          key={`${websiteData.platform}_${idx}`}
          websiteData={websiteData}
          editCta={websiteData.isPrimary ? EditCta : null}
          isMobile={isMobile}
        />
      ))}
    </Box>
  );
};

export default RenderWebsites;
