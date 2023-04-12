import React, { createContext, useContext, useState } from 'react';
import { DatesContextType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';

export const DatesContext = createContext<DatesContextType>({
  dateHoveringOn: null,
  focused: 'startDate',
  setFocused: () => {},
  setDateHoveringOn: () => {},
});

export const useDatesContext = () => useContext(DatesContext);

export const DatesContextProvider = ({ children }): JSX.Element => {
  const [dateHoveringOn, setDateHoveringOn] = useState<moment.Moment | null>(null);
  const [focused, setFocused] = useState<string>('startDate');
  return (
    <DatesContext.Provider
      value={{
        focused,
        dateHoveringOn,
        setFocused,
        setDateHoveringOn,
      }}
    >
      {children}
    </DatesContext.Provider>
  );
};
