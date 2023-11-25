import styled from 'styled-components';
import lazy from 'merchant/routes/LazyLoader';
import { Theme } from '@razorpay/blade/components';

const Configuration = lazy(
  () => import(/* webpackChunkName: "Configuration" */ 'merchant/views/Settings/Configuration'),
);

export const StyledConfiguration = styled(Configuration)`
  &#settings-content {
    max-width: 100%;
    padding: 0;
  }
  &.content-sm {
    min-height: auto;
  }
  .panel {
    margin-bottom: 0;
  }
  .scroll-into-view {
    border: none;
  }
`;

export const StyledHeader = styled.header`
  && {
    border-top: 0;
  }
`;

export const StyledContent = styled.div(
  ({ theme }: { theme: Theme }) => `
  &.content {
    margin-top: ${theme.spacing[6]}px;
  }
`,
);

export const StyledDivider = styled.div`
  margin: 25px 0px 0px 0px;
`;

export const StyledTabContentContainer = styled.div<{ hideStyle?: boolean }>(
  ({ theme, hideStyle }: { theme: Theme; hideStyle?: boolean }) =>
    !hideStyle &&
    `
  && {
    background: white;
    padding: ${theme.spacing[7]}px;
    @media screen and (max-width: 768px) {
      padding: 0;
    }
  }
  .list-group,
  .panel {
    margin-bottom: 0;
  }
`,
);

export const StyledTabContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  @media screen and (max-width: 768px) {
    .tabbed-container {
      padding: 10px 0 0;
    }

    ${StyledDivider} {
      padding: 0 ${theme.spacing[5]}px;
    }
  }

  .firc-settings-banner {
    height: 82px;
    width: 100%;
    margin: ${theme.spacing[7]}px 0;

    img {
      height: 100%;
      width: 50px;
    }

    .firc-settings-banner-icon {
      transform: scale(1.75);
      height: ${theme.spacing[7]}px;
      width: ${theme.spacing[7]}px;
    }

    div {
      font-size: ${theme.spacing[6]}px;
    }
    .link {
      font-size: 18px;
    }
  }
`,
);
