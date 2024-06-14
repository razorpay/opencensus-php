import { useState } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import { MODULE_NAME } from '../utils/constants';

const useAPI = <T1, T2>(
  initialState: T1,
  fetchFn: (args: T2) => Promise<T1>,
): [boolean, T1, (args: T2) => void] => {
  const [state, setState] = useState<T1>(initialState);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  const changeState = async (args: T2) => {
    setIsLoading(true);
    try {
      const newState = await fetchFn(args);
      setState(newState);
    } catch (error: unknown) {
      errorService.captureError(error, {
        rank: errorService.ErrorRank.P1,
        tags: {
          module: MODULE_NAME,
        },
      });
    } finally {
      setIsLoading(false);
    }
  };

  return [isLoading, state, changeState];
};

export default useAPI;
