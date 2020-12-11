import React from 'react';
import styled from 'styled-components';
import { getColor } from '@razorpay/blade/src/_helpers/theme';
import theme from '@razorpay/blade/src/tokens/theme.web';
import spacing from '@razorpay/blade/src/tokens/spacings';

export interface ProgressBarContinuousPropsT {
  percentDone: number;
  height?: string;
  progressBarCompletedColor?: string;
  progressBarBackgroundColor?: string;
}
const ProgressContainer = styled.div`
  width: 100%;
  max-height: ${(props) => props.height};
  height: ${(props) => props.height};
  max-width: 100%;
  border-radius: 100px;
  background-color: ${(props) => getColor(theme, props.progressBarBackgroundColor)};
`;

const ProgressBar = styled.div`
  background-color: ${(props) => getColor(theme, props.progressBarCompletedColor)};
  width: ${(props) => `${props.width}%`};
  height: ${(props) => props.height};
  border-radius: 100px;
  transition: width 0.3s ease-in-out;
`;
const ProgressBarContinuous: React.FC<ProgressBarContinuousPropsT> = ({
  percentDone,
  height = spacing.xsmall,
  progressBarCompletedColor = 'green.900',
  progressBarBackgroundColor = 'white.800',
}) => {
  return (
    <ProgressContainer height={height} progressBarBackgroundColor={progressBarBackgroundColor}>
      <ProgressBar
        width={percentDone}
        height={height}
        progressBarCompletedColor={progressBarCompletedColor}
      />
    </ProgressContainer>
  );
};
export default ProgressBarContinuous;
