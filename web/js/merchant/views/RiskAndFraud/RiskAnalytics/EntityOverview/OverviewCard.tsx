import React from 'react';
import {
  Box,
  Text,
  InfoIcon,
  Title,
  Tooltip,
  BarChartAltIcon,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import { StyledTabButton, TooltipWrapper } from 'merchant/views/RiskAndFraud/components/styled';

import { OVERVIEW_TABS } from './constants';
import { OverviewCardProps } from './types';
import { getLabelComparision } from './utils';

const OverviewCard: React.FC<OverviewCardProps> = (props) => {
  const { entity, selectedTab, ratios, handleTabChange } = props;
  const tab = OVERVIEW_TABS[entity];
  const { title, valueKey, comparisionKey, popoverContent } = tab;
  const isActive = selectedTab === valueKey;

  const labelInfo = getLabelComparision(ratios?.[valueKey], ratios?.[comparisionKey]);

  return (
    <StyledTabButton
      role="button"
      aria-roledescription="styled-button-card"
      id={valueKey}
      isActive={isActive}
      onClick={handleTabChange}
    >
      <Box width="100%" display="flex" flexDirection="column" height="90px">
        <Box display="flex" flexDirection="row" alignItems="center" marginBottom="spacing.2">
          <Text weight={isActive ? 'bold' : 'regular'} type="subtle" marginY="spacing.2">
            {title}
          </Text>
          <TooltipWrapper>
            <Tooltip content={popoverContent} placement="top">
              <TooltipInteractiveWrapper>
                <InfoIcon size="small" />
              </TooltipInteractiveWrapper>
            </Tooltip>
          </TooltipWrapper>
        </Box>
        <Box display="flex" flexDirection="column" alignItems="flex-start">
          <Title size={isActive ? 'large' : 'medium'} marginBottom="spacing.3">
            {ratios?.[valueKey] ?? 0}%
          </Title>
          {ratios?.[valueKey] !== undefined && ratios?.[comparisionKey] !== undefined && (
            <Box display="flex" flexDirection="row" alignItems="center">
              <BarChartAltIcon size="medium" color={labelInfo?.iconColor} />
              <Text size="small" color={labelInfo?.textColor} marginLeft="spacing.2">
                {labelInfo?.label}
              </Text>
            </Box>
          )}
        </Box>
      </Box>
    </StyledTabButton>
  );
};

export default OverviewCard;
