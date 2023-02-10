import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import Layout from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/ModalLayout';
import {
  BankAccountUpdateFlowPropInterface,
  BANK_ACCOUNT_UPDATE_STEPS,
  IformState,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { stepConfig } from './utils/stepConfig';
import { formInitState } from './components/Form/constants';

const BankAccountUpdateFlow = ({
  defaultView,
  closeModal,
  isMobile,
  showNotification,
  ...rest
}: BankAccountUpdateFlowPropInterface): JSX.Element => {
  const [view, changeView] = useState<{ active: string; back: string }>({
    active: defaultView,
    back: defaultView,
  });
  const [initialState, setInitialState] = useState<IformState>(formInitState);
  const [layoutInfo, setLayoutInfo] = useState<BANK_ACCOUNT_UPDATE_STEPS | ''>('');

  const handleChangeView = (view: BANK_ACCOUNT_UPDATE_STEPS): void => {
    changeView((prevState) => ({
      ...prevState,
      active: view,
      back: prevState.active,
    }));
  };

  const goBack = (): void => {
    changeView((prevState) => ({
      ...prevState,
      active: prevState.back,
      back: prevState.active,
    }));
  };

  const handleCloseModal = () => {
    if (view.active === BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM) {
      trackBankAccountUpdateEvent({
        objectName: 'Penny Test',
        actionName: 'Cancelled',
      });
    } else if (view.active === BANK_ACCOUNT_UPDATE_STEPS.UPLOAD_PROOF) {
      trackBankAccountUpdateEvent({
        objectName: 'Bank Account Number Verification',
        actionName: 'Cancelled',
      });
    }
    closeModal();
  };

  const Component = stepConfig[view.active].component;

  const { component: step, ...layoutConfig } = stepConfig[layoutInfo || view.active];
  return (
    <Layout closeModal={handleCloseModal} {...layoutConfig}>
      <Component
        view={view.active}
        setView={handleChangeView}
        state={initialState}
        setState={setInitialState}
        closeModal={closeModal}
        showNotification={showNotification}
        goBack={goBack}
        isMobile={isMobile}
        setLayoutInfo={setLayoutInfo}
        {...rest}
      />
    </Layout>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ closeModal, showNotification }, dispatch);
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(BankAccountUpdateFlow);

BankAccountUpdateFlow.defaultProps = {
  defaultView: BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM,
};
