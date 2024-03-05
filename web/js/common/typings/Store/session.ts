// import User from 'merchant/models/User';
import User from '../User';

export type Environments = 'test' | 'live';

export type UpdateSessionType = (arg0: Partial<SessionReducerState>) => void;

export type SessionReducerState = {
  user: User;
  org: Record<string, unknown>;
  mode: Environments;
  partnerMode: 'test';
  modeFormatted: 'Test';
  partnerModeFormatted: 'Test';
  highlightMode: boolean;
  isTourVisible: boolean;
  isUsingPartnerMode: boolean;
  user_segment_data: null;
  isTagsLoaded: boolean;
};
