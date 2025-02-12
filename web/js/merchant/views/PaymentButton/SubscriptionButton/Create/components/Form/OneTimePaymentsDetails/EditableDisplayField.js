import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import { getCurrency } from 'common/ui/Amount';
import { classList } from 'common/utils/rzp-utils';
import {
  updatePaymentField,
  deletePaymentField,
  updatePaymentButtonData,
  updateStepReviewProgress,
} from 'merchant/reducers/subscriptionButtons/create';

import BaseForm from './BaseForm';
import track from '../../../track';

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
    this.setState({
      isEditModeOpened: !this.state.isEditModeOpened,
    });
  };

  onSubmitBaseForm = (fieldData) => {
    const newOneTimePaymentField = fieldData;

    if (!newOneTimePaymentField) {
      throw 'Invalid field data';
    }

    this.props.updatePaymentField(newOneTimePaymentField, this.props.indexInOrder); // If index is undefined, it'll be added as new field

    this.markReviewUnDone();

    track.lj.trackAmountFieldSaveSuccess();
  };

  handleDeleteField = () => {
    this.props.deletePaymentField(this.props.indexInOrder);

    this.markReviewUnDone();

    track.lj.trackAmountFormDeleteField();
  };

  markReviewUnDone = () => {
    this.props.updateStepReviewProgress({
      isOneTimePaymentsDetailsReviewed: false,
    });
  };

  render() {
    const { field, children, currency, indexInOrder, validateSameTitleExists } = this.props;
    const { isEditModeOpened } = this.state;

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
              label="Label"
              className="Input--vTop Input--dummy"
              value={field.item.name}
              readOnly
            />

            <Input
              label="Value"
              className="Input--vTop Input--dummy"
              value={field.item.amount}
              description={field.item.description}
              readOnly
            />
          </React.Fragment>
        )}

        {isEditModeOpened && (
          <BaseForm
            indexInOrder={indexInOrder} // Index in the ordered schema. If not defined, tells that it's a new field
            field={field}
            currency={currency}
            handleDeleteField={this.handleDeleteField}
            handleClose={this.handleToggleEditMode}
            onSubmit={this.onSubmitBaseForm}
            validateSameTitleExists={validateSameTitleExists}
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
