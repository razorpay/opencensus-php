import React from 'react';
import { classList } from 'common/utils/rzp-utils';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

export default class Alert extends React.PureComponent {
  constructor(props) {
    super(props);
    this.state = {
      showAlert: true,
    };
  }

  render() {
    const { iconBefore, showCloseIcon, onCloseIconClick, children, ...props } = this.props;
    const { showAlert } = this.state;

    const handleCloseIcon = () => {
      this.setState({ showAlert: false });
      onCloseIconClick?.();
    };

    if (!showAlert) {
      return null;
    }

    return (
      <ErrorBoundary resetOnProps>
        <div {...props} className={classList(props.className, 'Alert')}>
          {iconBefore && <i className={`Alert-icon Alert-icon--before i ${iconBefore}`} />}
          <div className="Alert-content">{children}</div>
          {showCloseIcon && (
            <i className="Alert-icon Alert-icon--close i i-close" onClick={handleCloseIcon} />
          )}
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

Alert.Success = (props) => (
  <Alert {...props} className={classList(props.className, 'Alert--success')} />
);
