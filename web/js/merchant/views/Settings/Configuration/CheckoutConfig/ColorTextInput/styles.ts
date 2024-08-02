import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ColorInputLabel = styled.label`
  width: 100%;
`;

export const ColorInputWrapper = styled.div`
  display: flex;
  width: 100%;
  gap: ${({ theme }: { theme: Theme }) => theme.spacing[3]}px;
  align-items: center;
  background-color: ${({ theme }: { theme: Theme }) =>
    theme.colors.surface.background.gray.intense};
  border: 1px solid ${({ theme }: { theme: Theme }) => theme.colors.interactive.border.gray.default};
  height: ${({ theme }: { theme: Theme }) => theme.spacing[9]}px;
  border-radius: ${({ theme }: { theme: Theme }) => theme.border.radius.medium}px;
  padding: ${({ theme }: { theme: Theme }) => theme.spacing[3]}px
    ${({ theme }: { theme: Theme }) => theme.spacing[4]}px;
`;

export const ColorInputBox = styled.input`
  appearance: none;
  width: ${({ theme }: { theme: Theme }) => theme.spacing[6]}px !important;
  height: ${({ theme }: { theme: Theme }) => theme.spacing[6]}px !important;
  border-radius: ${({ theme }: { theme: Theme }) => theme.border.radius.small}px;
  border: 0;
  background: transparent;
  padding: 0;
  cursor: pointer;
  flex-shrink: 0;
  position: static !important;

  &::-webkit-color-swatch {
    border: 1px solid
      ${({ theme }: { theme: Theme }) => theme.colors.interactive.border.gray.default};
    border-radius: ${({ theme }: { theme: Theme }) => theme.border.radius.small}px;
  }

  &::-webkit-color-swatch-wrapper {
    padding: 0;
  }

  &::-moz-color-swatch {
    border: 0;
    border-radius: ${({ theme }: { theme: Theme }) => theme.border.radius.small}px;
  }

  &::-moz-color-swatch-wrapper {
    padding: 0;
  }
`;

export const ColorInputText = styled.input`
  width: 100%;
  border: 0;
  color: ${({ theme }: { theme: Theme }) => theme.colors.surface.text.gray.subtle};
`;
