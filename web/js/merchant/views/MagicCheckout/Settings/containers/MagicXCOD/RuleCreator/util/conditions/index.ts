import { isPojo } from "../misc";
import type { ConditionGroup } from "../../types";

export const isConditionGroup = (obj: any): obj is ConditionGroup => {
  return isPojo(obj) && Array.isArray(obj.conditions);
};
