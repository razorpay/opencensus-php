import ModalHeader from 'common/ui/ModalHeader';
import React from 'react';
import { bindActionCreators, compose } from 'redux';
import { closeModal as closeModalReducer } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { reduxForm, Field } from 'redux-form';
import Button from 'common/new-ui/Button';
import InputField from 'common/ui/Forms/InputField';
import { required } from 'common/utils/validators';
import AsyncButton from 'react-async-button';

const formTitle = (type) => {
  switch (type) {
    case 'fee':
      return 'Add Fee Credits';
    case 'refund':
      return 'Add Refund Credits';
    case 'reserve':
      return 'Add Reserve Funds';
    default:
      return '';
  }
};

function AddCreditsForm({
  closeModal,
  pristine,
  handleSubmit,
  type,
  onBack,
  destroy,
  handleAddCredits,
}) {
  return (
    <form>
      <ModalHeader
        title={formTitle(type)}
        onCloseClick={() => {
          closeModal();
          destroy();
        }}
      />
      <div className="modal-body">
        <div className="form-group">
          <Field
            component={InputField}
            type="text"
            placeholder="Enter Description"
            name="description"
            className="form-control"
            validate={required()}
            autoFocus={true}
          />
        </div>
        <div className="form-group">
          <Field
            component={InputField}
            type="text"
            placeholder="Enter Amount(INR)"
            name="amountInINR"
            className="form-control"
            validate={required()}
          />
        </div>
        <div>
          <p>Note: Standard TDR charges applies on adding credits</p>
        </div>
        <div className="Modal__actions">
          <AsyncButton
            type="submit"
            className="btn btn-primary btn-block"
            text="Add Credits"
            pendingText="Adding..."
            disabled={pristine}
            onClick={handleSubmit((fieldProps) => handleAddCredits(fieldProps, destroy))}
          />
          <Button.Primary type="button" className="btn btn-primary btn-block" onClick={onBack}>
            Back
          </Button.Primary>
        </div>
      </div>
    </form>
  );
}

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ closeModal: closeModalReducer }, dispatch);

export default compose(
  connect(null, mapDispatchToProps),
  reduxForm({
    form: 'AddCreditsForm',
    destroyOnUnmount: false, // <------ preserve form data
    forceUnregisterOnUnmount: true,
  }),
)(AddCreditsForm);
