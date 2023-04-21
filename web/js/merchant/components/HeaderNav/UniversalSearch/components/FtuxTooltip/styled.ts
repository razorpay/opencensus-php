import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledFtuxContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    position: absolute;
    top: 42px;
    width: 270px;
    background: #2b4486;
    border-radius: ${theme.spacing[1]}px;
    padding: ${theme.spacing[4]}px ${theme.spacing[5]}px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: ${theme.spacing[2]}px;
    line-height: 18px;
    &:before {
      border-style: solid;
      border-width: 0 5px 5px;
      border-color: #2b4486 transparent;
      content: '';
      display: block;
      position: absolute;
      top: -5px;
      z-index: 1;
    } 
    @media screen and (max-width: 768px) {
      top: 121px;
    }
  `,
);

export const FtuxAction = styled.div(
  ({ theme }: { theme: Theme }) => `
    cursor: pointer;
    padding: ${theme.spacing[4]}px ${theme.spacing[3]}px 3px;
  `,
);
