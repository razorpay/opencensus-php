import { connect } from 'react-redux';

import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal } from 'merchant_common/reducers/modals';

import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { getAvailableEmails } from 'merchant_common/views/Reports/utils/commonUtils';
import {
  handleDownloadsPageTrack,
  startLogsPoll,
  stopLogsPoll,
} from 'merchant_common/views/Reports/redux/reducer';
import {
  trackDownloadModal,
  trackDownloadsSection,
} from 'merchant_common/views/Reports/configs/analytics.config';

import { DownloadReportModal } from './DownloadReport';

const mapStateToProps = ({ reportsCore, accounts, session }, { dashboardType, i18 }) => {
  const { user, mode } = session;
  const {
    overview: {
      reportConfigs: { allConfigs },
    },
  } = reportsCore[dashboardType];

  const availableEmails = getAvailableEmails(user);
  const { availableAccounts, headers, parsePayloadBeforeSubmit, availableFormats } =
    getReportsDashboardConfig(dashboardType, session, accounts, mode, i18);

  const generatedBy = user.current;

  return {
    headers,
    allReportConfigs: allConfigs.data,
    availableEmails,
    availableAccounts,
    availableFormats,
    generatedBy,
    parsePayloadBeforeSubmit,
  };
};

const mapDispatchToProps = (dispatch, { dashboardType }) => ({
  closeModal: () => {
    trackDownloadModal({
      actionName: 'Close Report Modal Clicked',
      dashboardType,
    });
    dispatch(closeModal());
  },
  showNotification: (payload) => dispatch(showNotification(payload)),
  startLogsPoll: () => {
    dispatch(startLogsPoll({ dashboardType }));
    trackDownloadsSection({
      actionName: 'Downloads Logs Poll Start',
      dashboardType,
    });
  },
  stopLogsPoll: () => {
    dispatch(stopLogsPoll({ dashboardType }));
    trackDownloadsSection({
      actionName: 'Downloads Logs Poll Stop',
      dashboardType,
    });
  },
  handlePageChange: (payload) =>
    dispatch(handleDownloadsPageTrack({ pageNo: payload, dashboardType })),
});

const DownloadReport = connect(mapStateToProps, mapDispatchToProps)(DownloadReportModal);

export default DownloadReport;
