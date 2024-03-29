import React, { useState } from 'react';
import { Box, ChevronDownIcon, ChevronUpIcon, Text } from '@razorpay/blade/components';

import { useBladeBreakpoints } from 'merchant/views/POS/hooks';

import { OrderCollapsibleHeader, OrderCollapsibleIconContainer, StyledCollapsible } from './styles';

type OrderCollapsibleProps = {
  icon: JSX.Element;
  title: string | JSX.Element;
  subTitle?: string;
  defaultIsExpanded?: boolean;
  children?: React.ReactChild;
  headerWidgets?: JSX.Element | null;
  testID?: string;
  onCollapsibleChange?: (expandedState) => void;
};

const OrderCollapsible = ({
  icon,
  title,
  subTitle,
  defaultIsExpanded,
  children,
  headerWidgets,
  testID = '',
  onCollapsibleChange,
}: OrderCollapsibleProps): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const [isExpanded, setIsExpanded] = useState(defaultIsExpanded || false);

  const handleOnHeaderClick = () => {
    onCollapsibleChange?.(!isExpanded);
    setIsExpanded((isExpanded) => !isExpanded);
  };

  return (
    <Box
      borderWidth="thick"
      borderRadius="medium"
      borderColor="surface.border.gray.muted"
      marginBottom="spacing.5"
      testID={testID}
    >
      <OrderCollapsibleHeader onClick={handleOnHeaderClick} isExpanded={isExpanded}>
        <Box display="flex" alignItems="center" marginX={!isMobile ? '8px' : 'spacing.0'}>
          <OrderCollapsibleIconContainer isExpanded={isExpanded}>
            {icon}
          </OrderCollapsibleIconContainer>
          <Box>
            {typeof title === 'string' ? <Text size="large">{title}</Text> : title}
            <Text color="surface.text.gray.subtle">{subTitle}</Text>
          </Box>
          <Box marginLeft="auto" display="flex" alignItems="center">
            {headerWidgets}
            {isExpanded ? (
              <ChevronUpIcon
                size="large"
                color="interactive.icon.primary.normal"
                marginLeft="spacing.3"
              />
            ) : (
              <ChevronDownIcon
                size="large"
                color="interactive.icon.gray.normal"
                marginLeft="spacing.3"
              />
            )}
          </Box>
        </Box>
      </OrderCollapsibleHeader>
      <Box>
        <StyledCollapsible isOpen={isExpanded}>{children}</StyledCollapsible>
      </Box>
    </Box>
  );
};

export default OrderCollapsible;
