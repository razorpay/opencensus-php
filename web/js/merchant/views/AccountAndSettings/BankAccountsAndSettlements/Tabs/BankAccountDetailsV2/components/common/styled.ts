import styled, { css } from 'styled-components';

export const StyledDivider = styled.div`
  border: 1px solid rgba(121, 135, 156, 0.18);
  ${({ isFullWidth }) =>
    isFullWidth &&
    css`
      width: 100%;
    `}
`;

export const Flexbox = {
  Row: styled.div`
    display: flex;
  `,
  Column: styled.div`
    display: flex;
    flex-direction: column;
  `,
};
