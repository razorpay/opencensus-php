import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ModalHeader from 'common/ui/ModalHeader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { LinkAccountFormPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import { NOTIFICATION_TEXTS } from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';

const LinkAccountForm = (props: LinkAccountFormPropsType): JSX.Element => {
  const {
    closeModal,
    step,
    setStep,
    stepTexts,
    points,
    pointsHeader,
    onSavingAccountCreds,
    showNotification,
    setIntegrationMethod,
  } = props;

  const { Component, header, desc } = stepTexts[step];

  const handleModalClose = () => {
    setIntegrationMethod('');
    showNotification({
      type: 'neutral',
      message: NOTIFICATION_TEXTS.neutral,
      closeTimeout: 10000,
      className: 'magic-notification',
    });
    closeModal();
  };

  return (
    <SuspenseWithLoader type="center">
      <ModalHeader onCloseClick={handleModalClose} />
      <div className="link-account-content">
        <div className="link-account-header font-bold color-black">{header}</div>
        <div className="link-account-desc">{desc}</div>
        <Component
          step={step}
          setStep={setStep}
          points={points}
          pointsHeader={pointsHeader}
          onSavingAccountCreds={onSavingAccountCreds}
        />
      </div>
    </SuspenseWithLoader>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(LinkAccountForm);
