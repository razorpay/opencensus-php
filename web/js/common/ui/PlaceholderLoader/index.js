// Todo: delete this file, it's available in @dashboard/shared-ui
import { classList } from 'common/utils/rzp-utils';

export default (props) => (
  <span {...props} className={classList(props.className, 'PlaceholderLoader')} />
);
