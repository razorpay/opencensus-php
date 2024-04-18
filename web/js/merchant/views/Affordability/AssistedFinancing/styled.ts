import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const PaymentMethodContainer = styled.div(
  ({ theme, isSelected }: { theme: Theme; isSelected: boolean }) => `
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
    padding: ${theme.spacing[4]}px;
    background-color: ${
      isSelected
        ? theme.colors.surface.background.primary.subtle
        : theme.colors.surface.background.gray.intense
    };
  `,
);

export const PaymentLinkBanner = styled.div(
  ({ theme }: { theme: Theme }) => `
    display: flex;
    flex-direction: column;
    border-width: ${theme.border.width.thin}px;
    border-color: ${theme.colors.feedback.border.information.subtle};
    width: 100%;
    border-radius: ${theme.border.radius.medium}px;
    padding: ${theme.spacing[3]}px;
    position: relative;
    background-color: ${theme.colors.feedback.background.information.subtle};`,
);

export const StyledImage = styled.img(
  ({ notEligible }: { notEligible: boolean }) => `
    filter: ${notEligible ? 'grayscale(1)' : 'grayscale(0)'}} ;
    maxWidth: 100%;
    maxHeight: 100%;
    objectFit: contain
  `,
);

export const PaymentLinkModalContentContainer = styled.div(
  ({ theme, isSuccess }: { theme: Theme; isSuccess: boolean }) => `
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: ${theme.spacing[5]}px;
    margin-bottom: ${theme.spacing[6]}px;
    border-radius: ${theme.border.radius.medium}px;
    background-color:${
      isSuccess
        ? theme.colors.feedback.background.positive.subtle
        : theme.colors.feedback.background.negative.subtle
    };
  `,
);
