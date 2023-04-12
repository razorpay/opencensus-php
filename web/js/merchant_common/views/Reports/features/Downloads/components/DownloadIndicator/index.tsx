import React, { Fragment, useState } from 'react';
import { DownloadIcon, IconButton, Spinner } from 'merchant_common/views/Reports/components';
import { checkDownloadsLogStatus } from 'merchant_common/views/Reports/configs/downloads.config';
import { connect } from 'react-redux';
import { downloadFromUFH } from 'merchant/utils/downloadFile';
import { pickProps } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import {
  GENERATED_REPORT_FILE_DOWNLOAD_FAILED,
  GENERATED_REPORT_FILE_DOWNLOAD_SUCCESS,
} from 'merchant_common/views/Reports/constants/notifications';
import { SessionReducerState } from 'common/typings';
import { trackDownloadsSection } from 'merchant_common/views/Reports/configs/analytics.config';

const mapStateToProps = ({ session }) => {
  const { user } = session as SessionReducerState;
  const { current } = pickProps(user, ['current', 'international']);

  return {
    additionalInfo: {
      currentMerchantId: current,
    },
  };
};

const mapDispatchToProps = (dispatch) => {
  return { showNotification: (payload) => dispatch(showNotification(payload)) };
};

const DownloadIndicatorComponent = connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  ({
    status,
    file_id,
    consumer,
    additionalInfo,
    showNotification,
    id,
    dashboardType,
  }): JSX.Element => {
    const internalLogStatus = checkDownloadsLogStatus(status, file_id);
    const [isFileDownloading, setFileDownloading] = useState(false);

    const onDownloadClick = (accountId) => {
      trackDownloadsSection({
        actionName: 'download_file_click',
        properties: {
          log_id: id,
          file_id,
        },
        dashboardType,
      });

      setFileDownloading(true);
      return downloadFromUFH(file_id, accountId)
        .then((response) => {
          trackDownloadsSection({
            actionName: 'download_file_success',
            properties: {
              log_id: id,
              file_id,
            },
            dashboardType,
          });
          showNotification({
            type: 'success',
            message: GENERATED_REPORT_FILE_DOWNLOAD_SUCCESS,
          });
          return response;
        })
        .catch(() => {
          trackDownloadsSection({
            actionName: 'download_file_failed',
            properties: {
              log_id: id,
              file_id,
            },
            dashboardType,
          });
          return showNotification({
            type: 'error',
            message: GENERATED_REPORT_FILE_DOWNLOAD_FAILED,
          });
        })
        .finally(() => setFileDownloading(false));
    };

    switch (internalLogStatus) {
      case 'Pending':
        return <Spinner accessibilityLabel="Loading please wait..." />;
      case 'Success': {
        const { currentMerchantId } = additionalInfo;
        const accountId = currentMerchantId !== consumer ? consumer.replace('acc_', '') : undefined;
        return isFileDownloading ? (
          <Spinner
            accessibilityLabel="Downloading file, please wait."
            label="Please wait"
            labelPosition="right"
          />
        ) : (
          <div aria-label="Download Report Icon Container" className="pointer">
            <IconButton
              icon={DownloadIcon}
              accessibilityLabel="Download Report"
              onClick={() => onDownloadClick(accountId)}
              size="large"
              contrast="low"
            />
          </div>
        );
      }
      default:
        return <Fragment />;
    }
  },
);

export const DownloadIndicator = (props) => {
  const dashboardType = useDashboardType();
  return <DownloadIndicatorComponent dashboardType={dashboardType} {...props} />;
};
