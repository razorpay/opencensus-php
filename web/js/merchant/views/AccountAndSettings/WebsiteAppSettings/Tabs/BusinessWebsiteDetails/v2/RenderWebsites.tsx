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
import EmptyStateIllustration from './components/assets/EmptyStateIllusration.svg';

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
  const isKLA = !user.has_key_access;
  const businessWebsitesToShow = getBusinessWebsitesToShow({
    user,
    websiteUpdateData,
    businessWebsiteWorkflow,
    isKLA,
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
      alignItems="center"
      justifyContent="center"
      width="100%"
      minHeight="400px"
    >
      <Box>
        <img src={EmptyStateIllustration} />
      </Box>
      <Box>
        <Text size="medium" weight="semibold" color="surface.text.gray.normal" textAlign="center">
          Your websites and apps linked to Razorpay appear here
        </Text>
        <Text color="surface.text.gray.muted" textAlign="center">
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
