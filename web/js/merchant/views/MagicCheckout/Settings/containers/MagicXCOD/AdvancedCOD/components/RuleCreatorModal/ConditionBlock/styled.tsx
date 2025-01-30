import styled from 'styled-components';

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
