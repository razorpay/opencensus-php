import styled from 'styled-components';
import { IndicatorContainerProps } from './types';

export const IndicatorContainer = styled.div<IndicatorContainerProps>(
  ({ isInProcess }) => `
    position: absolute;
    right: -5px;
    top: 0;
    ${isInProcess && `animation: blinkReportsIndicator 1s linear infinite alternate;`}
    @keyframes blinkReportsIndicator {
      0% {
        opacity: 0.5;
      }
      100% {
        opacity: 1;
      }
    }
`,
);
