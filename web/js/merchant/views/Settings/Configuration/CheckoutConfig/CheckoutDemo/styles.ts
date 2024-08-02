import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

type StyledProps = { theme: Theme };

export const Wrapper = styled.div`
  flex: 3;
  display: flex;
  flex-direction: column;
  gap: ${({ theme }: StyledProps) => theme.spacing[3]}px;
  overflow: hidden;
  border-radius: ${({ theme }: StyledProps) => theme.border.radius['2xlarge']}px;
  background-color: ${({ theme }: StyledProps) => theme.colors.surface.background.gray.subtle};
  padding: ${({ theme }: StyledProps) => theme.spacing[11]}px;
  box-shadow: 0px 0px 24px 0px rgba(0, 0, 0, 0.05) inset;
  max-width: 615px;
`;

export const FrameContainer = styled.div`
  position: relative;
  width: 1160px;
  height: 680px;
`;

export const CheckoutFrame = styled.iframe<{ bgColor?: string }>`
  width: 1000px;
  height: 580px;
  pointer-events: none;
  border: 0;
  background: none;
`;
