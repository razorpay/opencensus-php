import React from 'react';
import { ArrowRightIcon, Box, Button, DownloadIcon, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { Dispatch, AnyAction } from 'redux';

import { OpenModalPayload, Notification } from 'common/typings';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getAvailableEmails } from 'merchant_common/views/Reports/utils/commonUtils';

import ReportModal from './ReportModal';
import { DOWNLOAD_REPORTS } from './constants';
import { DownloadIconWrapper } from './styled';
import { DownloadReportsProps } from './types';

const DownloadReports: React.FC<DownloadReportsProps> = (props) => {
  const { entity, availableEmails, generatedBy, openModal, closeModal, showNotification } = props;
  const { heading, description, note } = DOWNLOAD_REPORTS[entity];

  const handleDownload = () => {
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
    <Box
      position="relative"
      backgroundColor="surface.background.level3.lowContrast"
      padding={['spacing.5', 'spacing.7']}
      overflow="hidden"
    >
      <Box display="flex" flexDirection="column" gap="spacing.7">
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          paddingRight="spacing.10"
          width="85%"
        >
          <Text weight="bold" size="large" marginBottom="spacing.4">
            {heading}
          </Text>
          <Text>{description}</Text>
          {note && (
            <Text>
              <Text as="span" weight="bold">
                Note:{' '}
              </Text>
              {note}
            </Text>
          )}
        </Box>
        <Box>
          <Button
            size="small"
            type="button"
            variant="primary"
            iconPosition="right"
            icon={ArrowRightIcon}
            onClick={handleDownload}
          >
            Download list
          </Button>
        </Box>
      </Box>
      <Box
        position="absolute"
        width="152px"
        height="152px"
        borderRadius="round"
        display="flex"
        alignItems="center"
        justifyContent="center"
        backgroundColor="surface.background.level2.lowContrast"
        top="-30px"
        right="-4px"
        transform="rotate(-14deg)"
      >
        <DownloadIconWrapper>
          <DownloadIcon size="2xlarge" color="surface.action.icon.disabled.lowContrast" />
        </DownloadIconWrapper>
      </Box>
    </Box>
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
