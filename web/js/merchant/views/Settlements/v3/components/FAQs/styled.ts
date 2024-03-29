import styled, { css } from 'styled-components';

export const StyledSectionContent = styled.div<{ isDeviceUnderBreakpoint?: boolean }>`
  display: flex;
  border-radius: 4px;
  ${({ isDeviceUnderBreakpoint }) =>
    !isDeviceUnderBreakpoint &&
    css`
      height: 312px;
    `}
`;

export const TabList = styled.div<{
  isFirstActive: boolean;
  isLastActive: boolean;
  isDeviceUnderBreakpoint?: boolean;
}>`
  width: 35%;
  overflow-y: scroll;
  -ms-overflow-style: none;
  &::-webkit-scrollbar {
    display: none;
  }
  border-style: solid;
  border-color: ${({ theme }) => `${theme.colors.surface.border.gray.muted}`};
  border-radius: 4px 0 0 4px;
  ${({ isFirstActive, isLastActive }) => {
    if (isFirstActive) {
      return css`
        border-width: 0 0 1px 0;
      `;
    }
    if (isLastActive) {
      return css`
        border-width: 1px 0 0 0;
      `;
    }
    return css`
      border-width: 1px 0;
    `;
  }};
  ${({ isDeviceUnderBreakpoint }) =>
    isDeviceUnderBreakpoint &&
    css`
      width: 100%;
      border-radius: 4px;
    `}
`;

export const TabQueryItem = styled.div`
  display: flex;
  gap: 8px;
  align-items: center;
`;

export const TabListItem = styled.div<{
  isDeviceUnderBreakpoint?: boolean;
  isActive: boolean;
  activeTab: number;
}>`
  padding: 16px;
  cursor: pointer;
  display: flex;
  gap: 8px;
  justify-content: space-between;
  align-items: center;
  border-width: 1px 1px 0 1px;
  border-style: solid;
  border-color: ${({ theme }) => `${theme.colors.surface.border.gray.muted}`};
  &:first-child {
    border-width: 0 1px 0 1px;
    border-radius: 4px 0 0 0;
  }
  &:nth-child(${({ activeTab }) => activeTab + 2}) {
    border-width: 0 1px 0 1px;
  }
  &:last-child {
    border-radius: 0 0 0 4px;
  }
  svg {
    flex: 0 0 auto;
  }
  ${({ isDeviceUnderBreakpoint }) =>
    isDeviceUnderBreakpoint &&
    css`
      && {
        border-width: 1px 1px 0 1px;
      }
      &&:first-child {
        border-width: 0 1px 0 1px;
        border-radius: 4px 4px 0 0;
      }
      &:last-child {
        border-radius: 0 0 4px 4px;
      }
    `}

  ${({ isActive, theme }) =>
    isActive &&
    css`
      &&& {
        border-color: ${theme.colors.surface.background.primary.intense};
        border-width: 1px;
        background: rgba(21, 102, 241, 0.09);
      }
    `}
`;

export const TabContent = styled.div<{ isDeviceUnderBreakpoint?: boolean; isLast?: boolean }>`
  flex: 1;
  padding: 32px;
  background: ${({ theme }) => `${theme.colors.surface.background.gray.moderate}`};
  border-width: 1px 1px 1px 0;
  border-style: solid;
  border-color: ${({ theme }) => `${theme.colors.surface.border.gray.muted}`};
  border-radius: 0 4px 4px 0;
  overflow-y: scroll;
  -ms-overflow-style: none;
  &::-webkit-scrollbar {
    display: none;
  }
  ${({ isDeviceUnderBreakpoint, isLast }) =>
    isDeviceUnderBreakpoint &&
    css`
      border-width: 0 1px ${isLast ? '1px' : '0'} 1px;
      border-radius: 0;
      overflow: hidden;
    `}
  @media screen and (max-width: 768px) {
    padding: 12px;
  }
`;

export const TabOrder = styled.span`
  height: 6px;
  width: 6px;
  background: #a3afbf;
  border-radius: 50%;
  flex: 0 0 auto;
`;

export const CollapsibleIcon = styled.div<{ open: boolean }>`
  transition: 0.3s all;
  ${({ open }) =>
    open &&
    css`
      transform: rotate(180deg);
    `}
`;
