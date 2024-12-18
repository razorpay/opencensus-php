import React from 'react';

import { Badge, Text } from '@razorpay/blade/components';

import { LineItemsProps } from 'merchant/views/Settings/Configuration/components/Configuration/types';
import {
  LeftWrapper,
  TopWrapper,
  Wrapper,
  RightChildrenWrapper,
} from 'merchant/views/Settings/Configuration/components/Configuration/styled';

const RightChildren: React.FC = () => (
  <RightChildrenWrapper>
    <Badge color={'positive'}>Coming Soon</Badge>
  </RightChildrenWrapper>
);
const ComingSoonLineItems: React.FC<LineItemsProps> = ({ title, subTitle }) => {
  return (
    <Wrapper $border={'dashed'}>
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
