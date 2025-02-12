import React from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import SwitchField from 'common/ui/Forms/SwitchField';
import {
  updateStepReviewProgress,
  filterSubscriptionPaymentItems,
  removeAllOneTimePaymentFields,
} from 'merchant/reducers/subscriptionButtons/create';

import EditableDisplayField from './EditableDisplayField';

// import track from '../../../track';

class OneTimePaymentsDetails extends React.Component {
  maxFieldsLimit = 5;

  state = {
    hasOneTimePayments: this.oneTimePaymentFields && !!this.oneTimePaymentFields.length,
  };

  get oneTimePaymentFields() {
    const { paymentFields } = this.props;

    const items = filterSubscriptionPaymentItems(paymentFields, true);

    const cleanItems = items.filter(function () {
      return true;
    });

    return { items, length: cleanItems.length };
  }

  goNext = () => {
    this.props.goNext();

    this.markReviewDone();

    // track.lj.trackAmountScreenNextSuccess();
  };

  markReviewDone = () => {
    this.props.updateStepReviewProgress({
      isOneTimePaymentsDetailsReviewed: true,
    });
  };

  handleToggle = () => {
    const hasOneTimePayments = !this.state.hasOneTimePayments;

    if (!hasOneTimePayments) {
      this.props.removeAllOneTimePaymentFields(); // Make it in debounce, so that it's not accidentally removed. Same as Donation presets in Subscription Button
    }

    this.setState({
      hasOneTimePayments,
    });
  };

  validateSameTitleExists = (title, indexInOrder) => {
    const allFieldsTitles = this.oneTimePaymentFields.items.map((field) => {
      return field.item.name.toLowerCase();
    });

    const sameTitleIndex = allFieldsTitles.indexOf(title.toLowerCase());

    if (sameTitleIndex > -1 && sameTitleIndex !== indexInOrder) {
      return true;
    }
  };

  setRefFormContainer = (el) => (this.formContainerEl = el);

  render() {
    const { paymentFields, subscriptionButtonEntity } = this.props;
    const { hasOneTimePayments } = this.state;

    const currency = subscriptionButtonEntity.currency;

    const fields = this.oneTimePaymentFields;
    const items = [];

    fields.items.forEach((field, index) => {
      items.push(
        <EditableDisplayField
          key={field.item?.name}
          indexInOrder={index}
          field={field}
          currency={currency}
          validateSameTitleExists={this.validateSameTitleExists}
        />,
      );
    });

    return (
      <div className="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        {
          <div className="PaymentButtonForm-AmountDetails Form-content">
            <label style={{ marginBottom: 40 }}>
              <SwitchField
                type="prime round"
                checked={hasOneTimePayments}
                onChange={this.handleToggle}
              />
              <span className="m-l" style={{ cursor: 'pointer' }}>
                Add one-time payment options
              </span>
            </label>

            {hasOneTimePayments && (
              <React.Fragment>
                {items}
                {this.maxFieldsLimit > fields.length && (
                  <EditableDisplayField
                    field={null}
                    currency={currency}
                    validateSameTitleExists={this.validateSameTitleExists}
                  >
                    <Button
                      className="Button--primary--invert addFieldBtn"
                      // onClick={track.lj.trackCustomerScreenInputField}
                    >
                      <b>+ Add One-Time Item</b>
                    </Button>
                  </EditableDisplayField>
                )}
              </React.Fragment>
            )}
          </div>
        }

        <div className="Form-controls">
          <Button.Transparent
            type="button"
            onClick={() => {
              this.props.goBack();

              // track.lj.trackAmountScreenBackSuccess();
            }}
          >
            Back
          </Button.Transparent>

          <Button.Primary type="button" disabled={!paymentFields.length} onClick={this.goNext}>
            Next <i className="i i-chevron-right" />
          </Button.Primary>
        </div>
      </div>
    );
  }
}

export default connect((state) => ({}), {
  updateStepReviewProgress,
  removeAllOneTimePaymentFields,
})(OneTimePaymentsDetails);
