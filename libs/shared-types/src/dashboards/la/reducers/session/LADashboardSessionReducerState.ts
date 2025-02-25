import {DASHBOARD_MODE} from "../../../../common";
import type { LADashboardUser } from "../../user";

export type LADashboardSessionReducerState = {
  user: LADashboardUser;
  org: Record<string, unknown>;
  mode: DASHBOARD_MODE;
  modeFormatted: 'Test' | "Live";
  isTourVisible: boolean;
};
