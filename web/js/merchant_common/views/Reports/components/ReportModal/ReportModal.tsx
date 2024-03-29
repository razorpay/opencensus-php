import React, { lazy } from 'react';
import { BaseReportModalPropsType, ModalProps, RenderModalFnType } from './types';
import { CloseIcon, IconButton, Suspense } from 'merchant_common/views/Reports/components';
import { ReportCloseButton, ReportModalWrapper } from './styled';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { ConfirmModal } from './components/ConfirmModal';

const DownloadReport = lazy(
  () => import(/* webpackChunkName: "DownloadReport" */ './components/DownloadReport'),
);

const ScheduleReport = lazy(
  () => import(/* webpackChunkName: "ScheduleReport" */ './components/ScheduleReport'),
);

const ScheduleRunHistory = lazy(
  () => import(/* webpackChunkName: "ScheduleRunHistory" */ './components/ScheduleRunHistory'),
);

const DownloadCustomReport = lazy(
  () => import(/* webpackChunkName: "DownloadCustomReport" */ './components/DownloadCustomReports'),
);

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
    headers,
  }: ModalProps): JSX.Element => {
    const commonProps = {
      dashboardType,
      params,
      headers,
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
            emphasis="intense"
            onClick={onClose}
            icon={CloseIcon}
          />
        </ReportCloseButton>
        <Suspense minWidth={otherConfig?.width ?? 750}>
          <ReportModalWrapper width={otherConfig?.width} scrollable={otherConfig?.scrollable}>
            {children}
          </ReportModalWrapper>
        </Suspense>
      </div>
    );

    switch (type) {
      case 'download_report':
        return renderReportModal(<DownloadReport {...commonProps} />);
      case 'download_custom_report':
        return renderReportModal(<DownloadCustomReport {...commonProps} />, { scrollable: false });
      case 'create_edit_schedule':
        return renderReportModal(<ScheduleReport {...commonProps} />);
      case 'schedule_run_history':
        return renderReportModal(<ScheduleRunHistory {...commonProps} />, {
          width: 1000,
        });
      case 'confirm_modal':
        return (
          <>
            <ReportCloseButton>
              <IconButton
                accessibilityLabel="Close Modal"
                size="large"
                emphasis="intense"
                onClick={onClose}
                icon={CloseIcon}
              />
            </ReportCloseButton>
            <ConfirmModal {...commonProps} onClose={onClose} />
          </>
        );
      default:
        return <></>;
    }
  },
);

const ReportModal = (props: BaseReportModalPropsType): JSX.Element => <Modal {...props} />;

export default ReportModal;
