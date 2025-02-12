import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import { getCurrency } from 'common/ui/Amount';
import { classList, getFormattedAmount } from 'common/utils/rzp-utils';
import {
  updatePaymentField,
  deletePaymentField,
  updatePaymentButtonData,
  updateStepReviewProgress,
} from 'merchant/reducers/subscriptionButtons/create';
import { getPeriodLabel } from 'merchant/views/PaymentButton/SubscriptionButton/Create/constants/billingCycle';
import track from 'merchant/views/PaymentButton/SubscriptionButton/Create/track';

import BaseForm from './BaseForm';

class EditableDisplayField extends React.Component {
  state = {
    isEditModeOpened: false,
  };

  get currencySymbol() {
    const currency = this.props.currency;
    const _currencySymbol = getCurrency(currency).symbol;

    return _currencySymbol;
  }

  handleToggleEditMode = () => {
    this.setState((prevState) => ({
      isEditModeOpened: !prevState.isEditModeOpened,
    }));
  };

  onSubmitBaseForm = (fieldData) => {
    const newPlanField = fieldData;

    // Update currency so it could be used everywhere, in preview, in descriptions, for filtering plans as per currency etc.
    const currency = newPlanField?.item?.currency;
    this.props.updatePaymentButtonData({
      currency,
    });

    if (!newPlanField) {
      // eslint-disable-next-line no-throw-literal
      throw 'Invalid field data';
    }

    this.props.updatePaymentField(newPlanField, this.props.indexInOrder); // If index is undefined, it'll be added as new field

    this.markReviewUnDone();

    track.lj.trackPlanFieldSaveSuccess();
  };

  handleDeleteField = () => {
    this.props.deletePaymentField(this.props.indexInOrder);

    this.markReviewUnDone();

    track.lj.trackPlanFormDeleteField();
  };

  findSelectedOptionInPlanOptions() {
    return null;
  }

  markReviewUnDone = () => {
    this.props.updateStepReviewProgress({
      isPlansDetailsReviewed: false,
    });
  };

  render() {
    const { field, children, plansOptions, currency, indexInOrder } = this.props;
    const { isEditModeOpened } = this.state;

    let descriptionOfPlanFrequency;

    if (!children) {
      const planDetails = field.product_config.plan_details;
      descriptionOfPlanFrequency = (
        <span>
          <b>
            {this.currencySymbol} {getFormattedAmount(Number(field.item.amount), currency)}
          </b>{' '}
          to be charged {getPeriodLabel(planDetails.period, planDetails.interval)}
        </span>
      );
    }

    return (
      <div
        onClick={!isEditModeOpened ? this.handleToggleEditMode : () => {}}
        className={classList(
          'EditableUDF EditableDisplayField',
          children && 'EditableDisplayField--disabled',
          isEditModeOpened && 'EditableDisplayField--editMode',
        )}
      >
        {children || (
          <React.Fragment>
            <span className="btn btn-link edit-btn">
              Click to Edit This Field
              <i className="i i-edit" />
            </span>

            <Input
              label="Plan"
              className="Input--vTop Input--dummy"
              value={field.item.name}
              description={descriptionOfPlanFrequency}
              readOnly
            />

            <Input
              label="No. of Billing Cycles"
              className="Input--vTop Input--dummy"
              value={field.product_config.subscription_details.total_count}
              readOnly
            />
          </React.Fragment>
        )}

        {isEditModeOpened && plansOptions && (
          <BaseForm
            indexInOrder={indexInOrder} // Index in the ordered schema. If not defined, tells that it's a new field
            field={field}
            currency={currency}
            plansOptions={plansOptions}
            handleDeleteField={this.handleDeleteField}
            handleClose={this.handleToggleEditMode}
            onSubmit={this.onSubmitBaseForm}
          />
        )}
      </div>
    );
  }
}

export default connect(null, {
  updatePaymentField,
  deletePaymentField,
  updatePaymentButtonData,
  updateStepReviewProgress,
})(EditableDisplayField);
