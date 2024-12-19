import { Theme, BoxProps } from '@razorpay/blade/components';
import styled from 'styled-components';

type StyledProps = { theme: Theme };
type Border = BoxProps & {
  $border?: string;
  theme: Theme;
};

export const Wrapper = styled.div<Border>(
  ({ theme, $border }) => `
    display: flex;
    padding: 12px 16px;
    flex-direction: column;
    justify-content: space-between;
    gap: ${theme.spacing[3]}px;
    border-radius: 8px;
    background: ${theme.colors.surface.background.gray.moderate};
    ${$border ? `border: 1px ${$border} ${theme.colors.surface.border.gray.muted};` : ''}
  `,
);

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

export const TitleWrapper = styled.span`
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: ${({ theme }: StyledProps) => theme.spacing[2]}px;
`;
