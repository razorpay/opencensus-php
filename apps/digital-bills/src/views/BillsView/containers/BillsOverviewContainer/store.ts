import create from 'zustand';
import { SelectedOverviewCategory } from './types';

type BillsOverviewState = {
  selectedOverviewCategory: SelectedOverviewCategory;
  isGraphExpanded: boolean;
  setSelectedOverviewCategory: (type: SelectedOverviewCategory) => void;
  expandGraph: () => void;
  collapseGraph: () => void;
};

export const useBillsOverviewStore = create<BillsOverviewState>((set) => ({
  selectedOverviewCategory: null,
  isGraphExpanded: false,
  setSelectedOverviewCategory: (type: SelectedOverviewCategory) =>
    set((state) => ({ ...state, selectedOverviewCategory: type })),
  expandGraph: () =>
    set((state) => ({
      ...state,
      isGraphExpanded: true,
    })),
  collapseGraph: () =>
    set((state) => ({
      ...state,
      salesAggrType: null,
      isGraphExpanded: false,
    })),
}));
