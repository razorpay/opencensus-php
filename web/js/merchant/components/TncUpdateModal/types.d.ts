export type TncUpdateApiData = {
  show_tnc: boolean;
  version: string;
  created_at: number;
};

export type SaveTncUpdateAcceptanceApiData = {
  success: boolean;
  message: string;
};

export type SaveTncUpdateAcceptancePayload = {
  accepted_version: string;
  accepted_at: number;
};

export type TriggerAnalytics = (arg: SaveTncUpdateAnalyticsArguments) => void;

export type SaveTncUpdateAnalyticsArguments = {
  api_success: boolean;
  error?: unknown;
};
