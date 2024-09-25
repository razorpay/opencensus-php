import * as React from 'react';
import { Box } from '@razorpay/blade/components';
import styled from 'styled-components';

import { LayoutWidgetProps } from './types';
import { getBaseWidget } from '../utils';

const OverrideBox = styled.div`
  & > div > div {
    margin: 0;
  }
`;

export const Layout = ({
  queryKey,
  isLoading,
  properties,
  components,
  styles,
}: LayoutWidgetProps) => {
  return (
    <OverrideBox>
      <Box display={properties?.variant ?? 'flex'} {...styles}>
        {components.map((widgetData) => getBaseWidget({ widget: widgetData, isLoading, queryKey }))}
      </Box>
    </OverrideBox>
  );
};
