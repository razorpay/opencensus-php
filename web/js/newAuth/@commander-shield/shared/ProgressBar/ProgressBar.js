import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';

const Wrapper = styled(View)`
  background: ${(props) => props.theme.colors.shade[930]};
  border-radius: 10px 5px 5px 0;
  overflow: hidden;
`;

const StyledProgressBar = styled(View)`
  position: relative;
  width: ${(props) => props.percent}%;
  top: 0;
  left: 0;
  height: 3px;
  transition: all 0.5s ease-out;
  background: ${(props) => props.theme.colors.positive[960]};
  box-shadow: 0px 0px 5px rgba(39, 194, 76, 0.25);
  border-radius: 10px 5px 5px 0;
`;

const ProgressBar = ({ percent }) => (
  <Wrapper>
    <StyledProgressBar percent={percent} />
  </Wrapper>
);

ProgressBar.propTypes = {
  percent: PropTypes.number,
};

ProgressBar.defaultProps = {
  percent: 0,
};

export default ProgressBar;
