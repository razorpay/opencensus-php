export interface NavItemsMapping {
  id: string;
  title: string;
  component: JSX.Element;
}

export interface NavItemProps {
  title: string;
  onTabClick: () => void;
  active: boolean;
}
