import { QueryKey } from '@tanstack/react-query';
import { DateRangeValues } from './common/Select/types';

export interface CommonWidgetProps {
  id: string;
  isLoading: boolean;
  error?: {
    code?: string;
    message?: string;
  };
  queryKey: QueryKey;
  analytics: {
    enabled: boolean;
  };
  analyticsProperties?: Record<string, any>;
}

export interface renderWidgetProps {
  widget: any;
  isLoading?: boolean;
  queryKey?: QueryKey;
  props?: {
    [x: string]: any;
  };
  analyticsProperties?: Record<string, any>;
}

interface DateTime {
  quick?: DateRangeValues;
  custom?: {
    from: number;
    to: number;
  };
}
export interface RetryWidgetProps {
  // using a different schema, to abstract the actual request payload, and make inner components simpler
  id: string;
  date_time?: DateTime;
}

export interface WidgetRequestPayload {
  component_ids?: string[];
  date_time?: DateTime;
  alias?: string;
}

export interface TrackParameters {
  objectName: string;
  actionName: 'clicked' | 'loaded' | 'error';
  screen: string;
  properties?: Record<string, any>;
}
