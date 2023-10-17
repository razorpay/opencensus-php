import { useContext } from 'react';
import { FIRSContext } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/context';
import { FirsContextType } from 'merchant/views/AccountAndSettings/InternationalSettings/typings';

const useFirsContext = () => {
  return useContext(FIRSContext) as FirsContextType;
};

export default useFirsContext;
