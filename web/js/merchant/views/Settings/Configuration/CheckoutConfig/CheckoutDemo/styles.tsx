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
  height: 100%;
  gap: 20px;
  position: relative;
`;

export const FrameContainer = styled.div<{ isDesktopPreview: boolean }>`
  display: flex;
  height: 530px;
  justify-content: center;
  align-items: center;
  margin-top: 46px;
  width: ${(props) => (props.isDesktopPreview ? '1000px' : '500px')};
`;

export const CheckoutFrame = styled.iframe<{
  bgColor?: string;
  isDesktopPreview: boolean;
  shouldScaleToFit: boolean;
}>`
  width: ${(props) => (props.isDesktopPreview ? '1000px' : '260px')};
  height: 100%;
  pointer-events: none;
  border: 0;
  transform: ${(props) =>
    props.isDesktopPreview && props.shouldScaleToFit ? 'translate(-25%, -25%) scale(0.5)' : ''};
  background: ${(props) => props.bgColor || 'none'};
`;

export const PreviewSizeWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  background-color: ${theme.colors.popup.background.subtle};
  border-radius: ${theme.border.radius.max}px;
  padding: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  width: 112px;
  gap: 8px;
`,
);

export const PreviewButton = styled.div(
  ({ theme, isActive }: { theme: Theme; isActive: boolean }) => `
  background: ${isActive ? theme.colors.surface.background.primary.intense : ''};
  color: ${
    isActive ? theme.colors.surface.background.gray.intense : theme.colors.surface.icon.gray.normal
  };
  border: 1px solid transparent;
  border-radius: 32px;
  width: 48px;
  height: 32px;
  padding: 8px; 16px;
  display: flex;
  justify-content: center;
  cursor: ${isActive ? 'not-allowed' : 'pointer'};
  `,
);

export const MobileBackground = styled.div(
  ({ backgroundImg }: { backgroundImg: string }) => `
  pointer-events: none;
  position: absolute;
  z-index: 50;
  display: flex;
  width: 280px;
  height: 580px;
  top: 108px;
  left: 165px;
  align-items: center;
  background-size: cover;
  background-image: url(${backgroundImg})
  `,
);
