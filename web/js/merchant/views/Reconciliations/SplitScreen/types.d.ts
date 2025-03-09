interface IData {
  id: number;
  [key: string]: unknown;
}

interface MerchantSources {
  id: string;
  name: string;
}

interface DataGridProps {
  reconFilter: Record<string, any>;
  dateRange: Record<string, any>;
  sourceId: string;
  setIsDrawerOpen: React.Dispatch<React.SetStateAction<boolean>>;
  setClickedRow: React.Dispatch<React.SetStateAction<IData | null>>;
  merchantSources: MerchantSources[];
  setReconPairSources: React.Dispatch<React.SetStateAction<string[]>>;
}

interface FilterModalProps {
  isFilterModalOpen: boolean;
  setIsFilterModalOpen: React.Dispatch<React.SetStateAction<boolean>>;
  reconFilter: Record<string, string>;
  setReconFilter: React.Dispatch<React.SetStateAction<Record<string, string>>>;
}

interface SplitScreenDrawerProps {
  isDrawerOpen: boolean;
  setIsDrawerOpen: React.Dispatch<React.SetStateAction<boolean>>;
  recordId: string;
}

interface UseDragOptions {
  throttleDelay: number;
  minWidth?: number;
  maxWidth?: number;
  initialWidth?: number;
}

interface UseDragState {
  isDragging: boolean;
  initialOffset: number;
  initialWidth: number;
}

interface UseDragReturnType {
  dynamicWidth: number;
  handleDragMouseDown: (e: React.MouseEvent) => void;
  isDragging: boolean;
}

export type {
  IData,
  DataGridProps,
  FilterModalProps,
  SplitScreenDrawerProps,
  UseDragOptions,
  UseDragState,
  UseDragReturnType,
};
