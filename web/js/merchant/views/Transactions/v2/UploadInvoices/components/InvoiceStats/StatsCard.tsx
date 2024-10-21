import React from 'react';
import {
  Box,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon,
  Heading,
} from '@razorpay/blade/components';
import { useNavigate, useLocation } from 'react-router-dom';

import { onSearch } from 'merchant/views/Transactions/v2/common/utils';

import { StyledInvoiceStatsCard } from './styled';
import StatsLoader from './StatsLoader';
import { StatsCardProps } from './types';

const StatsCard = ({
  type,
  tooltipText,
  name,
  count = '0',
  isLoading,
}: StatsCardProps): JSX.Element => {
  const location = useLocation();
  const navigate = useNavigate();

  const onCardClick = () => {
    onSearch({ location, replace: navigate })({ [type]: 1 });
  };

  if (isLoading) return <StatsLoader />;
  return (
    <StyledInvoiceStatsCard onClick={onCardClick}>
      <Box display="flex" flexDirection="row" alignItems="center" marginBottom="spacing.5">
        <Text color="surface.text.gray.subtle" marginRight="spacing.2">
          {name}
        </Text>
        <Tooltip content={tooltipText} placement="top">
          <TooltipInteractiveWrapper height="17px">
            <InfoIcon size="small" />
          </TooltipInteractiveWrapper>
        </Tooltip>
      </Box>
      <Heading size="2xlarge">{count}</Heading>
    </StyledInvoiceStatsCard>
  );
};

export default StatsCard;
