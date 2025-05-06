import { Mode } from './types';

type GetCurrentModeType = {
  mode: Mode;
  partnerMode: Mode;
  selectedProduct: 'payments_top_navigation_item' | 'partners_top_navigation_item';
};

export const getCurrentMode = ({ mode, partnerMode, selectedProduct }: GetCurrentModeType) => {
  if (selectedProduct === 'payments_top_navigation_item') {
    return mode;
  }
  if (selectedProduct === 'partners_top_navigation_item') {
    return partnerMode;
  }
  return mode;
};
