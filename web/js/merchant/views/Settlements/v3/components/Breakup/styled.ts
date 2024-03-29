import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import styled, { css } from 'styled-components';

export const StyledBreakUp = styled.div`
  background: ${({ theme }) => `${theme.colors.surface.background.gray.intense}`};
  width: 100%;
  flex-direction: column;
  flex: 1.8;
  @media screen and (min-width: 768px) {
    flex-direction: row;
    min-width: 410px;
  }
`;

export const BreakupHeader = styled.div`
  padding: 12px 24px;
  border-bottom: 1px solid ${({ theme }) => `${theme.colors.surface.border.gray.muted}`};
`;

export const BreakupContent = styled.div`
  padding: 24px 57px 32px 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  @media screen and (max-width: 768px) {
    padding: 14px;
  }
`;

export const BreakupItem = styled.div<{ isChild?: boolean }>`
  display: flex;
  justify-content: space-between;
  align-items: center;
  ${({ isChild }) =>
    isChild &&
    css`
      padding-left: 11px;
    `}
  &:last-child {
    margin-top: 8px;
  }
`;

export const ItemText = styled.div`
  display: flex;
  align-items: center;
  gap: 5px;
`;

export const StyledDivider = styled(Divider)`
  margin: ${({ theme }) => theme.spacing[4]}px;
`;

export const StyledBox = styled.div`
  cursor: pointer;
`;
