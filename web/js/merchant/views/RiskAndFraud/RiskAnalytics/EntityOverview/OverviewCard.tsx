import React, { useState } from 'react';
import {
  Box,
  Text,
  InfoIcon,
  Heading,
  Tooltip,
  BarChartAltIcon,
  TooltipInteractiveWrapper,
  Popover,
} from '@razorpay/blade/components';

import {
  StyledButtonIcon,
  StyledTabCard,
  TooltipWrapper,
  InvisibleButton,
} from 'merchant/views/RiskAndFraud/components/styled';

import PopoverContent from './PopoverContent';
import { OVERVIEW_TABS } from './constants';
import { OverviewCardProps } from './types';
import { getLabelComparision } from './utils';

const OverviewCard: React.FC<OverviewCardProps> = (props) => {
  const { entity, selectedTab, ratios, handleTabChange } = props;
  const isActive = selectedTab === entity;
  const tab = OVERVIEW_TABS[entity];
  const { title, valueKey, comparisionKey, popoverContent } = tab;
  const labelInfo = getLabelComparision(ratios?.[valueKey], ratios?.[comparisionKey]);

  const [isPopoverOpen, setIsPopoverOpen] = useState(false);

  const onClick = (e: React.MouseEvent<HTMLButtonElement>) => {
    setIsPopoverOpen(true);
    e.stopPropagation();
  };

  return (
    <StyledTabCard
      isActive={isActive}
      data-testid={`${entity}-card`}
      aria-selected={isActive ? 'true' : 'false'}
    >
      <Box width="100%" display="flex" flexDirection="column" height="90px">
        <InvisibleButton
          id={entity}
          onClick={handleTabChange}
          role="button"
          aria-roledescription="styled-invisible-button"
          aria-label={`${entity}-button`}
        />
        <Box display="flex" flexDirection="row" alignItems="center" marginBottom="spacing.2">
          <Text
            weight="regular"
            marginY="spacing.2"
            marginRight="spacing.2"
            color="surface.text.gray.subtle"
          >
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
          <Heading size="medium" marginBottom="spacing.4">
            {ratios?.[valueKey] ?? '0.00'}%
          </Heading>
          {ratios?.[valueKey] !== undefined && ratios?.[comparisionKey] !== undefined && (
            <Popover
              isOpen={isPopoverOpen}
              placement="bottom-start"
              onOpenChange={({ isOpen }) => setIsPopoverOpen(isOpen)}
              zIndex={1200}
              content={
                <PopoverContent
                  valueKey={valueKey}
                  actualValue={ratios?.[valueKey]}
                  comparedValue={ratios?.[comparisionKey]}
                />
              }
            >
              <StyledButtonIcon onClick={onClick}>
                <BarChartAltIcon size="medium" color={labelInfo?.iconColor} />
                <Text size="small" color={labelInfo?.textColor} marginLeft="spacing.2">
                  {labelInfo?.label}
                </Text>
              </StyledButtonIcon>
            </Popover>
          )}
        </Box>
      </Box>
    </StyledTabCard>
  );
};

export default OverviewCard;
