import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

type StyledProps = { theme: Theme };

export const ExtraItemsWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
    margin-top: ${theme.spacing[6]}px
    display: flex;
    padding: ${theme.spacing[6]}px ${theme.spacing[7]}px ${theme.spacing[5]}px ${theme.spacing[7]}px;
    flex-direction: column;
    align-items: flex-start;
    gap: ${theme.spacing[6]}px;
    align-self: stretch;
    border-radius: 8px;
    background: #FFF;
    box-shadow: ${theme.elevation.lowRaised}
  `,
);

export const ColorInputBox = styled.input`
  appearance: none;
  width: ${({ theme }: { theme: Theme }) => theme.spacing[5]}px !important;
  height: ${({ theme }: { theme: Theme }) => theme.spacing[5]}px !important;
  border-radius: ${({ theme }: { theme: Theme }) => theme.border.radius.round};
  border: 0;
  background: transparent;
  padding: 0;
  cursor: pointer;
  flex-shrink: 0;
  position: static !important;

  &::-webkit-color-swatch {
    border-radius: ${({ theme }: { theme: Theme }) => theme.border.radius.round};
  }

  &::-webkit-color-swatch-wrapper {
    padding: 0;
  }

  &::-moz-color-swatch {
    border: 0;
    border-radius: ${({ theme }: { theme: Theme }) => theme.border.radius.round};
  }

  &::-moz-color-swatch-wrapper {
    padding: 0;
  }
`;

export const ColorInputWrapper = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  gap: ${({ theme }: StyledProps) => theme.spacing[3]}px;
  overflow: hidden;
  cursor: pointer;
`;
