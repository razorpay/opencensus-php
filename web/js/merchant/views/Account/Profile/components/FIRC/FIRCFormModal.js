import React, { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import FIRCFormContext from './FIRCFormContext';
import SelectPurposeCode from './SelectPurposeCode';
import EnterIECCode from './EnterIECCode';
import Confirm from './Confirm';
import TicketSuccess from 'merchant/views/Account/Profile/components/FIRC/TicketSuccess';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { getPurposeCodes, updatePurposeCode } from 'merchant/reducers/profile';
import { MODAL_HEADING, SPECIAL_PURPOSE_CODES, computeSearch } from './utility';
import 'merchant/views/Account/Profile/components/FIRC/css/firc.styl';

import { createSupportTicketForPurposeCode } from 'merchant/views/Account/Profile/components/FIRC/service';

const FIRCFormModal = (props) => {
  const { closeModal, showNotification, onSubmit, editMode, user, code } = props;

  const [step, setStep] = useState(1);
  const [list, setList] = useState({ isLoading: true, data: [] });
  const [search, setSearch] = useState({ text: '', results: [] });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [state, setState] = useState({
    purpose_code: code ?? '',
    purpose_code_desc: '',
    is_purpose_code_special: false,
    iec_code: '',
  });

  useEffect(() => {
    getPurposeCodes()
      .then(({ data }) => setList({ isLoading: false, data }))
      .catch(({ errors }) => {
        setList({ isLoading: false, data: [] });
        showNotification({
          type: 'error',
          message: errors[0] || 'Fetching purpose code list failed.',
        });
      });
  }, [showNotification]);

  const handleNext = useCallback(() => {
    const nextStep = state.is_purpose_code_special ? step + 1 : 3;
    setStep(nextStep);
  }, [state.is_purpose_code_special, step]);

  const handlePrev = useCallback(() => {
    const prevStep = state.is_purpose_code_special ? step - 1 : 1;
    setStep(prevStep);
  }, [state.is_purpose_code_special, step]);

  const closePopup = useCallback(() => closeModal(), [closeModal]);

  const handleSearch = useCallback(
    (e) => {
      const results = computeSearch(list.data, e.target.value);
      setSearch((prevState) => ({ ...prevState, text: e.target.value, results }));
    },
    [list.data],
  );

  const clearSearch = () => setSearch({ text: '', results: [] });

  const handlePurposeCode = (code) => {
    setState((prevState) => ({
      ...prevState,
      purpose_code: code.purposeCode,
      purpose_code_desc: code.description,
      is_purpose_code_special: SPECIAL_PURPOSE_CODES.includes(code.purposeCode),
      iec_code: '',
    }));
  };

  const handleInput = (e) => {
    const { name, value } = e.target;
    setState((prevState) => ({ ...prevState, [name]: value }));
  };

  const handleConfirm = useCallback(() => {
    const formData = {
      purpose_code: state.purpose_code,
      purpose_code_desc: state.purpose_code_desc,
      iec_code: state.iec_code ? state.iec_code : null,
    };

    setIsSubmitting(true);

    // In edit mode, raise a support ticket to update the purpose code
    if (editMode) {
      return createSupportTicketForPurposeCode({
        user,
        oldPurposeCode: code,
        newPurposeCode: formData.purpose_code,
      })
        .then(() => {
          setStep(4);
        })
        .catch(() => {
          showNotification({
            type: 'error',
            message: 'Failed to raise support ticket.',
          });
        })
        .finally(() => {
          setIsSubmitting(false);
        });
    }

    return onSubmit(formData)
      .then(({ data }) => {
        if (data.success === true) {
          showNotification({
            type: 'success',
            message: 'Updated successfully.',
          });
        }
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Sorry! Update failed.',
        });
      })
      .finally(() => {
        setIsSubmitting(false);
        closePopup();
      });
  }, [
    closePopup,
    onSubmit,
    code,
    editMode,
    user,
    showNotification,
    state.iec_code,
    state.purpose_code,
    state.purpose_code_desc,
  ]);

  const renderChild = () => {
    switch (step) {
      case 1:
        return (
          <SelectPurposeCode
            purposeCodeList={list}
            search={search}
            onSearch={handleSearch}
            clearSearch={clearSearch}
            onSelect={handlePurposeCode}
          />
        );
      case 2:
        return <EnterIECCode onChange={handleInput} />;
      case 3:
        return <Confirm onConfirm={handleConfirm} />;
      case 4:
        return <TicketSuccess onClose={closePopup} />;
      default:
        return null;
    }
  };

  return (
    <FIRCFormContext.Provider
      value={{
        step,
        formState: state,
        handlePrev,
        handleNext,
        closePopup,
        existingCode: code,
        isSubmitting,
      }}
    >
      <div className="firc-container">
        <ModalHeader title={MODAL_HEADING[step]} onCloseClick={closePopup} />
        <div className="modal-content">{renderChild()}</div>
      </div>
    </FIRCFormContext.Provider>
  );
};

const mapStateToProps = (state) => ({ user: state.session.user });

const mapDispatchToProps = {
  closeModal: fnCloseModal,
  showNotification: fnShowNotification,
  onSubmit: updatePurposeCode,
};

export default connect(mapStateToProps, mapDispatchToProps)(FIRCFormModal);
