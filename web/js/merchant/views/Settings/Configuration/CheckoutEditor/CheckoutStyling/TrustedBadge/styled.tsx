import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

type StyledProps = { theme: Theme };

export const Wrapper = styled.div`
  display: flex;
  padding: 12px 16px;
  flex-direction: column;
  justify-content: space-between;
  gap: ${({ theme }: StyledProps) => theme.spacing[3]}px;
  border-radius: 8px;
  background: ${({ theme }: StyledProps) => theme.colors.surface.background.gray.moderate};
`;

export const TopWrapper = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: ${({ theme }: StyledProps) => theme.spacing[3]}px;
`;

export const LeftWrapper = styled.div`
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: space-between;
`;

export const RightChildrenWrapper = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  gap: ${({ theme }: StyledProps) => theme.spacing[3]}px;
  overflow: hidden;
  cursor: pointer;
`;

export const TrustedIconWrapper = styled.div(
  ({ theme }) => `
    display: flex;
    padding: ${theme.spacing[1]}px  ${theme.spacing[3]}px ${theme.spacing[1]}px ${theme.spacing[3]}px;
    justify-content: center;
    align-items: center;
    gap: 8px;
    border-radius: 12px;
    border: 1px solid ${theme.colors.interactive.border.neutral.faded};
    background: rgba(108, 132, 157, 0.12);
    margin-top: ${theme.spacing[4]}px
`,
);
