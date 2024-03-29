import styled, { css } from 'styled-components';

export const InfoItem = styled.div<{ isBorder: boolean }>`
  padding: 0 22px 0 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  flex: 1;
  ${({ isBorder }) =>
    isBorder &&
    css`
      border-right: 1px solid ${({ theme }) => `${theme.colors.surface.border.gray.muted}`};
    `}
  &:first-child {
    gap: 8px;
  }
  p {
    white-space: nowrap;
  }
  @media screen and (max-width: 768px) {
    padding: 0;
    border: none;
    && {
      gap: 0;
      &:last-child {
        gap: 6px;
      }
    }
  }
`;

export const StyledInfoValue = styled.div<{ gap: string }>`
  display: flex;
  align-items: baseline;
  white-space: nowrap;
  cursor: pointer;
  ${({ gap }) =>
    gap &&
    css`
      gap: ${gap};
    `}
`;
