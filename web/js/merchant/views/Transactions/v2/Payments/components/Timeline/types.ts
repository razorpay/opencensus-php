export type EntityType = 'Payment' | 'Settlement' | 'Refund' | 'Dispute' | null;

export interface TimelineJourneyPoint {
  id: number | string;
  entity: EntityType;
  status: string;
  title: string;
  timestamp: number | null;
  metadata: Record<string, any>;
}
