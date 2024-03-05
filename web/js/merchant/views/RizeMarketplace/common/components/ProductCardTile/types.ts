export interface ProductCardTileProps {
  name: string;
  slug: string;
  logoSrc: string;
  category: string;
  excerpt: string;
  offer: string;
  target?: string;
  showKnowMoreCTA?: boolean;
  truncateExcerpt?: number;
  onClick: () => void;
}
