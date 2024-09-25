import React from 'react';

import { Text } from '@razorpay/blade/components';
import {
  LeftWrapper,
  TopWrapper,
  Wrapper,
} from 'merchant/views/Settings/Configuration/components/Configuration/styled';

import { LineItemsProps } from 'merchant/views/Settings/Configuration/components/Configuration/types';

const LineItems: React.FC<LineItemsProps> = ({ title, subTitle, rightChildren, extraItems }) => {
  return (
    <Wrapper>
      <TopWrapper>
        <LeftWrapper>
          <Text weight="medium" color="surface.text.gray.normal" variant="body" size="medium">
            {title}
          </Text>
          <Text color="surface.text.gray.muted" variant="body" size="small" weight="regular">
            {subTitle}
          </Text>
        </LeftWrapper>
        {rightChildren}
      </TopWrapper>
      {extraItems}
    </Wrapper>
  );
};

export default LineItems;
