import { DashboardModalSizeEnum } from "./DashboardModalSizeEnum";

export type DashboardOpenModalPayload = {
    size?: DashboardModalSizeEnum;
    component: JSX.Element;
    className?: string;
    overlayStyles?: Record<string, string>;
    isNew?: boolean;
  };
  