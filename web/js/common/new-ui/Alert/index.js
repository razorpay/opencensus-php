import React from 'react';
import { classList } from 'common/utils/rzp-utils';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

export default class Alert extends React.PureComponent {
  render() {
    const { iconBefore, children, ...props } = this.props;

    return (
      <ErrorBoundary resetOnProps>
        <div {...props} className={classList(props.className, 'Alert')}>
          {iconBefore && <i className={`Alert-icon Alert-icon--before i ${iconBefore}`} />}
          <div className="Alert-content">{children}</div>
        </div>
      </ErrorBoundary>
    );
  }
}

Alert.Warning = (props) => (
  <Alert {...props} className={classList(props.className, 'Alert--warning')} />
);

Alert.Info = (props) => <Alert {...props} className={classList(props.className, 'Alert--info')} />;

Alert.Error = (props) => (
  <Alert {...props} className={classList(props.className, 'Alert--error')} />
);
