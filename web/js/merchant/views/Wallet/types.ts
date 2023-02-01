import type { ModeT } from 'common/services/mode';

enum Status {
  active = 'active',
  pending_activation = 'pending_activation',
  inactive = 'inactive',
}

export interface Account {
  account_id: string;
  contact: string;
  account_holder_name: string;
  merchant: string;
  program: string;
  balance: number;
  status: Status;
  created_at: number;
}

export interface ListApiResponse<T> {
  entity: string;
  count: number;
  items: T[];
}

export interface ListApiParams {
  skip: number;
  count: number;
  mode: ModeT;
}
