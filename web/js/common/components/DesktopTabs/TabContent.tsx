import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';

const StyledTabContent = styled(View)`
  width: ${({ width }) => width};
`;

interface ITabContentProps {
  isVisible?: boolean;
  children: React.ReactElement | React.ReactElement[];
  calculatedWidth: string;
}

const TabContent = ({
  isVisible,
  children,
  calculatedWidth,
}: ITabContentProps): React.ReactElement | null => {
  if (!isVisible) {
    return null;
  }

  return <StyledTabContent width={calculatedWidth}>{children}</StyledTabContent>;
};

export default TabContent;
