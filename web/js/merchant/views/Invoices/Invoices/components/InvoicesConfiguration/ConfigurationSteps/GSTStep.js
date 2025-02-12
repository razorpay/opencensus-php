import { Component, Fragment } from 'react';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import { closeModal } from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

class GSTStepOnboarding extends Component {
  successNotification = () => {
    this.props.closeModal();
    this.props.showNotification({
      type: 'success',
      message: 'Invoices configured successfully. Start creating your first Invoice.',
    });
    this.props.onStart();
  };

  render() {
    const { merchantGstin, onSwitchStep } = this.props;

    return (
      <form autoComplete="off">
        <div className="row">
          <div className="col-md-12">
            <small className="help-block">STEP 2/2</small>
            <div className="section-title">GST Details:</div>
            {merchantGstin ? (
              <Fragment>
                <div className="m-t">
                  Your Business GSTIN: <span className="section-title">{merchantGstin}</span>
                </div>
                <p className="help-block">
                  To update your GST details, please <Link to="#ticket">write to support</Link>
                </p>
              </Fragment>
            ) : (
              <Fragment>
                <div className="m-t">No GSTIN added</div>
                <p className="help-block">
                  You can still create Non-GST invoices. For GST Invoices, you can add your GSTIN
                  later from Invoice Settings.
                </p>
              </Fragment>
            )}
          </div>
        </div>
        <div className="row">
          <div className="col-md-12">
            <div className="Modal__actions">
              <button className="btn btn-default m-r" onClick={(e) => onSwitchStep(e, 0)}>
                Previous Step
              </button>
              <AsyncButton
                type="submit"
                className="btn btn-primary"
                text={`Start Creating ${merchantGstin ? '' : 'Non-'}GST Invoices`}
                pendingText="Saving..."
                onClick={this.successNotification}
              />
            </div>
          </div>
        </div>{' '}
      </form>
    );
  }
}

export default connect(null, {
  closeModal,
  ...NotificationsActions,
})(GSTStepOnboarding);
