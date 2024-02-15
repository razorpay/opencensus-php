import { useContext } from 'react';

import { ListFiltersContext, ListFiltersContextType } from './context';

const useListFilters = (): ListFiltersContextType => {
  const { formik, handleChange } = useContext(ListFiltersContext);
  return { formik, handleChange };
};

export default useListFilters;
