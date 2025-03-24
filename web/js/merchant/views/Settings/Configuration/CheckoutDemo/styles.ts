import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const FrameContainer = styled.div<{
  isDesktopPreview: boolean;
  zoomMethodsScreen: boolean;
}>`
  display: flex;
  justify-content: center;
  align-items: center;
  height: 410px;
  margin-top: ${(props) => (props.isDesktopPreview ? '20px' : '42px')};
  width: 100%;
  transform-origin: ${(props) =>
    props.zoomMethodsScreen ? (props.isDesktopPreview ? '70% 60%' : '50% 45%') : 'center'};
  transform: ${(props) => (props.zoomMethodsScreen ? 'scale(1.4)' : 'scale(1)')};
  transition: transform 0.5s ease-in-out, transform-origin 0.5s ease-in-out;
`;

export const CheckoutFrame = styled.iframe<{
  bgColor?: string;
  isDesktopPreview: boolean;
  shouldScaleToFit: boolean;
  zoomTitleStyle: boolean;
}>`
  width: ${(props) => (props.isDesktopPreview ? '1000px' : '370px')};
  height: ${(props) => (props.isDesktopPreview ? '800px' : '760px')};
  border: 0;
  transform: ${(props) =>
    props.isDesktopPreview
      ? props.zoomTitleStyle
        ? 'translate(-25%, -22.5%) scale(0.5)'
        : 'scale(0.5)'
      : 'translateY(9%) scale(0.7)'};
  background: ${(props) => props.bgColor || 'none'};
`;

export const PreviewButton = styled.div(
  ({ theme, isActive }: { theme: Theme; isActive: boolean }) => `
  background: ${isActive ? theme.colors.surface.background.primary.intense : ''};
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
  ({ backgroundImg, zoomMethodsScreen }: { backgroundImg: string; zoomMethodsScreen: boolean }) => `
  pointer-events: none;
  position: absolute;
  z-index: 1;
  display: flex;
  width: 280px;
  height: 580px;
  left: 49.3%;
  align-items: center;
  background-size: cover;
  background-image: url(${backgroundImg});
  margin-top: 20px;
  transform-origin: ${zoomMethodsScreen ? '50% 40%' : ' center'};
  transform: translateX(-50%) ${zoomMethodsScreen ? 'scale(1.4)' : 'scale(1)'};
  transition: transform 0.5s ease-in-out, transform-origin 0.5s ease-in-out;
  `,
);

export const MobileToolbar = styled.div(
  ({
    backgroundColor,
    zoomMethodsScreen,
  }: {
    backgroundColor: string;
    zoomMethodsScreen: boolean;
  }) => `
    visibility: ${zoomMethodsScreen ? 'hidden' : 'visible'};
    margin-top: 20px;
    position: absolute;
    height: 28px;
    width: 251px;
    border-top-left-radius: 10%;
    border-top-right-radius: 10%;
    top: 2%;
    left: 50%;
    transform: translateX(-50%);
    background-color: ${backgroundColor};
    transition: transform 0.5s ease-in-out, transform-origin 0.5s ease-in-out;
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
    margin-bottom: ${theme.spacing[3]}px;
  `,
);
