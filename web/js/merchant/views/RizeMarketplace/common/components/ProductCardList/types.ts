import { ProductCardTileProps } from 'merchant/views/RizeMarketplace/common/components/ProductCardTile/types';

export type ProductCardListProps = Omit<
  ProductCardTileProps,
  'showKnowMoreCTA' | 'truncateExcerpt'
>;
