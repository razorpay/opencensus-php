import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ProfileContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    border-radius: ${theme.spacing[2]}px;
    max-width: 1136px;
    background: ${theme.colors.surface.background.level2.lowContrast};
    box-shadow: 0px 1px 2px rgba(21, 45, 75, 0.2), 0px 0px 1px rgba(21, 45, 75, 0.2);
    width: 100%;
    min-width: 750px;
    display: flex;
  `,
);

export const PersonalInfoContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    background: ${theme.colors.brand.primary[300]};
    min-width: 370px;
    border-radius: ${theme.spacing[2]}px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: ${theme.spacing[7]}px;
    width: 370px;
    @media screen and (max-width: 768px) {
      width: 100%;
      padding: ${theme.spacing[4]}px;
      min-width: 100%;
    }
  `,
);
