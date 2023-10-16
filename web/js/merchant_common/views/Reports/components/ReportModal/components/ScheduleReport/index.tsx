import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { getAvailableEmails } from 'merchant_common/views/Reports/utils/commonUtils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { startSchedulePoll, stopSchedulePoll } from 'merchant_common/views/Reports/redux/reducer';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { ScheduleReportModal } from './ScheduleReport';

const mapStateToProps = ({ reportsCore, accounts, session }, { dashboardType, i18 }) => {
  const { user, mode } = session;
  const {
    overview: {
      reportConfigs: { allConfigs },
    },
  } = reportsCore[dashboardType];

  const availableEmails = getAvailableEmails(user, allConfigs.data.map((e) => e.emails).flat());
  const { availableAccounts, headers, parseSchedulePayloadBeforeSubmit } =
    getReportsDashboardConfig(dashboardType, session, accounts, mode, i18);

  const generatedBy = user.current;

  return {
    headers,
    allReportConfigs: allConfigs.data,
    availableEmails,
    availableAccounts,
    generatedBy,
    parseSchedulePayloadBeforeSubmit,
  };
};

const mapDispatchToProps = (dispatch, { dashboardType }) => ({
  closeModal: () => dispatch(closeModal()),
  showNotification: (payload) => dispatch(showNotification(payload)),
  startSchedulesPoll: () => dispatch(startSchedulePoll({ dashboardType })),
  stopSchedulesPoll: () => dispatch(stopSchedulePoll({ dashboardType })),
});

const ScheduleReport = connect(mapStateToProps, mapDispatchToProps)(ScheduleReportModal);

export default ScheduleReport;
