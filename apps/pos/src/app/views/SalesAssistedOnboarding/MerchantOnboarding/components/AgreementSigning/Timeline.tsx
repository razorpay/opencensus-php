import React from 'react';
import { Badge, Box, CopyIcon, Heading, Link, Text } from '@razorpay/blade/components';
import { TimelineItem, TimelineStepStatus } from 'apps/pos/src/app/types/AgreementSigning';
import {
  StyledIcon,
  StyledTimelineIcon,
  StyledTimelineItemWrapper,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/styles';
import fileIcon from 'apps/pos/src/assets/Filemark.svg';
import checkIcon from 'apps/pos/src/assets/Checkmark.svg';
import clockIcon from 'apps/pos/src/assets/Clockmark.svg';
import { COMPLETED, IN_PROGRESS, NOT_STARTED } from 'apps/pos/src/app/utils/agreementSigning';
interface TimelineProps {
  data: TimelineItem[];
  copyLink?: () => void;
}

//TODO: Use Blade's StepGroup component whenver Blade version is upgraded to atleast v11.15
const Timeline = ({ data, copyLink }: TimelineProps) => {
  const getStepIcon = (status: TimelineStepStatus | undefined) => {
    switch (status) {
      case COMPLETED:
        return <StyledIcon src={checkIcon} />;
      case IN_PROGRESS:
        return <StyledIcon src={clockIcon} />;
      case NOT_STARTED:
        return <StyledIcon src={fileIcon} />;
      default:
        return null;
    }
  };
  return (
    <Box marginTop="spacing.5">
      {data.map((item) => (
        <StyledTimelineItemWrapper status={item.status} key={item.heading}>
          <StyledTimelineIcon status={item.status}>{getStepIcon(item.status)}</StyledTimelineIcon>
          <Box width="fit-content" paddingBottom="spacing.8">
            <Heading marginBottom="spacing.2" as="h3" color="surface.text.gray.subtle">
              {item.heading}
            </Heading>
            {item.badge ? (
              <Box marginTop="spacing.4" marginBottom="spacing.3" width="fit-content">
                <Badge color={item.badge.mood} icon={item.badge.icon}>
                  {item.badge.text ?? ''}
                </Badge>
              </Box>
            ) : null}
            <Text variant="caption" color="surface.text.gray.muted" size="small">
              {item.date ?? ''}
            </Text>
            {item.showCopyLink ? (
              <Box marginTop="spacing.4">
                <Link onClick={copyLink} icon={CopyIcon} iconPosition="left" variant="button">
                  Copy link
                </Link>
              </Box>
            ) : null}
          </Box>
        </StyledTimelineItemWrapper>
      ))}
    </Box>
  );
};

export default Timeline;
