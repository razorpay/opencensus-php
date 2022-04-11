export interface ProductListItemT {
  icon: string;
  onClickCTA: () => void;
  ctaText: string;
  title: string;
  subTitle: string | JSX.Element;
}

interface openModalArgs {
  size: string;
  component: JSX.Element;
}

export type OpenModalT = (arg: openModalArgs) => void;
