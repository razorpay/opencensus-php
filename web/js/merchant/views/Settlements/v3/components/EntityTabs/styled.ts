import styled, { css } from 'styled-components';

interface StyledActiveProps {
  isActive: boolean;
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
  background: ${({ theme }) => theme.colors.surface.background.level2.lowContrast};
  border-bottom: ${({ theme }) => `1px solid ${theme.colors.surface.border.normal.lowContrast}`};
`;

export const Tab = styled.div<StyledActiveProps>`
  display: flex;
  padding-top: ${({ theme }) => `${theme.spacing[0]}px`};
  padding-bottom: ${({ theme }) => `${theme.spacing[4]}px`};
  padding-right: 6px;
  padding-left: 6px;
  cursor: pointer;
  ${({ isActive, theme }) =>
    isActive &&
    css`
      border-bottom: 2px solid ${theme.colors.brand.primary[500]};
    `}
`;

export const StyledDivTabText = styled.div<StyledActiveProps>`
  p {
    margin-left: ${({ theme }) => `${theme.spacing[2]}px`};
    color: ${({ isActive, theme }) => {
      return isActive
        ? `${theme.colors.brand.primary[500]}`
        : `${theme.colors.surface.text.normal.lowContrast}`;
    }};
  }
`;
