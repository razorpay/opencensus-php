import { VisibleStatus } from './types';

export const VISIBLE_STATES: Record<string, VisibleStatus> = {
  NONE: 'none',
  ALL: 'all',
  SOME: 'some',
};

export const GLOBAL_KEY = 'International';
export const DEPTH_MAP = {
  // commented below line to remove International option in popup, might need it in future
  [GLOBAL_KEY]: 0,
  COUNTRY: 1,
  STATE: 2,
};

export const MARGIN_LEFT = [0, '25px', '40px'];
