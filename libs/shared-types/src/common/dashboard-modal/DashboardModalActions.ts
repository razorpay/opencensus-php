import { DashboardCloseModalType } from "./DashboardCloseModalType";
import { DashboardOpenModalPayload } from "./DashboardOpenModalPayload";

export type DashboardModalActions = {
    openModal: DashboardOpenModalPayload;
    closeModal: DashboardCloseModalType;
  };
  