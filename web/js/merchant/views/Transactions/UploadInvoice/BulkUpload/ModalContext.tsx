import React, { createContext, useState } from 'react';

import { BatchError, ModalContextType, ModalProviderInterface } from './types';

export const modalContext = createContext<ModalContextType | null>(null);

const ModalProvider: React.FC<ModalProviderInterface> = ({ children }) => {
  //states
  const [currentTab, setCurrentTab] = useState(0);
  const [clientErrors, setClientErrors] = useState<Array<BatchError>>([]);
  const [isUploading, setIsUploading] = useState(false);
  const [files, setFiles] = useState<Array<File>>([]);
  const [progress, setProgress] = useState(0);
  const [shouldShowExitPrompt, setShouldShowExitPrompt] = useState(false);

  const value: ModalContextType = {
    currentTab,
    setCurrentTab,
    isUploading,
    setIsUploading,
    files,
    setFiles,
    progress,
    setProgress,
    clientErrors,
    setClientErrors,
    shouldShowExitPrompt,
    setShouldShowExitPrompt,
  };

  return <modalContext.Provider value={value}>{children}</modalContext.Provider>;
};

export default ModalProvider;
