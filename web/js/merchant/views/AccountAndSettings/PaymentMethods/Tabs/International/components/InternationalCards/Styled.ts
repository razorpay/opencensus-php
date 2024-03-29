import { Theme } from '@razorpay/blade/components';
import { Link } from 'react-router-dom';
import styled, { css } from 'styled-components';

export const StyledDisabledInternationalCardsSection = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding: ${theme.spacing[9]}px ${theme.spacing[5]}px ${theme.spacing[5]}px;
  border-top: 1px solid rgba(121, 135, 156, 0.18);
  display: flex;
  flex-direction: row;
  margin-top: ${theme.spacing[7]}px;
  justify-content: space-between;
  align-items: flex-start;

  @media screen and (max-width: 768px) {
    align-items: initial;
    flex-direction: column;
    row-gap: ${theme.spacing[7]}px;
    padding: 28px ${theme.spacing[4]}px ${theme.spacing[6]}px;
    margin-top: ${theme.spacing[5]}px;
  }
`,
);

export const StyledNotActivatedTopSection = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-start;
  row-gap: ${theme.spacing[3]}px;
`,
);

export const StyledDisabledHeading = styled.p(
  ({ theme }: { theme: Theme }) => `
  font-weight: 600;
  font-size: ${theme.spacing[5]}px;
  line-height: ${theme.spacing[7]}px;
  color: ${theme.colors.surface.text.gray.subtle};
`,
);

export const StyledDisabledSubtitle = styled.p(
  ({ theme }: { theme: Theme }) => `
  font-size: 14px;
  line-height: ${theme.spacing[6]}px;
  color: ${theme.colors.surface.text.gray.subtle};
`,
);

export const StyledAlertSection = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-top: ${theme.spacing[3]}px;

  @media screen and (max-width: 768px) {
    margin-top: ${theme.spacing[6]}px;
  }
`,
);

export const StyledICProductsSection = styled.div`
  margin-top: 24px;
`;

export const StyledProductInfo = styled.div(
  ({ theme }: { theme: Theme }) => `
  border-top: 1px solid rgba(121, 135, 156, 0.18);
  padding: ${theme.spacing[8]}px 0;
  font-weight: 400;
  font-size: 14px;
  line-height: ${theme.spacing[6]}px;
  color: ${theme.colors.surface.text.gray.subtle};

  &:nth-child(3) {
    padding-bottom: ${theme.spacing[3]}px;
  }

  .non-3ds-card-activation {
    padding: ${theme.spacing[0]}px;

    .header-title {
      display: flex;
      width: 100%;
      justify-content: space-between;

      strong {
        font-size: ${theme.spacing[5]}px;
      }
    }
  }

  @media screen and (max-width: 768px) {
    padding: ${theme.spacing[7]}px 0;
  }
`,
);

export const StyledProductInfoHeader = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: ${theme.spacing[3]}px;

  @media screen and (max-width: 768px) {
    flex-direction: column;
    align-items: flex-start;
    row-gap: 10px;
    margin-bottom: ${theme.spacing[5]}px;
  }
`,
);

export const StyledProductInfoContent = styled.div(
  ({ theme }: { theme: Theme }) =>
    `
display: flex;
flex-direction: column;
row-gap: ${theme.spacing[3]}px;

p {
  display: flex;
}

span {
  display: flex;
  align-items: center;
}

.rzp-amount .rzp-whole,
.rzp-amount .rzp-paise {
  font-weight: 600;
  color: ${theme.colors.surface.text.gray.subtle};
}

@media screen and (max-width: 768px) {
  row-gap: ${theme.spacing[5]}px;

  p {
    flex-direction: column;
    align-items: flex-start;
    row-gap: ${theme.spacing[3]}px;
  }
}
`,
);

export const StyledProductInfoHeading = styled.p(
  ({ theme }: { theme: Theme }) => `
  font-weight: 600;
  font-size: ${theme.spacing[5]}px;
  line-height: ${theme.spacing[7]}px;;
`,
);

export const StyledEditTransactionLimitLink = styled(Link)(
  ({ theme }: { theme: Theme }) => `
  margin-left: ${theme.spacing[3]}px;
`,
);

export const StyledRequestToActivateButtonWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-top: ${theme.spacing[7]}px;
`,
);

export const StyledUpdateBusinessDetailsContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding: ${theme.spacing[7]}px;
  max-width: 480px;
`,
);

export const StyledUpdateBusinessDetailsContent = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  column-gap: ${theme.spacing[5]}px;
  margin-bottom: ${theme.spacing[8]}px;
  align-items: flex-start;

  svg {
    flex-shrink: 0;
  }

  h4 {
    font-weight: 600;
    font-size: ${theme.spacing[5]}px;
    line-height: ${theme.spacing[7]}px;
    color: ${theme.colors.surface.text.gray.normal};
    margin-bottom: ${theme.spacing[5]}px;
  }

  p {
    font-size: ${theme.spacing[5]}px;
    line-height: ${theme.spacing[7]}px;
    color: ${theme.colors.surface.text.gray.subtle};
  }
`,
);

export const StyledProductOption = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  align-items: center;

  && {
    .Input.Input--checkbox .Input-content {
      padding-right: ${theme.spacing[2]}px;
    }

    svg {
      margin-top: 6px;
    }
  }
`,
);

export const StyledLabelWithToolTip = styled.div(
  ({ theme, required }: { theme: Theme; required: boolean }) => `
    display: flex;
    justify-content: flex-end;
    column-gap: ${theme.spacing[2]}px;
    
    ${
      required
        ? css`
            && {
              span:nth-child(1)::after {
                content: '*';
                font-size: 0.75rem;
                position: absolute;
                margin-left: 1px;
                top: 0;
                color: #f05050;
              }

              &::after {
                content: '';
                display: none;
              }
            }
          `
        : ''
    }  

    span:nth-child(2) {
      margin-left: ${theme.spacing[2]}px;
    }

    @media screen and (max-width: 768px) {
      justify-content: flex-start;
    }
  `,
);
