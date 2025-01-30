import styled from 'styled-components';

export const ConditionTreeBranch = styled.div`
  &::before {
    content: '';
    position: absolute;
    top: 18px;
    left: -70px;
    background: ${({ theme }) => theme.colors.surface.border.gray.subtle};
    width: 70px;
    height: 1px;
  }

  ${({ drawVertical, heightMultiplier = 0, errorCount = 0, theme }) =>
    drawVertical
      ? `
    &::after {
      content: '';
      position: absolute;
      bottom: 18px;
      left: -70px;
      background: ${theme.colors.surface.border.gray.subtle};
      width: 1px;
      height: ${84 + heightMultiplier * 112 + errorCount * 16}px;
    }
  `
      : ''}
`;
