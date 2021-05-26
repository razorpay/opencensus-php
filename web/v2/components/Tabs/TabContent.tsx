import React from 'react';
import styled from 'styled-components';
import { Motion, spring } from 'react-motion';
import View from '@razorpay/blade-old/src/atoms/View';

const StyledTabContent = styled(View)`
  transform: ${(props) => `translateX(${props.left}px)`};
`;

interface TabContentProps {
  isVisible: boolean;
  children: React.ReactElement | React.ReactElement[];
}

const TabContent = ({ isVisible, children }: TabContentProps): React.ReactElement | null => {
  if (!isVisible) {
    return null;
  }

  return (
    <Motion defaultStyle={{ left: -200 }} style={{ left: spring(0) }}>
      {(value) => <StyledTabContent left={value.left}>{children}</StyledTabContent>}
    </Motion>
  );
};

export default TabContent;
