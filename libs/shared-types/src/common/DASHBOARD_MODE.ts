export const DASHBOARD_MODE = {
  LIVE: 'live',
  TEST: 'test',
} as const;

export type DASHBOARD_MODE = (typeof DASHBOARD_MODE)[keyof typeof DASHBOARD_MODE];
