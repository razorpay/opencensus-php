import styled from 'styled-components';
import type { Theme } from '@razorpay/blade/components';
import type { EcosystemDowntimesStatusType } from 'merchant/views/EcosystemDowntimes/types';

const COLOR_MAP = {
  background: {
    main: (colors) => colors.surface.background.gray.moderate,
    layer: (colors) => colors.surface.background.gray.subtle,
    positive: (colors) => colors.feedback.background.positive.subtle,
    notice: (colors) => colors.feedback.background.notice.subtle,
    negative: (colors) => colors.feedback.background.negative.subtle,
    information: (colors) => colors.surface.background.gray.subtle,
  },
  icon: {
    neutral: (colors) => colors.feedback.icon.neutral.intense,
    positive: (colors) => colors.feedback.icon.positive.intense,
    notice: (colors) => colors.feedback.icon.notice.intense,
    negative: (colors) => colors.feedback.icon.negative.intense,
  },
  action: {
    enabled: (colors) => colors.interactive.text.primary.normal,
    disabled: (colors) => colors.interactive.text.primary.disabled,
  },
  border: {
    focused: (colors) => colors.interactive.border.primary.default,
    separator: (colors) => colors.interactive.background.neutral.default,
  },
};

export const MethodsContainerStyled = styled.main(
  ({ theme }: { theme: Theme }) => `
    height: 90vh;
    overflow-y: scroll;
    padding-bottom: ${theme.spacing[8]}px;
  `,
);

export const MethodsGrid = styled.div(
  ({ theme }: { theme: Theme }) => `
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
    padding-right: ${theme.spacing[5]}px;
    grid-gap: ${theme.spacing[5]}px;
    margin-bottom:${theme.spacing[8]}px;
    > div {
      margin: ${theme.spacing[5]}px 0 0;
    }
`,
);

export const EcosystemDowntimeContainer = styled.main(
  ({ theme }: { theme: Theme }) => `
    padding: ${theme.spacing[5]}px 0 ${theme.spacing[5]}px ${theme.spacing[5]}px;
    background-color: ${COLOR_MAP.background.main(theme.colors)};
    height: 100vh;
    width: 75vw;
    @media screen and (max-width: ${theme.breakpoints.l}px) {
      width: 65vw;
    }
    @media screen and (max-width: ${theme.breakpoints.m}px) {
      width: 100vw;
    }
    .ecosystem-health-error {
      margin-top: ${theme.spacing[5]}px;
      margin-right: ${theme.spacing[5]}px;
    }
`,
);

export const MethodName = styled.div(
  ({ theme }: { theme: Theme }) => `
    position: relative;
    padding: 0 ${theme.spacing[3]}px;
    ::before {
      content: '';
      background-color: ${COLOR_MAP.border.focused(theme.colors)};
      position: absolute;
      width: 2.5px;
      height: 24px;
      left: 0;
    }
`,
);

export const MethodsListContainer = styled.div`
  > ul {
    padding: 0;
    list-style: none;
  }
`;

export const InstrumentItem = styled.div(
  ({ status, theme }: { status: EcosystemDowntimesStatusType; theme: Theme }) => `
    cursor: pointer;
    padding: ${theme.spacing[3]}px;
    display: flex;
    align-items: center;
    margin: ${theme.spacing[3]}px 0;
    background-color: ${COLOR_MAP.background[status?.colorKey || 'layer']?.(theme.colors)};
    .instrument-logo {
      height: 35px;
      width: 36px;
      margin-right: ${theme.spacing[3]}px;
    }
    .instrument-status {
      margin-left: auto;
    }
    :hover {
      opacity: 0.7; 
    }
`,
);
export const ShimmerGroup = styled.div`
  position: relative;
  display: flex;
  flex-direction: column;
`;

export const EcosystemLoaderContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    height: 90vh;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
    > div span {
      margin: ${theme.spacing[5]}px ${theme.spacing[8]}px 0 0;
    }
`,
);

export const EcosystemHealthHeading = styled.div(
  ({ theme }: { theme: Theme }) => `
    padding: 0 0 ${theme.spacing[3]}px;
    position: sticky;
    top: 0;
    background-color: ${COLOR_MAP.background.main(theme.colors)};
    z-index: 1;
    gap: ${theme.spacing[3]}px;
    display: flex;
    align-items: center;
    @media screen and (max-width: ${theme.breakpoints.s}px) {
      min-height: 60px;
      display: block;
    }
