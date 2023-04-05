import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const Content = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  flex-direction: column;
  gap: ${theme.spacing[3]}px;
  padding-top: 5px;
`,
);

export const ListItem = styled.div`
  display: flex;
  align-items: center;
  gap: 9px;
  margin-left: 8px;
`;

export const Order = styled.span`
  height: 5px;
  width: 5px;
  border-radius: 50%;
  background: hsla(216, 27%, 36%, 1);
`;
