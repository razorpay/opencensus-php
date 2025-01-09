export type RightChildrenProps = {
  isChecked: boolean;
  onChange: (event: { isChecked: boolean }) => void;
  accessibilityLabel: string;
};

export type FeatureToggleProps = {
  isChecked: boolean;
  feature: string;
  isFeature?: boolean;
  title: string | React.ReactNode;
  subTitle: string;
  toggleHandler?: (isChecked: boolean) => void;
  extraItems?: React.ReactNode;
  blockData?: any;
  badgeText?: React.ReactNode;
  subSectionName?: string;
};

export type LineItemsProps = {
  title?: string | React.ReactNode;
  subTitle?: string | React.ReactNode;
  rightChildren?: React.ReactNode;
  extraItems?: React.ReactNode;
  blockData?: any;
  subSectionName?: string;
  showFeedback?: boolean;
};
