import React, { createContext, useState } from 'react';

import { useSplitzService } from 'common/splitz';
import { PopupType } from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import { fetchFirsData } from 'merchant/views/AccountAndSettings/InternationalSettings/services';
import {
  FirsContextType,
  FirsDataType,
  PopupDataType,
} from 'merchant/views/AccountAndSettings/InternationalSettings/typings';
import {
  checkFirsData,
  isRequestFirsEnabled as isFirsExpEnabled,
} from 'merchant/views/AccountAndSettings/InternationalSettings/utils';

export const FIRSContext = createContext<FirsContextType | undefined>(undefined);

const FIRSProvider = ({ children }): JSX.Element => {
  const [listYear, setListYear] = useState(new Date().getFullYear());
  const [firsData, setFirsData] = useState<FirsDataType>({});
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [popupData, setPopupData] = useState<PopupDataType>({
    isOpen: false,
    type: PopupType.DOWNLOAD_FIRS,
    month: '',
    year: new Date().getFullYear(),
  });

  /* FIRS Experiment */
  const {
    abExperiments: { firs_request },
  } = useSplitzService();
  const isRequestFirsEnabled = isFirsExpEnabled(firs_request);

  const getFirsData = async (year: number, month?: string, onSuccess?: () => void) => {
    try {
      const data = checkFirsData(firsData, year, month);
      if (data) {
        onSuccess?.();
        return;
      }
      setIsLoading(true);
      const response = await fetchFirsData(year);
      setFirsData((prevData) => ({ ...prevData, ...response }));
      onSuccess?.();
    } catch (error) {
      //api errors will always be string -- check services file
      if (typeof error === 'object') setError(error?.toString() ?? '');
    } finally {
      setIsLoading(false);
    }
  };

  const value: FirsContextType = {
    listYear,
    setListYear,
    firsData,
    setFirsData,
    getFirsData,
    isLoading,
    setIsLoading,
    error,
    setError,
    popupData,
    setPopupData,
    isRequestFirsEnabled,
  };

  return <FIRSContext.Provider value={value}>{children}</FIRSContext.Provider>;
};

export default FIRSProvider;
