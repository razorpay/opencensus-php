import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const DeviceImage = styled.img(
  ({ theme }: { theme: Theme }) => `
  width: 25%;
  min-width: 10rem;
  max-width: 15rem;
  position: absolute;
  top: 50%;
  left: 0;
  transform: translate(10%, -50%);

  @media screen and (max-width: ${theme.breakpoints.l}px) {
    min-width: 8rem;
    transform: translate(0,0);
    top: 5%;
    left: 5%;
  } 

  @media screen and (max-width: ${theme.breakpoints.m}px) {
    width: 25vw;
    max-width: 9.4rem;
  } 

  @media screen and (max-width: ${theme.breakpoints.s}px) {
    width: 25vw;
    min-width: 0px;
  } 
`,
);
