export const DASHBOARD_PRIORITY_RANKS = {
  P0: 'P0',
  P1: 'P1',
  P2: 'P2',
  P3: 'P3',
} as const;

export type DASHBOARD_PRIORITY_RANKS =
  (typeof DASHBOARD_PRIORITY_RANKS)[keyof typeof DASHBOARD_PRIORITY_RANKS];
