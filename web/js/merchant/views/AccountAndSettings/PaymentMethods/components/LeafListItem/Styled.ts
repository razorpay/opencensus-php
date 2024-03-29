import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const LeafListItemSection = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  flex-direction: column;
  row-gap: ${theme.spacing[8]}px;
  padding: ${theme.spacing[7]}px 0;
  background: #ffffff !important;
  border: none !important;
  height: auto !important;

  @media screen and (max-width: 768px) {
    padding: ${theme.spacing[7]}px 0 ${theme.spacing[5]}px;
  }
`,
);

export const LeafListItem = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding: ${theme.spacing[7]}px !important;
  background: ${theme.colors.surface.background.gray.moderate} !important;
  border-radius: 0px ${theme.spacing[2]}px ${theme.spacing[2]}px ${theme.spacing[2]}px;
  border-bottom: none !important;

  &.level-3 {
    .change-account-action {
      display: flex;
      column-gap: ${theme.spacing[4]}px;
    }

    .instruments-methods-container {
      border: 0 !important;
      margin: -${theme.spacing[7]}px !important;
    }

    .top-container .left-text-wrapper h4,
    .method-list-container .instrument-row .text-wraper .name {
      color: ${theme.colors.surface.text.gray.subtle}; !important;
      font-weight: 600 !important;
      font-size: ${theme.spacing[5]}px !important;
      line-height: ${theme.spacing[7]}px !important;
    }

    .top-container .left-text-wrapper p,
    .method-list-container .instrument-row .text-wraper .description {
      font-size: 14px !important;
      line-height: ${theme.spacing[6]}px !important;
    }

    .method-list-container {
      margin: 0 18px;
      padding-bottom: ${theme.spacing[5]}px;

      .instrument-row {
        border-bottom: none !important;
        padding: ${theme.spacing[5]}px 0 !important;
        margin: 0 -10px;
      }
    }
  }

  @media screen and (max-width: 768px) {
    padding: ${theme.spacing[5]}px !important;
  }
`,
);

export const StyledLeafListItemHeader = styled.div(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.gray.subtle};;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  position: relative;

  h3 {
    font-weight: 600;
    font-size: ${theme.spacing[5]}px;
    line-height: ${theme.spacing[8]}px;
    margin-bottom: ${theme.spacing[3]}px;
  }

  p {
    font-size: 14px;
    line-height: ${theme.spacing[6]}px;
  }

  .toggler-btn {
    display: flex;
    align-items: center;
    column-gap: 9.5px;

    b {
      color: ${theme.colors.surface.text.gray.muted};
    }

    button {
      &::before {
        border-radius: ${theme.spacing[3]}px;
      }

      &::after {
        border-radius: 10px;
      }
    }
  }

  @media screen and (max-width: 768px) {
    flex-direction: column;
    row-gap: ${theme.spacing[5]}px;
    align-items: initial;

    .toggler-btn {
      position: absolute;
      right: 0;
      top: ${theme.spacing[3]}px;
    }
  }
`,
);

export const StyledLeafListItemHeaderInfo = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-bottom: ${theme.spacing[3]}px;
`,
);
