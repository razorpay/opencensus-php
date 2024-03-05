export type AppReducerState = {
  luminateRowId: string | null;
  windowWidth: number;
  windowHeight: number;
  isMobileResolution: boolean;
  isWebView: boolean;
  activeEntityId?: string | null;
  activeSecEntityId?: string | null;
};
