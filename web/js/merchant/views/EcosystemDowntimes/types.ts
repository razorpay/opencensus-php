import type { Dispatch } from 'react';

export type EcosystemDowntimesProviderType = {
  children: React.ReactNode;
  showNotification: (payload) => void;
  isPreviousDowntimesFetchDisabled: boolean;
};

type InstrumentField = 'logo' | 'name' | 'key' | 'method' | 'group';

export interface InstrumentMetaData extends Record<InstrumentField, string> {
  srKey: string | null;
}

export type MethodType = 'card' | 'emandate' | 'netbanking' | 'upi';

export type InstrumentGroupTypes = 'issuer' | 'vpa_handle' | 'network' | 'bank' | 'psp';

export type EcosystemDowntimesActionType = {
  type: EcosystemDowntimesActions;
  payload: {
    previousDowntimes?: PreviousDowntimeDictionaryType;
    activeDowntimes?: DowntimeDictionaryType;
    lastUpdatedAt?: string;
    focusedInstrument?: InstrumentMetaData | null;
  };
};

export type EcosystemDowntimesActions =
  | 'SET_ACTIVE_DOWNTIMES'
  | 'SET_FOCUSED_INSTRUMENT'
  | 'SET_PREVIOUS_DOWNTIMES';

export type InstrumentMapType = {
  [key in InstrumentGroupTypes]?: string;
};

export interface MethodsInstrumentListType extends InstrumentMapType {
  method: string;
  srKey: string | null;
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

export type DowntimeDictionaryType = {
  [key in MethodType]?: {
    [key in InstrumentGroupTypes]?: {
      [key: string]: DowntimeMetaDataType;
    };
  };
};

export type PreviousDowntimeDictionaryType = {
  [key in MethodType]?: {
    [key in InstrumentGroupTypes]?: {
      [key: string]: {
        previousDowntimes: DowntimeMetaDataType[];
      };
    };
  };
};

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
  focusedInstrument?: InstrumentMetaData | null;
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

export type MethodInstrumentDataListType = {
  [key in MethodType]?: {
    [key in InstrumentGroupTypes]?: {
      key: string;
      logo: string;
      name: string;
      weight: number;
      srKey: string | null;
    }[];
  };
};

export interface DowntimeSummaryFieldTypes {
  name: string;
  description: string;
  information: string;
  value: (args: {
    activeDowntimeForInstrument: DowntimeMetaDataType;
    pastDowntimesForInstrument: DowntimeMetaDataType[];
  }) => string | number;
}

export type StaticInstrumentMappingType = {
  [key in MethodType]?: {
    [key in InstrumentGroupTypes]?: {
      [key: string]: boolean;
    };
  };
};

export type SuccessRateResponseType = {
  status_code: number;
  success: boolean;
  data: {
    code?: string;
    name?: 'string';
    sr?: number;
    successful?: number;
    total?: number;
    Code?: string; //in case endpoint throws error
    Description?: string; //in case endpoint throws error
  };
};
