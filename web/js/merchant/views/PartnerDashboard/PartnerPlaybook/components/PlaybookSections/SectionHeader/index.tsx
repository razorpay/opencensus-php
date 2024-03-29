import React from 'react';
import {
  Badge,
  Box,
  CheckCircleIcon,
  HashIcon,
  HeadphonesIcon,
  IconComponent,
  Text,
  Heading,
  TrendingUpIcon,
} from '@razorpay/blade/components';
import styled from 'styled-components';

import { ProgramSection } from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';

// This prevents the heading to scroll up into the common header
const StyledSectionHeader = styled.div`
  scroll-margin-top: 80px;
  scroll-snap-margin-top: 80px; /* iOS 11 and older */
`;

const iconMap: Record<string, IconComponent> = {
  CheckCircleIcon,
  TrendingUpIcon,
  HeadphonesIcon,
};
type SectionHeaderProps = Pick<ProgramSection, 'header'> & {
  isSearchQueryPresent: boolean;
};
const SectionHeader = ({
  header: { hash, icon, count, title, description },
  isSearchQueryPresent,
}: SectionHeaderProps): JSX.Element => {
  const Icon = iconMap[icon] || HashIcon;
  return (
    <Box>
      <Box display="flex" gap="spacing.3" alignItems="center">
        <StyledSectionHeader id={hash}>
          <Heading color="interactive.text.gray.normal" size="xlarge">
            <Icon marginRight="spacing.3" color="interactive.icon.primary.subtle" size="xlarge" />
            {title}
          </Heading>
        </StyledSectionHeader>
        {!isSearchQueryPresent ? (
          <Badge size="large" color="primary">
            {count} {count === 1 ? 'item' : 'items'}
          </Badge>
        ) : null}
      </Box>
      <Text marginTop="spacing.4" size="medium" color="feedback.text.neutral.intense">
        {description}
      </Text>
    </Box>
  );
};

export default SectionHeader;
