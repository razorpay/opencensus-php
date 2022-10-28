import { classList } from 'common/utils/rzp-utils';

export default (props) => (
  <span {...props} className={classList(props.className, 'PlaceholderLoader')} />
);
