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
            border-bottom: 1.25px solid ${theme.colors.interactive.text.primary.normal};
          }
          p {
            color: ${theme.colors.interactive.text.primary.normal};
          }
        }
        a {
          border: none;
          border-bottom: 0.75px solid ${theme.colors.surface.text.gray.muted};
          p {
            color: ${theme.colors.surface.text.gray.muted};
          }
        }
      }
    }
  }
`,
);
