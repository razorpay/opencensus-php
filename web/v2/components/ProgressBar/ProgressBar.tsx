import React from 'react';
import styled from 'styled-components';
import { getColor } from '@razorpay/blade/src/_helpers/theme';
import theme from '@razorpay/blade/src/tokens/theme.web';
import spacing from '@razorpay/blade/src/tokens/spacings';
import Space from '@razorpay/blade/src/atoms/Space';

export interface ProgressBarPropsT {
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

const StyledProgressBar = styled.div`
  background-color: ${(props) => getColor(theme, props.progressBarCompletedColor)};
  width: ${(props) => `${props.width}%`};
  height: ${(props) => props.height};
  border-radius: 100px;
  transition: width 0.3s ease-in-out;
`;
const ProgressBar: React.FC<ProgressBarPropsT> = ({
  percentDone,
  height = spacing.xsmall,
  progressBarCompletedColor = 'green.900',
  progressBarBackgroundColor = 'white.800',
}) => {
  return (
    <Space margin={[0.25, 0, 0, 0]}>
      <ProgressContainer height={height} progressBarBackgroundColor={progressBarBackgroundColor}>
        <StyledProgressBar
          width={percentDone}
          height={height}
          progressBarCompletedColor={progressBarCompletedColor}
        />
      </ProgressContainer>
    </Space>
  );
};
export default ProgressBar;
