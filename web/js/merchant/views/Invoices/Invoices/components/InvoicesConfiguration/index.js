import { Component } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import PropTypes from 'prop-types';

import { GSTStep, InvoiceLabelStep } from './ConfigurationSteps';

export default class InvoicesOnboarding extends Component {
  static propTypes = {
    /**
     * Merchant.
     */
    merchant: PropTypes.object.isRequired,

    /**
     * Callback for once the details are saved.
     */
    onStart: PropTypes.func,

    /**
     * Callback for modal close.
     */
    onCloseClick: PropTypes.func,
  };

  static defaultProps = {
    onStart: () => {},
  };

  constructor() {
    super(...arguments);
    this.state = {
      //- step 0 => GST label selection form, step 1 => GST details form
      currentFormStep: 0,
    };
  }

  UNSAFE_componentWillMount() {
    const { invoiceLabelField } = this.props;
  }

  switchStep = (e, step) => {
    // prevent form submission
    e && e.preventDefault();

    this.setState({ currentFormStep: step });
  };

  render() {
    const { merchant, onCloseClick, invoiceLabelField } = this.props;
    const { currentFormStep } = this.state;

    return (
      <div className="InvoicesOnboardingModal">
        <ModalHeader title="Configure Invoices" onCloseClick={onCloseClick} />
        <div className="modal-body">
          <Alert type="error" message={this.state.errors} />
          <div className="row">
            <div className="col-md-12">
              <div className="row">
                <div className="col-md-12">
                  <p>
                    Confirm the following details first to start creating GST
                    invoices:
                  </p>
                </div>
              </div>
              <hr />
              {currentFormStep === 0 && (
                <InvoiceLabelStep
                  invoiceLabelField={invoiceLabelField}
                  merchantName={merchant.name}
                  businessName={merchant.business_name}
                  businessDba={merchant.business_dba}
                  onSwitchStep={this.switchStep}
                  onCloseClick={this.props.onCloseClick}
                  onStart={this.props.onStart}
                />
              )}
              {currentFormStep === 1 && (
                <GSTStep
                  merchantGstin={merchant.gstin}
                  onSwitchStep={this.switchStep}
                  onStart={this.props.onStart}
                />
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}
