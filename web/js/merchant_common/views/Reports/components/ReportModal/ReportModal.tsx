import React from 'react';
import { BaseReportModalPropsType, ModalProps, RenderModalFnType } from './types';
import { CloseIcon, IconButton } from 'merchant_common/views/Reports/components';
import { DownloadReport } from './components/DownloadReport';
import { DownloadCustomReport } from './components/DownloadCustomReports';
import { ReportCloseButton, ReportModalWrapper } from './styled';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
// import { ScheduleReport } from './components/ScheduleReport';
// import { ScheduleRunHistory } from './components/ScheduleRunHistory';

const mapDispatchToProps = (dispatch) => ({
  closeModal: () => dispatch(closeModal()),
});

const Modal = connect(
  null,
  mapDispatchToProps,
)(
  ({
    type,
    params,
    onCloseCallback = () => {},
    closeModal,
    dashboardType,
    ariaLabelBy,
  }: ModalProps): JSX.Element => {
    const commonProps = {
      dashboardType,
      params,
    };

    const onClose = () => {
      onCloseCallback();
      closeModal();
    };

    const renderReportModal: RenderModalFnType = (children, otherConfig) => (
      <div aria-label={ariaLabelBy}>
        <ReportCloseButton>
          <IconButton
            accessibilityLabel="Close Modal"
            size="large"
            contrast="low"
            onClick={onClose}
            icon={CloseIcon}
          />
        </ReportCloseButton>
        <ReportModalWrapper
          initialWidth={otherConfig?.initialWidth}
          scrollable={otherConfig?.scrollable}
        >
          {children}
        </ReportModalWrapper>
      </div>
    );

    switch (type) {
      // case 'create_schedule':
      //   return renderReportModal(<ScheduleReport {...commonProps} />);
      case 'download_report':
        return renderReportModal(<DownloadReport {...commonProps} />);
      case 'download_custom_report':
        return renderReportModal(<DownloadCustomReport {...commonProps} />, { scrollable: false });
      // case 'schedule_run_history':
      //   return renderReportModal(<ScheduleRunHistory {...commonProps} />, {
      //     initialWidth: 1100,
      //   });
      default:
        return <></>;
    }
  },
);

export const ReportModal = (props: BaseReportModalPropsType): JSX.Element => <Modal {...props} />;
