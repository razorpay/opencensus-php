export const DashboardModalSizeEnum = {
    REGULER : "regular",
    SMALL:"small",
    MEDIUM:"medium",
    MEDIUMLARGE:"med-large",
    LARGE:"large",
    XLARGE:"xlarge",
    CUSTOM:"custom"
  } as const;
  
  export type DashboardModalSizeEnum = (typeof DashboardModalSizeEnum)[keyof typeof DashboardModalSizeEnum];
  