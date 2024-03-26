import styled, { css, keyframes } from 'styled-components';

const slideIn = keyframes`
  0% { height: 0px; opacity:0 }
  40% { height: 0px; opacity:0 }
  100% { height: 20px; opacity:1 }
`;

export const AnimatedBox = styled('div')(
  ({ theme }) => css`
    cursor: pointer;
    display: flex;
    flex-direction: row;
    gap: ${theme.spacing[5]}px;
    animation-duration: ${theme.motion.duration.xmoderate}ms;
    animation-timing-function: ${theme.motion.easing.entrance.revealing};
    align-items: center;
    animation-name: ${slideIn};
  `,
);

export const ProductCardWidgetWrapper = styled.div`
  width: 284px;
  height: 416px;
  display: flex;
  flex-direction: column;
`;
