import React from 'react';

interface SteppedSplitPaneContext {
  screen: string;
  selectedStepId: string | null;
  setSelectedStepId: React.Dispatch<React.SetStateAction<string | null>>;
  selectedStepIndex: number | null;
  setSelectedStepIndex: React.Dispatch<React.SetStateAction<number | null>>;
  selectedStepTitle: string | null;
  setSelectedStepTitle: React.Dispatch<React.SetStateAction<string | null>>;
  suggestedProduct: string | null;
  pitchingType: string | null;
}

export const SteppedSplitPaneContext = React.createContext({} as SteppedSplitPaneContext);

export const useSteppedSplitPaneContext = () => React.useContext(SteppedSplitPaneContext);
