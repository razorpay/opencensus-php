import store from 'merchantLA/store';
import ShowWhen, {
  showWhenUtil as showWhenUtilx,
} from '../../merchant_common/components/ShowWhen';

export const showWhenUtil = showWhenUtilx(store);
export default ShowWhen(store);
