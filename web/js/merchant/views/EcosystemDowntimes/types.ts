import type { Dispatch } from 'react';

export type EcosystemDowntimesProviderType = {
  children: React.ReactNode;
};

export type FocusedInstrumentMetaDataType = {
  key: string;
  logo: string;
  name: string;
  method: string;
  group: string;
};

export type EcosystemDowntimesActionType = {
  type: EcosystemDowntimesActions;
  payload: {
    previousDowntimes?: PreviousDowntimeDictionaryType;
    activeDowntimes?: DowntimeDictionaryType;
    lastUpdatedAt?: string;
    focusedInstrument?: FocusedInstrumentMetaDataType | null;
  };
};

export type EcosystemDowntimesActions =
  | 'SET_ACTIVE_DOWNTIMES'
  | 'SET_FOCUSED_INSTRUMENT'
  | 'SET_PREVIOUS_DOWNTIMES';

export type InstrumentGroupTypes = 'issuer' | 'vpa_handle' | 'network' | 'bank' | 'psp';

export type InstrumentMapType = {
  [key in InstrumentGroupTypes]?: string;
};

export interface MethodsInstrumentListType extends InstrumentMapType {
  method: string;
}
export interface DowntimeMetaDataType {
  id: string;
  entity?: string;
  method: string;
  begin: number;
  end: number | null;
  status: string;
  scheduled?: boolean;
  severity: string;
  instrument: InstrumentMapType;
  created_at: number;
  updated_at: number;
  fromToString?: string;
  duration?: string;
}

export interface DowntimeDictionaryType {
  [key: string]: {
    [key: string]: DowntimeMetaDataType;
  };
}

export interface PreviousDowntimeDictionaryType {
  [key: string]: {
    [key: string]: {
      [key: string]: {
        previousDowntimes: DowntimeMetaDataType[];
      };
    };
  };
}

export interface EcosystemQueryFieldTypes {
  isOngoingDowntimesError?: boolean;
  isOngoingDowntimeLoading?: boolean;
  isOngoingDowntimeFetching?: boolean;
  isPreviousDowntimesError?: boolean;
  isPreviousDowntimesLoading?: boolean;
  isPreviousDowntimesFetching?: boolean;
}
export type StatusTypes = 'operational' | 'low' | 'medium' | 'high';
export interface EcosystemDowntimesStatusType {
  slug: string;
  text: string;
  weight: number;
  colorKey: 'positive' | 'negative' | 'notice' | 'information';
  icon: JSX.Element;
}

export interface EcosystemDowntimesInitialState extends EcosystemQueryFieldTypes {
  lastUpdatedAt?: string;
  activeDowntimes: DowntimeDictionaryType;
  previousDowntimes?: PreviousDowntimeDictionaryType;
  focusedInstrument?: FocusedInstrumentMetaDataType | null;
}

export interface EcosystemDowntimesContextType {
  state: EcosystemDowntimesInitialState;
  dispatch: Dispatch<EcosystemDowntimesActionType>;
  refreshData: () => void;
}

export interface EcosystemMethodSummaryType {
  method: string;
  maxToShow?: number;
}

export interface DowntimeResponseType {
  status_code?: number;
  success?: boolean;
  data: DowntimeMetaDataType[];
}

export interface InstrumentType {
  instrument: {
    [key: string]: string;
  };
  status?: DowntimeMetaDataType;
  onClick?: (FocusedInstrumentMetaDataType) => void;
}

export interface MethodInstrumentDataListType {
  [key: string]: {
    [key: string]: {
      key: string;
      logo: string;
      name: string;
    }[];
  };
}

export interface DowntimeSummaryFieldTypes {
  name: string;
  description: string;
  information: string;
  value: (args: {
    activeDowntimeForInstrument: DowntimeMetaDataType;
    pastDowntimesForInstrument: DowntimeMetaDataType[];
  }) => string | number;
}

type methodsTypes = 'card' | 'upi' | 'netbanking' | 'emandate';
type instrumentGroupTypes = 'issuer' | 'bank' | 'vpa_handle' | 'network';

export type StaticInstrumentMappingType = {
  [key in methodsTypes]?: {
    [key in instrumentGroupTypes]?: {
      [key: string]: boolean;
    };
  };
};
