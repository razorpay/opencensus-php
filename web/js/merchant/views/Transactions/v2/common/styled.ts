import { Theme } from '@razorpay/blade/components';
import { NavLink } from 'react-router-dom';
import styled from 'styled-components';

export const StyledTable = styled.div(
  ({ theme, loading }: { theme: Theme; loading: boolean }) => `
  .transactions-table-v2 {
    .PlaceholderLoader {
      height: ${theme.spacing[5]}px;
      top: ${theme.spacing[1]}px;
    }
    .ClipboardCustom {
      opacity: 0;
    }
    thead > tr > th {
      @media screen and (max-width: ${theme.breakpoints.l}px) {
        &:nth-child(2) {
          text-align: right;
        }
      }
      vertical-align: middle;
      background-color: ${theme.colors.surface.background.level3.highContrast};
    }
    tbody > tr > td {
      height: 52px;
      vertical-align: middle;
    }
    tbody > tr > td:last-child {
      text-align: right;
      width: ${loading ? '100' : theme.spacing[5]}px;
      line-height: ${theme.spacing[0]}px;
    }
    @media screen and (max-width: ${theme.breakpoints.l}px) {
      tbody > tr > td {
        padding-left: 0;
        padding-right: 0;
      }
      tbody > tr > td:nth-child(2) {
        text-align: right;
      }
      tbody > tr > td:last-child {
        width: auto;
        button > div > div:first-child {
          display: none;
        }
      }
    }
    @media screen and (min-width: ${theme.breakpoints.l}px) and (max-width: ${
    theme.breakpoints.xl
  }px) {
      tbody > tr > td:nth-child(2) {
        .bank-rrn > p,
        [data-testid='payment-id'] > p:first-child {
          max-width: 75px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
      }
    }
    tbody > tr:hover {
      .ClipboardCustom {
        opacity: 1;
        cursor: pointer;
      }
      background-color: ${theme.colors.surface.background.level3.lowContrast};
      border-bottom: 1px solid rgba(121, 135, 156, 0.18);
      box-shadow: 0px 12px 16px -4px rgba(19, 38, 68, 0.08), 0px 4px 6px -2px rgba(19, 38, 68, 0.03);
    }
  }
  .pager {
    .btn-group > .btn:first-child {
      margin-right: ${theme.spacing[2]}px;
    }
  }
`,
);

export const StyledDateRangePicker = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  button:first-child {
    border-top-right-radius: unset;
    border-bottom-right-radius: unset;
    &:focus {
      box-shadow: none;
    }
  }
  .rzp-daterange-picker {
    border-top-left-radius: unset;
    border-bottom-left-radius: unset;
    border-left: 0;
    > .daterange-container {
      height: 34px;
      border-left: 0;
    }
  }
  @media screen and (max-width: ${theme.breakpoints.m}px) {
    .rzp-daterange-picker > .daterange-container {
      height: 36px;
      margin-top: ${theme.spacing[0]};
    }
  }
  .rzp-daterange-picker .DateRangePickerInput .DateInput_input {
    height: 34px;
    border: 0;
  }
  .rzp-daterange-picker .DateRangePickerInput .DateRangePickerInput_arrow {
    line-height: 34px;
  }
  .rzp-daterange-picker .DateRangePickerInput > .DateInput:first-child input {
    padding: ${theme.spacing[2]}px ${theme.spacing[3]}px;
  }
  .rzp-daterange-picker > div .DateRangePickerInput .DateInput:first-child {
    width: 110px;
  }
`,
);

export const StyledListFilter = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  column-gap: 8px;
  row-gap: 12px;
  margin-bottom: ${theme.spacing[4]}px;
  flex-wrap: wrap;
  white-space: nowrap;
  justify-content: space-between;
`,
);

export const StyledSubListFilter = styled.div`
  display: flex;
  column-gap: 8px;
  row-gap: 12px;
  flex-wrap: wrap;
  @media screen and (max-width: ${({ theme }) => theme.breakpoints.m}px) {
    flex-wrap: nowrap;
    height: 50px;
    align-items: center;
  }
`;

export const StyledSearchByFilter = styled.div(
  ({ theme }: { theme: Theme }) => `
  label[data-blade-component='form-label'] {
    display: none;
  }
  [data-blade-component='select-input'] button {
    min-width: 110px;
  }
  @media screen and (max-width: ${theme.breakpoints.m}px) {
    [data-blade-component='select-input'] button {
      min-width: 88px;
      max-width: 88px;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      padding-right: ${theme.spacing[0]};
    }
    [data-blade-component='textinput'] + button {
      padding-left: ${theme.spacing[2]}px;
      padding-right: ${theme.spacing[2]}px;
    }
  }
  .country-code-input > div > span {
    padding: ${theme.spacing[3]}px  ${theme.spacing[2]}px;
    border-left: none;
    border-right: none;
    border-top: none;
    border-bottom-left-radius: ${theme.spacing[0]};
    border-bottom-right-radius: ${theme.spacing[0]};
    background: ${theme.colors.brand.gray.a50.lowContrast};
  }
`,
);

export const StyledTabHeader = styled.header(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  text-align: center;
  font-size: ${theme.spacing[6]}px;
  &&& > .active {
    color: ${theme.colors.action.text.link.default};
    border-color: ${theme.colors.action.text.link.default};
    pointer-events: none;
  }
`,
);

export const StyledTabItem = styled(NavLink)(
  ({ theme }: { theme: Theme }) => `
  &&& {
    padding: ${theme.spacing[0]} ${theme.spacing[5]}px ;
    margin: ${theme.spacing[0]};
    color: ${theme.colors.surface.text.subdued.lowContrast};
    flex: 1;
  }
`,
);

export const StyledContent = styled.div(
  ({ theme }: { theme: Theme }) => `
  .tabbed-header-actions {
    margin: -10px -${theme.spacing[5]}px ${theme.spacing[5]}px;
  }  
`,
);
