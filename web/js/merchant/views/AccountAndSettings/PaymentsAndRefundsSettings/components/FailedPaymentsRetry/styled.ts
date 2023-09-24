import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const AmountWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
    display: flex;
    align-items: center;
    gap: ${theme.spacing[1]}px;
    overflow-x: scroll;
    -ms-overflow-style: none;
    &::-webkit-scrollbar {
      display: none;
    }
  `,
);
