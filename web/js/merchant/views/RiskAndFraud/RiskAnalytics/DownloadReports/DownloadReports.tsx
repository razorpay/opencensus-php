import React from 'react';
import { connect } from 'react-redux';
import { Dispatch, AnyAction } from 'redux';

import { OpenModalPayload, Notification } from 'common/typings';
import ActionContainer from 'merchant/views/RiskAndFraud/RiskAnalytics/components/ActionContainer';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getAvailableEmails } from 'merchant_common/views/Reports/utils/commonUtils';

import ReportModal from './ReportModal';
import { DOWNLOAD_REPORTS } from './constants';
import { DownloadReportsProps } from './types';
import { trackEvent } from '../../common/trackEvents';
import { RISK_DECLINED } from '../constants';

const DownloadReports: React.FC<DownloadReportsProps> = (props) => {
  const { entity, availableEmails, generatedBy, openModal, closeModal, showNotification } = props;
  const { heading, description } = DOWNLOAD_REPORTS[entity];

  const handleDownload = () => {
    trackEvent({
      objectName: 'Download list - Open',
      properties: { section: entity },
    });
    openModal({
      component: (
        <ReportModal
          entity={entity}
          availableEmails={availableEmails}
          generatedBy={generatedBy}
          onCloseCallback={closeModal}
          showNotification={showNotification}
        />
      ),
    });
  };

  return (
    <ActionContainer
      heading={heading}
      description={description}
      buttonText="Download list"
      showDownloadIcon={entity === RISK_DECLINED}
      onButtonClick={handleDownload}
    />
  );
};
const mapStateToProps = ({ session }) => {
  const { user } = session;
  return { availableEmails: getAvailableEmails(user), generatedBy: user.current };
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) => ({
  openModal: (modal: OpenModalPayload) => dispatch(openModal(modal)),
  closeModal: () => dispatch(closeModal()),
  showNotification: (payload: Notification) => dispatch(showNotification(payload)),
});

export default connect(mapStateToProps, mapDispatchToProps)(DownloadReports);
