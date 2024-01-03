import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const CartButtonContainer = styled.div(
  ({ theme, isMobile }: { theme: Theme; isMobile: boolean }) => `
  position: ${isMobile ? 'absolute' : 'static'};
  top: 15px;
  right: 18px;
  cursor: pointer;
  margin-right: ${isMobile ? theme.spacing[0] : theme.spacing[4]}px; 
`,
);

export const PricingCard = styled.div(
  ({ theme, isSelected }: { theme: Theme; isSelected: boolean }) => `
  flex: 0 1 50%;
  border: ${theme.border.width.thin}px solid ${
    isSelected ? theme.colors.brand.primary[500] : theme.colors.surface.border.normal.lowContrast
  };
  padding: ${theme.spacing[4]}px;
  border-radius: ${theme.border.radius.medium}px;
  cursor: pointer;
  background-color: ${isSelected ? theme.colors.surface.background.level3.lowContrast : 'none'};
  margin-bottom: ${theme.spacing[4]}px;
`,
);

export const CartBackdropStyled = styled.div`
  position: fixed;
  z-index: 5;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: rgb(0, 0, 0, 0.5);
`;

export const CartFooterContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  background-color: ${theme.colors.surface.background.level2.lowContrast};
  position: absolute;
  bottom: 0;
  width:100%;
  min-height: 100px;
  box-shadow: 0px -4px 6px -2px rgba(19, 38, 68, 0.03), 0px -12px 16px -4px rgba(19, 38, 68, 0.08);
  padding: ${theme.spacing[5]}px ${theme.spacing[5]}px ${theme.spacing[8]}px ${theme.spacing[5]}px ;
`,
);
