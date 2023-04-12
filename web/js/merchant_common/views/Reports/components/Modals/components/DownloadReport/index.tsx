import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { getAvailableEmails } from 'merchant_common/views/Reports/utils/commonUtils';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  handleDownloadsPageTrack,
  startLogsPoll,
  stopLogsPoll,
} from 'merchant_common/views/Reports/redux/reducer';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { DownloadReportModal } from './DownloadReport';
import {
  trackDownloadModal,
  trackDownloadsSection,
} from 'merchant_common/views/Reports/configs/analytics.config';

const mapStateToProps = ({ reportsCore, accounts, session: { user, mode } }, { dashboardType }) => {
  const {
    overview: {
      reportConfigs: { allConfigs },
    },
  } = reportsCore[dashboardType];

  const availableEmails = getAvailableEmails(user);
  const { availableAccounts, headers, parsePayloadBeforeSubmit } = getReportsDashboardConfig(
    dashboardType,
    user,
    accounts,
    mode,
  );

  const generatedBy = user.current;
  return {
    headers,
    allReportConfigs: allConfigs.data,
    availableEmails,
    availableAccounts,
    generatedBy,
    parsePayloadBeforeSubmit,
  };
};

const mapDispatchToProps = (dispatch, { dashboardType }) => ({
  closeModal: () => {
    trackDownloadModal({
      actionName: 'close_modal_click',
      dashboardType,
    });
    dispatch(closeModal());
  },
  showNotification: (payload) => dispatch(showNotification(payload)),
  startLogsPoll: () => {
    dispatch(startLogsPoll({ dashboardType }));
    trackDownloadsSection({
      actionName: 'downloads_logs_poll_start',
      dashboardType,
    });
  },
  stopLogsPoll: () => {
    dispatch(stopLogsPoll({ dashboardType }));
    trackDownloadsSection({
      actionName: 'downloads_logs_poll_stops',
      dashboardType,
    });
  },
  handlePageChange: (payload) =>
    dispatch(handleDownloadsPageTrack({ pageNo: payload, dashboardType })),
});

export const DownloadReport = connect(mapStateToProps, mapDispatchToProps)(DownloadReportModal);
