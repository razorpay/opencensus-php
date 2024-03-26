import create from 'zustand';
import { devtools } from 'zustand/middleware';

type MarketPlaceStore = {
  isPartnerPlatformFeeEnabled: boolean;
  setIsPartnerPlatformFeeEnabled: (value: boolean) => void;
};
const initialStore: Pick<MarketPlaceStore, 'isPartnerPlatformFeeEnabled'> = {
  isPartnerPlatformFeeEnabled: false,
};

export const useMarketplaceStore = create<MarketPlaceStore>(
  devtools((set) => ({
    ...initialStore,
    setIsPartnerPlatformFeeEnabled: (value: boolean): void =>
      set({ isPartnerPlatformFeeEnabled: value }),
  })),
);
