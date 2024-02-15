import { createContext } from 'react';

import { UseFormikReturnType } from 'common/typings';

export type ListFiltersContextType = {
  formik: null | UseFormikReturnType;
  handleChange: (args: { name?: string; value?: string }) => void;
};
const initialState = {
  formik: null,
  handleChange: () => {},
};
export const ListFiltersContext = createContext<ListFiltersContextType>(initialState);
