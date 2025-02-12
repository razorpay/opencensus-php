import React from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import { fetchPlans } from 'merchant/reducers/plans';
import {
  updateStepReviewProgress,
  filterSubscriptionPaymentItems,
} from 'merchant/reducers/subscriptionButtons/create';

import EditableDisplayField from './EditableDisplayField';

// import track from '../../../track';

class PlansDetails extends React.Component {
  maxFieldsLimit = 5;

  componentDidMount() {
    this.props.fetchPlans({ count: 100 }); // TODO: Api support max 100. If some merchant has plans >100, we should implement auto complete
  }

  get plans() {
    const { subscriptionButtonEntity, plans } = this.props;

    return plans.items.filter(
      (plan) =>
        !subscriptionButtonEntity.currency ||
        plan.item.currency === subscriptionButtonEntity.currency,
    );
  }

  get planFields() {
    const { paymentFields } = this.props;

    const items = filterSubscriptionPaymentItems(paymentFields);

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
      isPlansDetailsReviewed: true,
    });
  };

  setRefFormContainer = (el) => (this.formContainerEl = el);

  render() {
    // TODO: Filter plan as per the currency of 1st plan selected
    const { subscriptionButtonEntity } = this.props;
    const currency = subscriptionButtonEntity.currency;

    const fields = this.planFields;
    const items = [];

    fields.items.forEach((field, index) => {
      items.push(
        <EditableDisplayField
          key={field.id}
          indexInOrder={index}
          field={field}
          plansOptions={this.plans}
          currency={currency}
        />,
      );
    });

    return (
      <div className="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        <div className="PaymentButtonForm-AmountDetails Form-content">
          {items}
          {this.maxFieldsLimit > fields.length && (
            <EditableDisplayField field={null} plansOptions={this.plans} currency={currency}>
              <Button
                className="Button--primary--invert addFieldBtn"
                // onClick={track.lj.trackCustomerScreenInputField}
              >
                <b>+ Add Plan Item</b>
              </Button>
            </EditableDisplayField>
          )}
        </div>

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

          <Button.Primary type="button" disabled={!fields.length} onClick={this.goNext}>
            Next <i className="i i-chevron-right" />
          </Button.Primary>
        </div>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    plans: state.plans,
  }),
  {
    updateStepReviewProgress,
    fetchPlans,
  },
)(PlansDetails);
