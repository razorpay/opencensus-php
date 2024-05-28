import { makeMotionTime } from '@razorpay/blade/utils';
import styled, { css } from 'styled-components';

interface RTUXProps {
  isRTUXHomepage: boolean;
  isMobile: boolean;
}

interface SidebarContainerProps extends RTUXProps {
  isVisible: boolean;
}

export const SidebarContainer = styled.div<SidebarContainerProps>(
  ({ theme, isRTUXHomepage, isVisible, isMobile }) => `
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  bottom: 0;
  background-color: ${
    isRTUXHomepage
      ? isMobile
        ? theme.colors.surface.background.cloud.subtle
        : 'transparent'
      : '#2e3345'
  };
  width: ${isRTUXHomepage ? 216 : 248}px;
  z-index: 1111;
  transform: translate(-100%,0);
  transition: transform ${makeMotionTime(theme.motion.delay.short)} ${
    theme.motion.easing.standard.effective
  };
  will-change: transform;
  ${
    isVisible &&
    css`
      transform: translate(0);
    `
  }
`,
);

export const SidebarSection = styled.section<RTUXProps>(
  ({ isRTUXHomepage, isMobile, theme }) => `
  ${
    isRTUXHomepage
      ? ''
      : `&:after {
    content: '';
    position: absolute;
    border: 1px solid #45495a;
    left: 20px;
    right: 20px;
  }`
  }
  a {
    display: block;
    line-height: 50px;
    text-align: center;
    padding: 10px;
    padding-top: 0px;
  }
  background-color: ${
    isRTUXHomepage
      ? isMobile
        ? theme.colors.surface.background.cloud.subtle
        : 'transparent'
      : undefined
  };
`,
);

export const Logo = styled.img`
  max-width: 100%;
  height: auto;
  display: inline-block;
  width: auto;
  height: 28px;
`;

export const Items = styled.div`
  display: flex;
  flex-direction: column;
  gap: 2px;
`;

export const NavContent = styled.div`
  padding-left: 0;
  margin-bottom: 0;
  list-style: none;
`;

export const Navigation = styled.nav<RTUXProps>(
  ({ isRTUXHomepage }) => `
  padding: ${isRTUXHomepage ? `8px` : `15px`} 0 30px;
  overflow-y: auto;
  overflow-x: hidden;
  -ms-overflow-style: none;
  &::-webkit-scrollbar {
    display: none;
  }
`,
);

export const ExternalLink = styled.a`
  height: 29px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  line-height: 21px;
  color: #b4b6bc;
  position: relative;
  &:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
  }
`;

export const SidebarBackgroundOverlay = styled.div(
  ({ theme }) => `
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  z-index: 1110;
  cursor: pointer;
  background-color: ${theme.colors.overlay.background.subtle};
`,
);
