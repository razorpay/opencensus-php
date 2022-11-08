import React from 'react';

// utils
import { connect } from 'react-redux';

// components
import ModalHeader from 'common/ui/ModalHeader';
import HSCodeSearch from 'merchant/views/Account/Profile/components/FIRC/HSCode/HSCodeSearch';
import HSCodeConfirm from 'merchant/views/Account/Profile/components/FIRC/HSCode/HSCodeConfirm';

// actions
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { fetchMerchantHSCode } from 'merchant/reducers/profile';

// hooks
import { useAllHSCode } from 'merchant/views/Account/Profile/components/FIRC/HSCode/helpers';

// styles
import 'merchant/views/Account/Profile/components/FIRC/css/firc.styl';

// prop types
interface HSCodeModalProps {
  hsCode?: string;
  close?: () => void;
  showNotification?: (arg: { type: 'error' | 'success'; message: string }) => void;
  getHSCodeDetails?: () => void;
}

const HSCodeModal = ({
  close,
  hsCode,
  showNotification,
  getHSCodeDetails,
}: HSCodeModalProps): JSX.Element => {
  const {
    isLoading,
    data,
    selectedCode,
    hasConfirm,
    onSave,
    onSelect,
    onBack,
    onConfirm,
  } = useAllHSCode({ hsCode, close, showNotification, getHSCodeDetails });

  const handleCloseModal = () => {
    if (typeof close === 'function') {
      close();
    }
  };

  return (
    <div className="firc-container">
      <ModalHeader
        title={hasConfirm ? 'Confirmation' : 'Select HS Code'}
        onCloseClick={handleCloseModal}
      />
      <div className="modal-content">
        {hasConfirm ? (
          <HSCodeConfirm selected={selectedCode} onConfirm={onSave} onBack={onBack} />
        ) : (
          <HSCodeSearch
            codeList={data}
            isLoading={isLoading}
            selected={selectedCode?.value}
            onNext={onConfirm}
            onSelect={onSelect}
          />
        )}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({ hsCode: state.profile.hsCodeDetails?.data });

const mapDispatchToProps: HSCodeModalProps = {
  close: fnCloseModal,
  showNotification: fnShowNotification,
  getHSCodeDetails: fetchMerchantHSCode,
};

export default connect(mapStateToProps, mapDispatchToProps)(HSCodeModal);
