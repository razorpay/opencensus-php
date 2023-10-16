import { useI18Service } from 'common/i18';
import ShowWhen from 'merchant/components/ShowWhen';
import EditTransactionLimit from 'merchant/views/Account/Profile/components/EditTransactionLimit';
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import { openModal as openModalFn } from 'merchant_common/reducers/modals';
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { StyledTransactionLimitContainer } from './styled';

const TransactionLimits = ({ openModal }): JSX.Element => {
  const openNeedsClarificationModal = (data) => {
    openModal({
      size: 'small',
      component: <NeedsClarificationModal {...data} />,
    });
  };
  const { isConfigTagEnabled } = useI18Service();

  return (
    <StyledTransactionLimitContainer>
      <EditTransactionLimit transactionType="domestic" replyHandler={openNeedsClarificationModal} />
      <ShowWhen additionalCondition={() => !isConfigTagEnabled('settings.international')}>
        <EditTransactionLimit
          transactionType="international"
          replyHandler={openNeedsClarificationModal}
        />
      </ShowWhen>
    </StyledTransactionLimitContainer>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ openModal: openModalFn }, dispatch);
};

export default connect(null, mapDispatchToProps)(TransactionLimits);
