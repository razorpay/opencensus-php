import * as React from 'react';
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

export const ConditionBlockTreeBranch = styled.div`
  &::before {
    content: '';
    position: absolute;
    top: 40px;
    left: -45px;
    height: 1px;
    width: 45px;
    background-color: ${({ theme }) => theme.colors.surface.border.gray.subtle};
  }
`;

type ConditionBlockTreeStemProps = {
  lastBlockConditionCount: number;
  errorsCount: number;
};
export const ConditionBlockTreeStem: React.FC<ConditionBlockTreeStemProps> = styled.div`
  &::after {
    content: '';
    position: absolute;
    left: 24px;
    top: 40px;
    bottom: ${({ lastBlockConditionCount, errorsCount }) =>
      108 /* 60 + 48 */ + 19.25 * errorsCount + 112 * lastBlockConditionCount - 18}px;
    width: 1px;
    background-color: ${({ theme }) => theme.colors.surface.border.gray.subtle};
  }
`;
