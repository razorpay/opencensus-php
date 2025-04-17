import { ReactNode } from 'react';

export type AccordionDataType = {
  title: string;
  content: ReactNode;
  isIncomplete?: boolean;
};

export enum FEATURE_FLAGS {
  SHOW_PG_V3 = 'show_pg_v3',
  PG_V3_ONBOARDING_COMPLETE = 'pg_v3_onboarding_complete',
}
export enum WORKFLOW_TYPES {
  BUSINESS_WEBSITE = 'ADD_BUSINESS_WEBSITE',
}
