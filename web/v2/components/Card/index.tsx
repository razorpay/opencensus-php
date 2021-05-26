import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';

const styles = {
  backgroundColor({ theme, backgroundColor: _backgroundColor }) {
    return getColor(theme, `${_backgroundColor}`);
  },
  shadowColor({ theme, shadowColor: _shadowColor }) {
    return getColor(theme, `${_shadowColor}`);
  },
};

const StyledCard = styled(View)`
  background-color: ${styles.backgroundColor};
  box-shadow: 0px 4px 10px ${styles.shadowColor};
`;

export interface CardPropsT {
  backgroundColor?: string;
  shadowColor?: string;
  children: React.ReactNode;
  padding?: number[] | string[] | number | string;
  margin?: number[] | string[] | number | string;
}

const Card: React.FC<CardPropsT> = ({
  children,
  backgroundColor = 'background.200',
  shadowColor = 'primary.920',
  padding = [1],
  margin = [0],
}) => (
  <Space padding={padding} margin={margin}>
    <StyledCard backgroundColor={backgroundColor} shadowColor={shadowColor}>
      {children}
    </StyledCard>
  </Space>
);

export default Card;
