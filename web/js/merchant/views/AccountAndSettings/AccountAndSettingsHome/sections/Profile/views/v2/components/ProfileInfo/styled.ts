import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ProfilePhotoContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    background: ${theme.colors.surface.background.gray.intense};
    border: 1px solid ${theme.colors.surface.border.gray.muted};
    border-radius: ${theme.spacing[2]}px;
    height: 112px;
    width: 112px;
    display: flex;
    justify-content: center;
    align-items: center;
    i {
      font-size: 300%;
    }
    img {
      height: 60px;
      width: 60px;
    }
  `,
);

export const StyledInitialsImage = styled.div(
  ({ theme }: { theme: Theme }) => `
    font-size: ${theme.spacing[9]}px;
    font-weight: 600;
    color: ${theme.colors.surface.text.gray.muted}
  `,
);
