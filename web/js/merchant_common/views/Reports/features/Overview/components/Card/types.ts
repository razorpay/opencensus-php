export interface OverviewLinksParams {
  isSchedulesEnabled: boolean;
}

export type OverviewLinksFnReturnType = {
  type: string;
  label: string;
}[];
