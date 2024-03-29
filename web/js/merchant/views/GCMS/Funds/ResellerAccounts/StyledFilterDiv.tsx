import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledFilterDiv = styled.div(
  ({ theme }: { theme: Theme }) => `
  .gcms-resellers-filter-group.all-time-filter-selected {
    .daterange-container {
      display: none;
    }
  }
  .filter-buttons-wrapper{
    display: flex;
    column-gap: ${theme.spacing[3]}px;
  }
  @media screen and (max-width: 768px) {
    .presets-container.pull-left {
      width: calc(100% - 30px);
    }
    .datepicker-group .PowerSelect {
      width: 100%;
    }
    .rzp-daterange-picker > div.daterange-container{
      margin-top: ${theme.spacing[3]}px;
    }
    .list-filter-container{
      padding-right: ${theme.spacing[7]}px;
    }
    .list-filter-item.btn-toolbar{
      margin-top: 0px !important;
      width: 100%;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      > .btn:first-child {
        border: transparent;
        background: transparent;
        color: ${theme.colors.interactive.text.primary.subtle};
        font-size: 14px;
        font-weight: 600;
        width: 160px;
        padding-left: 0;
        text-align: left;
        i {
          font-size: 1.25rem;
          line-height: 0.5;
          margin-left: ${theme.spacing[3]}px;
        }
      }
    }
    .filter-buttons-wrapper{
      margin-top: ${theme.spacing[5]}px;
    }
  }
  
`,
);
