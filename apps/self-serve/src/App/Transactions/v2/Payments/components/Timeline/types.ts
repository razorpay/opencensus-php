export type EntityType = 'Payment' | 'Settlement' | 'Refund' | 'Dispute' | null;

export interface TimelineJourneyPoint {
  id: number | string;
  entity: EntityType;
  status: string;
  title: string;
  timestamp: number | null;
  metadata: Record<string, any>;
}

export interface SkipTransactions {
  skip_time: number;
  skip_reason: string;
}

export interface SkipTimelineTransactions {
  started_at: number;
  eligible_at: number;
  skips: SkipTransactions[];
}
