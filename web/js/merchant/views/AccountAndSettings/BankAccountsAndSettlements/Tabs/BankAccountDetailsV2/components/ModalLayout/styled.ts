import styled, { css } from 'styled-components';

export const StyledLayout = styled.div<any>`
  background: #ffffff;
  box-shadow: 0px 1px 2px rgba(21, 45, 75, 0.2), 0px 0px 1px rgba(21, 45, 75, 0.2);
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  @media screen and (min-width: 768px) {
    padding: 24px;
    ${({ minHeight }) =>
      minHeight &&
      css`
        min-height: ${minHeight}px;
      `}
  }
  @media screen and (max-width: 768px) {
    padding: 12px;
    gap: 12px;
  }
  ${({ isCentered }) =>
    isCentered &&
    css`
      gap: 0px;
    `}
`;

export const HeadingWrapper = styled.div``;

export const TopBar = styled.div`
  display: flex;
  justify-content: space-between;
  gap: 25px;
  align-items: center;
`;

export const IconWrapper = styled.div`
  cursor: pointer;
`;

export const StyledLayoutContent = styled.div<any>`
  display: flex;
  flex-direction: column;
  ${({ isCentered }) =>
    isCentered &&
    css`
      flex: 1;
      justify-content: center;
      align-items: center;
    `}
`;
