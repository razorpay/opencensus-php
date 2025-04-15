import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

type StyledProps = { theme: Theme };

export const SSOContainer = styled.div`
  background: white;
  display: flex;
  flex-direction: column;
`;

export const PreviewWrapper = styled.div`
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
  margin-top: 20px;
`;

export const CheckoutFrame = styled.iframe<{
  bgColor?: string;
  isDesktopPreview?: boolean;
}>`
  width: 1100px;
  height: 550px;
  border: 0;
  transform: ${(props) => (props.isDesktopPreview ? 'scale(0.85)' : 'scale(0.85)')};
  transform-origin: center;
  transition: transform 0.5s ease-in-out;
  background: ${(props) => props.bgColor || 'none'};
  flex-shrink: 0;
  margin: 0;
  padding: 0;
`;

export const PreviewBadge = styled.div<{
  theme: Theme;
  isEditable: boolean;
}>`
  position: sticky;
  top: 0;
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
  padding: 4px 24px;
  background-color: ${({ isEditable, theme }) =>
    isEditable
      ? theme.colors.feedback.background.notice.intense
      : theme.colors.feedback.background.positive.intense};
  color: ${({ theme }) => theme.colors.surface.text.staticWhite.normal};
  font-size: ${({ theme }) => theme.typography.fonts.size[75]}px;
  font-weight: ${({ theme }) => theme.typography.fonts.weight.regular};
  margin-bottom: ${({ theme }) => theme.spacing[1]}px;
`;

export const FrameContainer = styled.div<{
  isDesktopPreview: boolean;
}>`
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: 550px;
  padding: 0;
  margin: 0;
`;

export const ScrollablePreview = styled.div<{
  isDesktopPreview: boolean;
}>`
  position: relative;
  overflow: hidden;
  height: 550px;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0;
  margin: 0;
`;

export const VideoGuideWrapper = styled.div`
  position: absolute;
  right: 10px;
  top: 10px;
  display: none;
`;

export const CustomisationButtonsWrapper = styled.div`
  display: flex;
  align-items: flex-start;
  gap: 20px;
  padding: 4px 60px; // Keep 28px side padding to match iframe container
  width: 100%;
  background: transparent;
`;

export const ButtonContainer = styled.div`
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: white;
  border-radius: 20px;
  padding: 8px 8px;
  cursor: default;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: relative;

  &:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
`;

export const ColorButton = styled.div`
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: ${(props) => props.color};
  cursor: pointer;
  position: relative;
  overflow: hidden;
  border: 2px solid #e9ecef;

  input[type='color'] {
    opacity: 0;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
  }
`;

export const FontSelector = styled.select`
  position: absolute;
  top: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #dee2e6;
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  z-index: 10;
  cursor: pointer;
`;

export const StyledSpan = styled.span`
  padding-left: 4px;
  cursor: pointer;
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