import store from 'merchantLA/store';
import {
  ShowWhen,
  showWhenUtil as showWhenUtilWrapper,
  RouteGuard,
} from 'merchant_common/components/RouteGuard';

export const showWhenUtil = showWhenUtilWrapper(store);

export { RouteGuard };
export default ShowWhen;
