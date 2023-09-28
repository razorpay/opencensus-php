import styled from 'styled-components';

export const ShippingSettingsWrapper = styled.div(
  ({ theme }) => `
  margin-top: ${theme.spacing[7]}px;
  padding: ${theme.spacing[7]}px;
`,
);

export const ShippingToggle = styled.div(
  ({ theme }) => `
  margin: ${theme.spacing[5]}px 0;
  .setting-label {
    width: 25%;
  }
  .toggle-status {
    margin-left: 15px;
  }
  .setting-toggle {
    align-items: center;
    label {
      margin-bottom: 0;
    }
    .width-full {
      & > .slabs-container {
        & > .toggler-btn {
          top: 0;
        }
      }
    }
    .toggle-status {
      top: -2px;
    }
  }
  .rzp-popover-content {
    & > .rzp-popover-body {
      & > p {
        line-height: 18px;
      }
    }
  }
  

`,
);
