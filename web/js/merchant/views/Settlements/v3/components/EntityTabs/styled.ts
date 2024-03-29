import styled, { css } from 'styled-components';

interface StyledActiveProps {
  isActive: boolean;
  isCountSuffix?: boolean;
}

export const TabsContainer = styled.div`
  display: flex;
  justify-content: flex-start;
  flex-direction: row;
  padding-top: ${({ theme }) => `${theme.spacing[6]}px`};
  padding-left: ${({ theme }) => `${theme.spacing[6]}px`};
  padding-bottom: ${({ theme }) => `${theme.spacing[0]}px`};
  padding-right: ${({ theme }) => `${theme.spacing[0]}px`};
  max-width: 100%;
  overflow: scroll;
  background: ${({ theme }) => theme.colors.surface.background.gray.intense};
  border-bottom: ${({ theme }) => `1px solid ${theme.colors.surface.border.gray.muted}`};
`;

export const Tab = styled.div<StyledActiveProps>`
  display: flex;
  padding-top: ${({ theme }) => `${theme.spacing[0]}px`};
  padding-bottom: ${({ theme }) => `${theme.spacing[4]}px`};
  padding-right: 6px;
  padding-left: 6px;
  cursor: pointer;
  flex-direction: ${({ isCountSuffix }) => (isCountSuffix ? 'row-reverse' : 'row')};
  gap: ${({ theme }) => `${theme.spacing[2]}px`};
  ${({ isActive, theme }) =>
    isActive &&
    css`
      border-bottom: 2px solid ${theme.colors.surface.background.primary.intense};
    `}
`;

export const StyledDivTabText = styled.div<StyledActiveProps>`
  p {
    color: ${({ isActive, theme }) => {
      return isActive
        ? `${theme.colors.surface.background.primary.intense}`
        : `${theme.colors.surface.text.gray.normal}`;
    }};
  }
`;
