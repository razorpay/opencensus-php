import React from 'react';
import { BaseReportModalPropsType, ModalProps } from './types';
import { CloseIcon, IconButton } from 'merchant_common/views/Reports/components';
import { DownloadReport } from './components/DownloadReport';
import { DownloadCustomReport } from './components/DownloadCustomReports';
import { ReportCloseButton, ReportModalWrapper } from './styled';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';

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

    const renderReportModal = (children, scrollable?) => (
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
        <ReportModalWrapper scrollable={scrollable}>{children}</ReportModalWrapper>
      </div>
    );

    switch (type) {
      case 'download_report':
        return renderReportModal(<DownloadReport {...commonProps} />);
      case 'download_custom_report':
        return renderReportModal(<DownloadCustomReport {...commonProps} />, false);
      case 'create_schedule':
      default:
        return <></>;
    }
  },
);

export const ReportModal = (props: BaseReportModalPropsType): JSX.Element => <Modal {...props} />;
