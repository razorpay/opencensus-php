import React from 'react';

const SelectContext = React.createContext(null);

export const useSelectContext = () => {
  const selectContext = React.useContext(SelectContext);
  if (selectContext === undefined) {
    throw new Error('useSelectContext must be used within a SelectProvider');
  }
  return selectContext;
};

export default SelectContext;
