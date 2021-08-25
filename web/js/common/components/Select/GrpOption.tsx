import React, { ReactNode } from 'react';

export interface GrpOptionsPropsT {
  children: ReactNode;
  label: string;
}
export interface GrpOptionFCT extends React.FC<GrpOptionsPropsT> {
  isSelectGrpOption: boolean;
}

const GrpOption: React.FC<GrpOptionsPropsT> & { isSelectGrpOption: boolean } = ({ children }) => {
  return <> {children} </>;
};

GrpOption.isSelectGrpOption = true;

export default GrpOption;
