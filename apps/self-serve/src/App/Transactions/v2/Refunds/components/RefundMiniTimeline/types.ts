export type EntityType = 'Refund';

export type EntityStatus = 'processing' | 'processed' | 'failed';

export interface RefundTimelineJourneyPoint {
  id: number;
  entity: EntityType;
  title: string;
  timestamp: number | null;
}

export interface RefundTimelineType {
  refundStatus: EntityStatus;
  timelineJourney: RefundTimelineJourneyPoint[];
}
