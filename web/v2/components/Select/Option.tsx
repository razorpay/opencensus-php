import React, { ReactNode } from 'react';

export interface OptionsPropsT {
  children: ReactNode;
  value: string;
  label: string;
  disabled?: boolean;
}

const Option: React.FC<OptionsPropsT> & { isSelectOption: boolean } = ({ children }) => {
  return <> {children} </>;
};

Option.isSelectOption = true;

export default Option;
