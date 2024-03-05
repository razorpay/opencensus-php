import React from 'react';
import styled, { css } from 'styled-components';

export interface DividerPropsInterface {
  noMargin?: boolean;
}

const StyledDivider = styled.div<any>`
  border: 1px solid rgba(121, 135, 156, 0.09);
  flex: none;
  align-self: stretch;
  flex-grow: 0;
  ${({ noMargin }) =>
    !noMargin &&
    css`
      margin: 12px 0 14px 0;
    `}
`;

const Divider = ({ noMargin }: DividerPropsInterface): JSX.Element => (
  <StyledDivider data-testid="divider" noMargin={noMargin} />
);

export default Divider;
