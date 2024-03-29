import styled, { css } from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const StyledCopyButton = styled.button(
  ({ theme }: { theme: Theme }) => `
    border: none;
    background: transparent;
    display: flex;
    column-gap: ${theme.spacing[2]}px;
    align-items: center;
    padding: 0;

    svg {
      opacity: 0;
    }

    &:disabled{
      cursor: default;
    }
  `,
);

export const StyledSettlementListTableHeaderCell = styled.th(
  ({ hasToolTip }: { hasToolTip: boolean }) => `
  ${
    hasToolTip
      ? css`
          p {
            width: max-content;
          }
        `
      : ''
  }
`,
);

export const StyledSettlementListTable = styled.table(
  ({ theme }: { theme: Theme }) => `
    width: 100%;
    
    thead {
        background-color: ${theme.colors.surface.background.gray.moderate};
        height: ${theme.spacing[9]}px;
        ${StyledSettlementListTableHeaderCell}{
          height: 100%;

          p {
            display: flex;
            align-items: center;
            column-gap: 2px;

            span {
              padding-top: 4px;
            }
          }

          &:nth-last-child(3) p {
            justify-content: flex-end;
          }

        }
    }

    tr {
      border: 1px solid ${theme.colors.surface.border.gray.muted};
      border-left: 0px;
      border-right: 0px;
    }

    td, th{
      padding: ${theme.spacing[3]}px 10px;
    }

    @media screen and (max-width: 768px) {
      td, th{
        padding: ${theme.spacing[3]}px ${theme.spacing[2]}px;
      }

      td:first-of-type {
        padding-right: 0;
      }

      td:last-of-type {
        button {
          height: ${theme.spacing[9]}px;
        }
      }

      th:last-of-type p {
        justify-content: flex-end;
        padding-right: ${theme.spacing[6]}px;
      }
    }

`,
);

export const StyledSettlementRow = styled.tr(
  ({ theme }: { theme: Theme }) => `
  &:hover {
    background-color: ${theme.colors.surface.background.gray.moderate};

    ${StyledCopyButton} svg {
      opacity: 1;
    }
  }

  .settlement-breakup-tooltip.rzp-popover.rzp-tooltip .rzp-tooltip-inner{
    padding: 0;
  }
`,
);

export const StyledDivider = styled.div(
  ({ theme }: { theme: Theme }) => `
  height: 1px;    
  background-color: ${theme.colors.surface.border.gray.subtle};
  margin: 0 ${theme.spacing[6]}px;
`,
);

export const StyledWrapper = styled.div`
  @media screen and (max-width: 768px) {
    .pager {
      margin-right: 0 !important;
    }
  }
`;

export const StyledLoaderCell = styled.td`
  height: 200px;

  div {
    justify-content: center;
  }
`;
