import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

const StyledHeader = styled.header(
  ({ theme, showTopBorder = false }: { theme: Theme; showTopBorder?: boolean }) => `

  && {
    ${showTopBorder ? '' : 'border-top: 0;'}
  }

  > a.flex-link:not(.dropdown a) {
    display: inline-flex;
    width: fit-content;
    align-items: center;
    gap: ${theme.spacing[3]}px;
  }
  `,
);

export { StyledHeader };
