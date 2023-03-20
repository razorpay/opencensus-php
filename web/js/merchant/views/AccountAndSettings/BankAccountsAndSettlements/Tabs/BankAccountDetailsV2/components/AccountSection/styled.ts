import styled, { css } from 'styled-components';

export const StyledAccountSectionContainer = styled.div`
  box-shadow: 0px 1px 2px rgba(21, 45, 75, 0.2), 0px 0px 1px rgba(21, 45, 75, 0.2);
  border-radius: 4px 4px 0px 0px;
  @media screen and (max-width: 768px) {
    width: 100%;
  }
`;

export const StyledAccountSectionHeader = styled.div`
  padding: 24px;
  background: #ffffff;
  display: flex;
  gap: 12px;
  flex-direction: column;
  @media screen and (max-width: 768px) {
    gap: 16px;
  }
`;

export const HeaderTopBar = styled.div`
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
`;

export const StyledAccountSectionContent = styled.div<any>`
  background: #f2f4f8;
  padding: ${({ isSections }) => (isSections ? 12 : 24)}px;
  display: flex;
  gap: 24px;
  flex-direction: column;
  @media screen and (max-width: 768px) {
    padding: 8px;
    ${({ isSections }) =>
      !isSections &&
      css`
        padding: 20px;
        display: flex;
        align-items: center;
      `}
  }
`;

export const CollapsibleIcon = styled.div<any>`
  height: 20px;
  width: 20px;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: 0.3s all;
  .i {
    font-size: 80%;
  }
  ${({ open }) =>
    open &&
    css`
      transform: rotate(180deg);
    `}
`;
