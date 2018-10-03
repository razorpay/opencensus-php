import store from 'merchant/store';
import ShowWhen, {
  showWhenUtil as showWhenUtilx,
  ShowWhenRoute as ShowWhenRoutex,
} from '../../merchant_common/components/ShowWhen';

export const showWhenUtil = showWhenUtilx(store);
export const ShowWhenRoute = ShowWhenRoutex(store, '/dashboard');
export default ShowWhen(store);
