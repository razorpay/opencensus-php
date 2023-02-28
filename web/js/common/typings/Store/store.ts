import { WorkflowsReducerState } from './workflows';
import { SessionReducerState } from './session';
import { SettlementReducerState } from './settlement';
import { InstrumentRequestsReducerState } from './instrument-requests';
import { AppReducerState } from './app';
import { ModalReducerState } from './modal';

type Store = {
  workflows: WorkflowsReducerState;
  session: SessionReducerState;
  settlement: SettlementReducerState;
  app: AppReducerState;
  instrumentRequests: InstrumentRequestsReducerState;
  modal: ModalReducerState;
};

export default Store;
