import React from 'react';
import { Badge, Box, Text } from '@razorpay/blade/components';
import { LineItemsProps } from 'merchant/views/Settings/Configuration/components/Configuration/types';

const Wrapper = ({ children }) => (
  <Box
    display="flex"
    flexDirection="column"
    justifyContent="space-between"
    gap="spacing.3"
    borderRadius="large"
    backgroundColor="surface.background.gray.moderate"
    padding={['spacing.4', 'spacing.5']}
    borderStyle="dashed"
  >
    {children}
  </Box>
);

const TopWrapper = ({ children }) => (
  <Box display="flex" alignItems="center" justifyContent="space-between" gap="spacing.3">
    {children}
  </Box>
);

const LeftWrapper = ({ children }) => (
  <Box display="flex" flexDirection="column" alignItems="flex-start" justifyContent="space-between">
    {children}
  </Box>
);

const RightChildrenWrapper = ({ children }) => (
  <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.3" overflow="hidden">
    {children}
  </Box>
);

const RightChildren: React.FC = () => (
  <RightChildrenWrapper>
    <Badge color="positive">Coming Soon</Badge>
  </RightChildrenWrapper>
);
const ComingSoonLineItems: React.FC<LineItemsProps> = ({ title, subTitle }) => {
  return (
    <Wrapper>
      <TopWrapper>
        <LeftWrapper>
          <Text weight="medium" color="surface.text.gray.muted" variant="body" size="medium">
            {title}
          </Text>
          <Text color="surface.text.gray.muted" variant="body" size="small" weight="regular">
            {subTitle}
          </Text>
        </LeftWrapper>
        <RightChildren />
      </TopWrapper>
    </Wrapper>
  );
};
export default ComingSoonLineItems;
