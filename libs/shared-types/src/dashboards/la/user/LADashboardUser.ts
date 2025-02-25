import type { RazorpayUser } from "../../../common";
import type { LADashboardUserGetters } from "./LADashboardUserGetters";
import type { LADashboardUserMethods } from "./LADashboardUserMethods";

export type LADashboardUser = Partial<RazorpayUser> & LADashboardUserGetters & LADashboardUserMethods;


