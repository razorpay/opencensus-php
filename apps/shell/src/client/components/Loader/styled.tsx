import styled, { css, keyframes } from 'styled-components';

const toggle = keyframes`
  0% { fill: #305eff; }
  50% { fill: #edf4f7; }
  100% { fill: #305eff; }
`;

const togglePath = keyframes`
  0% { fill: #edf4f7; }
  50% { fill: #305eff; }
  100% { fill: #edf4f7; }`;

export const LogoSvg = styled('svg')(
  ({ theme }) => css`
    animation-duration: ${theme.motion.duration['2xgentle'] * 1.5}ms;
    animation-timing-function: ${theme.motion.easing.entrance.attentive};
    animation-iteration-count: infinite;
    animation-name: ${toggle};
    & path {
      animation-duration: ${theme.motion.duration['2xgentle'] * 1.5}ms;
      animation-timing-function: ${theme.motion.easing.entrance.attentive};
      animation-iteration-count: infinite;
      animation-name: ${togglePath};
    }
  `,
);
