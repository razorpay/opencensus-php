export type RightChildrenProps = {
  isChecked: boolean;
  onChange: (event: { isChecked: boolean }) => void;
  accessibilityLabel: string;
};

export type FeatureToggleProps = {
  isChecked: boolean;
  feature: string;
  title: string;
  subTitle: string;
  toggleHandler?: (isChecked: boolean) => void;
  extraItems?: React.ReactNode;
};

export type LineItemsProps = {
  title?: string | ReactNode;
  subTitle?: string | ReactNode;
  rightChildren?: ReactNode;
  extraItems?: ReactNode;
};
