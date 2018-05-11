import { prevent } from 'common/util';
import { classList } from 'common/util';

export default class Alert extends React.PureComponent {
  render() {
    let { iconBefore, children, ...props } = this.props;

    return (
      <div {...props} class={classList(props.className, 'Alert')}>
        {iconBefore && (
          <i class={'Alert-icon Alert-icon--before i-' + iconBefore} />
        )}
        {children}
      </div>
    );
  }
}

Alert.Warning = props => (
  <Alert {...props} class={classList(props.className, 'Alert--warning')} />
);

Alert.Info = props => (
  <Alert {...props} class={classList(props.className, 'Alert--info')} />
);
