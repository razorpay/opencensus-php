import { RouteItem, GenericRecord, User } from 'merchant/views/MagicCheckout/types';

export const isRouteAuthorised = (
  item: RouteItem,
  user: User,
  abExperiments: GenericRecord,
  isRCOD: boolean,
): boolean => {
  if (item.condition && !item.condition(user, abExperiments)) return false;

  /**
   * rcod -> config from api response(merchant level)
   * onRCODOnly , onRCOD -> route item config(route level)
   * when rcod is true , only onRCOD and onRCODOnly items should be rendered
   * onRCODOnly item should only be displayed only when rcod config is true
   * onRCOD can be displayed irrespective of rcod config
   */

  if (isRCOD && !(item.onRCOD || item.onRCODOnly)) return false;
  if (!isRCOD && item.onRCODOnly) return false;
  return true;
};
