export type CollectionReducer<T> = {
  loading: boolean;
  items: T[];
  error: null | string;
};

export type SettlementInfo = {
  amount: number;
  amountInINR: string;
  created_at: number;
  entity: 'settlement';
  fees: number;
  id: string;
  optimizer_provider: string;
  resourceIdField: 'id';
  resourceUrl: 'settlements';
  settled_by: string;
  status: 'PROCESSED';
  tax: number;
  utr: string;
};

export type SettlementsCollectionReducerState = CollectionReducer<SettlementInfo>;
