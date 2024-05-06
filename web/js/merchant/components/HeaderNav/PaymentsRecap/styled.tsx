import styled, { css, keyframes } from 'styled-components';

const animateBgPosition = keyframes`
  from { background-position: -600px; }
  to { background-position: 0; }
`;

const shimmerAnimation = () => css`
  ${animateBgPosition} 5s infinite linear;
`;

export const BannerBtn = styled.button<{
  isMobileBanner: boolean;
}>`
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  left: 85%;
  font-size: ${(props) => (props.isMobileBanner ? '10px' : '14px')};
  min-height: ${(props) => (props.isMobileBanner ? '30px' : '36px')};
  padding: ${(props) => (props.isMobileBanner ? '0px 10px' : '0px 15px')};
  width: max-content;
  cursor: pointer;
  background: linear-gradient(
    130deg,
    rgba(88, 212, 153, 1) 0%,
    rgba(88, 212, 153, 1) 45%,
    rgba(255, 255, 255, 1) 50%,
    rgba(88, 212, 153, 1) 65%,
    rgba(88, 212, 153, 1) 100%
  );
  background-color: rgba(88, 212, 153, 1);
  backgroundrepeat: no-repeat;
  backgroundposition: 0px;
  backgroundsize: 300%;
  color: #2d2a26;
  font-weight: 600;
  border-width: 1px;
  border-radius: 2px;
  border-style: solid;
  border-color: rgba(88, 212, 153, 1);
  animation: ${shimmerAnimation};
`;
