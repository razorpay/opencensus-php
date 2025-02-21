import { Theme } from '@razorpay/blade/components';
import { makeMotionTime } from '@razorpay/blade/utils';
import styled from 'styled-components';

export const SortableItem = styled.div(
  ({ transform, transition }: { transform: string; transition: string }) => `
    transition: ${transition};
    transform: ${transform};
  `,
);

export const StyledButton = styled.button(
  ({ cursor }: { cursor: string }) => `
    border: none;
    background-color: transparent;
    padding: 0px;
    cursor: ${cursor ?? 'pointer'};
`,
);

export const SlideUp = styled.div(
  ({ isVisible, theme }: { isVisible: boolean; theme: Theme }) => `
    transform: ${isVisible ? 'translateY(0)' : 'translateY(100%)'};
    opacity: ${isVisible ? 1 : 0};
    display: flex;
    justify-content: flex-end;
    width: 100%;
    background-color: white;
    transition: ${
      isVisible
        ? `transform ${makeMotionTime(theme.motion.duration.gentle)} ${
            theme.motion.easing.entrance
          }, opacity ${makeMotionTime(theme.motion.duration.gentle)} ${
            theme.motion.easing.entrance
          }`
        : `transform ${makeMotionTime(theme.motion.duration.gentle)} ${
            theme.motion.easing.exit
          }, opacity ${makeMotionTime(theme.motion.duration.gentle)} ${theme.motion.easing.exit}`
    };
    
`,
);
