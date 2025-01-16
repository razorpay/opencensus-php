import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

type StyledProps = { theme: Theme };

export const Wrapper = styled.div`
  flex: 3;
  align-items: center;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-radius: ${({ theme }: StyledProps) => theme.border.radius['2xlarge']}px;
  background-color: ${({ theme }: StyledProps) => theme.colors.surface.background.gray.subtle};
  box-shadow: 0px 0px 24px 0px rgba(0, 0, 0, 0.05) inset;
  height: 100%;
  position: relative;
`;

export const FrameContainer = styled.div<{ isDesktopPreview: boolean }>`
  display: flex;
  justify-content: center;
  align-items: center;
  height: 410px;
  margin-top: ${(props) => (props.isDesktopPreview ? '20px' : '42px')};
  width: ${(props) => (props.isDesktopPreview ? '1000px' : 'auto')};
`;

export const CheckoutFrame = styled.iframe<{
  bgColor?: string;
  isDesktopPreview: boolean;
  shouldScaleToFit: boolean;
  zoomTitleStyle: boolean;
}>`
  width: ${(props) => (props.isDesktopPreview ? '1000px' : '370px')};
  height: ${(props) => (props.isDesktopPreview ? '800px' : '760px')};
  pointer-events: none;
  border: 0;
  transform: ${(props) =>
    props.isDesktopPreview
      ? props.zoomTitleStyle
        ? 'translate(-25%, -22.5%) scale(0.5)'
        : 'scale(0.5)'
      : 'translateY(9%) scale(0.7)'};
  background: ${(props) => props.bgColor || 'none'};
`;

export const SwitchPreviewWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  background-color: ${theme.colors.popup.background.subtle};
  border-radius: ${theme.border.radius.max}px;
  padding: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  width: 112px;
  align-self: flex-start;
  margin-top: auto;
  margin-left: 5%;
  margin-bottom: 5%;
  gap: ${theme.spacing[3]}px;

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
  z-index: 1;
  display: flex;
  width: 280px;
  height: 580px;
  left: 49.3%;
  transform: translateX(-50%);
  align-items: center;
  background-size: cover;
  background-image: url(${backgroundImg});
  margin-top: 20px;
  `,
);

export const MobileToolbar = styled.div(
  ({ backgroundColor }: { backgroundColor: string }) => `
    margin-top: 20px;
    position: absolute;
    height: 23px;
    width: 251px;
    border-top-left-radius: 10%;
    border-top-right-radius: 10%;
    top: 2%;
    left: 50%;
    transform: translateX(-50%);
    background-color: ${backgroundColor}
  `,
);

export const PreviewBadge = styled.div(
  ({ theme }: { theme: Theme }) => `
    position: sticky;
    top: 0px;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
    padding: 4px 24px;
    background-color: ${theme.colors.feedback.background.notice.intense};
    color: ${theme.colors.surface.text.staticWhite.normal};
    font-size: ${theme.typography.fonts.size[75]}px;
    font-weight: ${theme.typography.fonts.weight.regular};
  `,
);

export const ScrollablePreview = styled.div`
  position: relative;
  overflow: scroll;
  height: 500px;
`;
