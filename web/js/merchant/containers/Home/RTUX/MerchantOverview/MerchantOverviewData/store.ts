import create from 'zustand';

type SettlementHovered = {
  isHovered: boolean;
  setIsHovered: (isHovered: boolean) => void;
};

export const useIsSettlementHovered = create<SettlementHovered>((set) => ({
  isHovered: false,
  setIsHovered: (isHovered: boolean) => set({ isHovered }),
}));
