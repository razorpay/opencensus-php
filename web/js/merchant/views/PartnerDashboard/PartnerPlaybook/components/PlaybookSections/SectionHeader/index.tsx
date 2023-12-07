import React from 'react';
import {
  Badge,
  Box,
  CheckCircleIcon,
  HashIcon,
  HeadphonesIcon,
  IconComponent,
  Text,
  Title,
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
          <Title color="action.text.tertiary.default" size="large">
            <Icon marginRight="spacing.3" color="action.icon.link.default" size="xlarge" />
            {title}
          </Title>
        </StyledSectionHeader>
        {!isSearchQueryPresent ? (
          <Badge variant="blue" size="large">
            {count} {count === 1 ? 'item' : 'items'}
          </Badge>
        ) : null}
      </Box>
      <Text marginTop="spacing.4" size="medium" color="feedback.text.neutral.lowContrast">
        {description}
      </Text>
    </Box>
  );
};

export default SectionHeader;
