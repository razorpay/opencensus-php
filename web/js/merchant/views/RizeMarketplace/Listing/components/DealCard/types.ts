export interface DealCardProps {
  slug: string;
  offer: string;
  couponCode?: string;
  availLink: string;
  defaultIsAvailed?: boolean;
}

export interface SmallDealCardProps extends DealCardProps {
  isSticky?: boolean;
}
