import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledDivider = styled.div`
  border: ${({ theme }: { theme: Theme }): string =>
    `1px solid ${theme.colors.surface.border.normal.highContrast}`};
  flex: none;
  align-self: stretch;
  flex-grow: 0;
`;

export const StyledDetailListing = styled.div`
  p {
    word-break: break-all;
  }
`;
