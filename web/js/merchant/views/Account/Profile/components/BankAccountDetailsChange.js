import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import ModalHeader from 'common/ui/ModalHeader';
import * as ModalActions from 'merchant_common/reducers/modals';
import { BankUpdateRenderStep } from './BankAccountDetailsChangeSteps';

const BankAccountDetailsChange = (props) => {
  const [step, setStep] = useState('init');
  const { closeModal, onSave } = props;

  return (
    <div className="bank-details-change">
      <ModalHeader title="Change Bank Account Details" onCloseClick={closeModal} />
      <div className="modal-body">
        <div className={`bank-details-change-content ${step}`}>
          <BankUpdateRenderStep
            setStep={setStep}
            step={step}
            onSave={onSave}
            closeModal={closeModal}
          />
        </div>
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ ...ModalActions }, dispatch);
};

export default connect(null, mapDispatchToProps)(BankAccountDetailsChange);
