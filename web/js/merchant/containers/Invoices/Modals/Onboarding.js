import { Component } from 'react';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import PropTypes from 'prop-types';

import InvoiceLabelStep from './OnboardingSteps/InvoiceLabelStep';
import GSTStep from './OnboardingSteps/GSTStep';

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

  componentWillMount() {
    const { invoiceLabelField } = this.props;

    // if invoice label is selected, show gst details step
    if (invoiceLabelField) {
      this.setState({
        currentFormStep: 1,
      });
    }
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
      <div class="InvoicesOnboardingModal">
        <ModalHeader title="Configure Invoices" onCloseClick={onCloseClick} />
        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />
          <div class="row">
            <div class="col-md-12">
              <div class="row">
                <div class="col-md-12">
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
                  onCloseClick={this.props.onCloseClick}
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