`,
);

export const EcosystemOverallSummaryContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    padding: ${theme.spacing[3]}px;
    border-radius: ${theme.border.radius.medium}px;
    border: 1px solid ${COLOR_MAP.border.focused(theme.colors)};
    margin: ${theme.spacing[5]}px ${theme.spacing[5]}px ${theme.spacing[3]}px 0;
    display: flex;
    align-items: center;
    background-color: ${COLOR_MAP.background.layer(theme.colors)};
    .icon-container {
      margin-right: ${theme.spacing[3]}px;
      > img {
        height: 20px;
        width: 20px;
      }
    }
`,
);

export const EcosystemMethodSummaryContainer = styled.div(
  ({ isExpanded, theme }: { isExpanded?: boolean; theme: Theme }) => `
    border-radius: ${theme.border.radius.medium}px;
    border: ${theme.border.width.thin}px solid ${COLOR_MAP.border.focused(theme.colors)};
    margin: ${theme.spacing[5]}px 0;
    background-color: ${COLOR_MAP.background.layer(theme.colors)};
    > ul {
      list-style: none;
      padding: 0;
    }
    .downtime-summary-item {
      margin: ${theme.spacing[3]}px;;
      .downtime-summary-header-container {
        display: flex;
        align-items: center;
        gap: ${theme.spacing[2] + 0.5}px;
        margin-bottom: ${theme.spacing[1]}px;
      }
    }
    .downtime-summary-footer {
      display: flex;
      padding: 0 ${theme.spacing[3]}px;
      margin-bottom: ${theme.spacing[3]}px;
      justify-content: ${isExpanded ? 'right' : 'space-between'};
      > div:nth-child(${isExpanded ? 1 : 2}) {
        color: ${COLOR_MAP.action.enabled(theme.colors)};
        cursor: pointer;
        display: flex;
        align-items: center;
      }
    }
`,
);

export const EcosystemStatusIconContainer = styled.div(
  ({
    height,
    width,
    bgColorKey,
    theme,
  }: {
    height: number;
    width: number;
    bgColorKey: string;
    theme: Theme;
  }) => `
    display: flex;
    align-items: center;
    justify-content: center;
    height: ${height || 30}px;
    width: ${width || 30}px;
    background-color: ${COLOR_MAP.icon?.[bgColorKey || 'neutral']?.(theme.colors)};
    border-radius:${theme.border.radius.round};
`,
);

export const EcosystemRefreshNudgeContainer = styled.div(
  ({ isRefreshEnabled, theme }: { isRefreshEnabled: boolean; theme: Theme }) => `
  display: flex;
  align-items: center;
  .last-updated-at {
    margin-right:${theme.spacing[2]}px;
  }
  .refresh-btn {
    display: flex;
    align-items: center;
    cursor: pointer;
    margin-left:${theme.spacing[3]}px;
    > p {
      margin-left: ${theme.spacing[2]}px;
      color: ${
        isRefreshEnabled
          ? COLOR_MAP.action.enabled(theme.colors)
          : COLOR_MAP.action.disabled(theme.colors)
      }
    }
  }
  .loader div {
    height: 20px;
    width: 20px;
  }
`,
);

export const DowntimeDetailsHeaderStyled = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  margin-top: ${theme.spacing[7]}px;
  .instrument-logo{
    margin-right: ${theme.spacing[3]}px;
  }
  .instrument-details {
    display: flex;
    align-items: center;
    .separator {
      height: 14px;
      width: 2px;
      background-color: ${COLOR_MAP.border.separator(theme.colors)};
      margin: 0 ${theme.spacing[3]}px;
      opacity: 0.65;
    }
  }
`,
);

export const DowntimeSummaryTileStyled = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding: ${theme.spacing[5]}px;
  background-color: ${COLOR_MAP.background.layer(theme.colors)};
`,
);

export const DowntimeTilesContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin: 1rem 0;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(5rem, 1fr));
  grid-gap: ${theme.spacing[5]}px;
`,
);

export const DowntimeHistoryContent = styled.div(
  ({ theme }: { theme: Theme }) => `
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  .downtime-info {
    max-width: 80%;
    @media screen and (max-width: ${theme.breakpoints.s}px) {
      max-width: 54%;
    }
  }
`,
);

export const DowntimeHistoryContainer = styled.div(
  ({ theme, isMobile }: { theme: Theme; isMobile: boolean }) => `
  margin: 1rem 0;
  .previous-downtime-title {
    position: relative;
    ::before {
      content: '';
      background-color: ${COLOR_MAP.border.focused(theme.colors)};
      position: absolute;
      width: 2.5px;
      height: 15.5px;
      left: 0;
      top: 2px;
    }
    p {
      margin-left: ${theme.spacing[2] + 3}px;
    }
  }
  .downtime-timeline {
    min-height: 3vh;
    max-height: ${isMobile ? '23vh' : '30vh'};
    overflow-y: auto;
    padding-right: ${theme.spacing[5]}px;
    margin-top: ${theme.spacing[5]}px;
  }
`,
);
