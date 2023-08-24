import styled from 'styled-components';

// Note: this wrapper is needed because Blade doesn't have Tabs support yet
export const StyledMerchantTabs = styled.div(
  ({ theme }) => `
  .invite-merchant-form {
    &.hide-tabs {
      .nav-tabs {
        display: none;
      }
    }
    .nav-tabs {

      li {
        &.active {
          a {
            border-bottom: 1.5px solid ${theme.colors.action.text.link.active};
          }
          p {
            color: ${theme.colors.action.text.link.active};
          }
        }
        a {
          border: none;
          border-bottom: 1px solid ${theme.colors.surface.text.muted.lowContrast};
          p {
            color: ${theme.colors.surface.text.muted.lowContrast};
          }
        }
      }
    }
  }
`,
);
