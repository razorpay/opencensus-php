import { Location, NavigateFunction } from 'react-router-dom';

export interface RouterParams {
  location: Location;
  navigate: NavigateFunction;
}
export interface HandleDetailsClickParams {
  itemId: string;
  baseUrl: string;
  initiatePage: string;
  isButton?: boolean;
  isDisabled?: boolean;
  rowData?: { paymentMethod?: string; sourceChannel?: string };
  notes?: any;
  isStorefront?: boolean;
}

export type DetailsProps = HandleDetailsClickParams;
