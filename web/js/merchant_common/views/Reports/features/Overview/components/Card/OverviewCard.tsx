import React, { useState } from 'react';
import defaultIcon from 'assets/reports/default.svg';
import { CardPropsType } from 'merchant_common/views/Reports/features/Overview/types';
import { CardContainer } from './style';
import { availableLinks, reportTypeIconsMap } from './configs';
import { trackOverviewSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { NON_OWNED_CONFIG_TYPE } from 'merchant_common/views/Reports/constants';
import {
  modalTitles,
  modalDescriptions,
  maxCustomReportLimitReached,
  customReportsLimit,
  deviceRestrictionMessages,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';
import {
  Card,
  CardBody,
  CardHeader,
  CardHeaderLeading,
  CardHeaderIcon,
  ReportModal,
  Text,
  Box,
  Link,
  IconButton,
  TrashIcon,
  DownloadIcon,
} from 'merchant_common/views/Reports/components';
import { Icon } from 'merchant_common/views/Reports/components/styled';
import { CopyIcon, EditIcon, Tooltip } from '@razorpay/blade/components';
import { ConfirmModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/ConfirmModal';
import { CreateConfigModel } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/CreateConfigModel';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import { showNotification } from 'merchant_common/reducers/notifications';
import { Action } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/types';
import { useBladeBreakpoints } from '@libs/shared-utils';

const mapStateToProps = ({ session }) => {
  const isSchedulesEnabled = Boolean(session.user.isRevampedReportsEnabled?.schedules);
  return { isSchedulesEnabled };
};

const mapDispatchToProps = (dispatch) => ({
  openModal: (modal) => dispatch(openModal(modal)),
});

export const OverviewCard = connect(
  mapStateToProps,
  mapDispatchToProps,
)(({ isCustomConfig = false, data, openModal, isSchedulesEnabled }: CardPropsType) => {
  const { name, description, id, type } = data;
  const [isOpenExitPromptModal, setIsOpenExitPromptModal] = useState(false);
  const [isOpenCreateConfigModal, setIsOpenCreateConfigModal] = useState(false);
  const [isEditOrClone, setIsEditOrClone] = useState('');
  const availableLinksArr = availableLinks({
    isSchedulesEnabled: type === NON_OWNED_CONFIG_TYPE ? false : isSchedulesEnabled,
  });
  const dashboardType = useDashboardType() as DashboardType;
  const userConfigs = useCreateConfigModal((state) => state.userConfigs);
  const updateStandardReportName = useCreateConfigModal((state) => state.updateStandardReportName);
  const updateReportName = useCreateConfigModal((state) => state.updateReportName);
  const updateReportDescription = useCreateConfigModal((state) => state.updateReportDescription);
  const setSelectedConfigId = useCreateConfigModal((state) => state.setSelectedConfigId);
  const setIsReportTypeChanged = useCreateConfigModal((state) => state.setIsReportTypeChanged);
  const { isDesktop } = useBladeBreakpoints();

  const handleEditOrClone = (actionType: string): void => {
    if (!isDesktop) {
      showNotification({
        type: 'error',
        message:
          actionType === Action.Edit
            ? deviceRestrictionMessages.Edit
            : deviceRestrictionMessages.Clone,
      });
    } else if (actionType === Action.Clone && userConfigs.length >= customReportsLimit) {
      showNotification({
        type: 'error',
        message: maxCustomReportLimitReached,
      });
    } else {
      setIsEditOrClone(actionType);
      userConfigs.forEach((config) => {
        if (config.id === id) {
          updateStandardReportName(config.name);
          updateReportName(config.name);
          updateReportDescription(config.description);
          setSelectedConfigId(config.id);
          setIsReportTypeChanged(true);
        }
        setIsOpenCreateConfigModal(true);
      });
    }
  };

  const onLinkClick = (linkType: string) => {
    trackOverviewSection({
      actionName:
        linkType === 'schedule' ? 'Cards Schedule Link Click' : 'Cards Download Link Click',
      properties: {
        report_type: type,
        config_id: id,
      },
      dashboardType,
    });

    openModal({
      component: (
        <ReportModal
          params={{
            startPollOnSubmit: false,
            selectedConfig: id,
          }}
          type={
            linkType === 'schedule'
              ? 'create_edit_schedule'
              : type === NON_OWNED_CONFIG_TYPE
              ? 'download_custom_report'
              : 'download_report'
          }
          dashboardType={dashboardType}
        />
      ),
      size: 'custom',
    });
  };
  return (
    <>
      {isOpenExitPromptModal ? (
        <ConfirmModal
          isOpenExitPromptModal={isOpenExitPromptModal}
          setIsOpenExitPromptModal={setIsOpenExitPromptModal}
          title={modalTitles['delete']}
          description={modalDescriptions['deleteConfig']}
          isDeleteConfig={true}
          configId={id}
        />
      ) : null}
      {isOpenCreateConfigModal ? (
        <CreateConfigModel
          isOpen={isOpenCreateConfigModal}
          setIsOpen={setIsOpenCreateConfigModal}
          isEditOrClone={isEditOrClone}
          id={id}
        />
      ) : null}
      <CardContainer>
        <Card elevation="midRaised">
          <CardHeader>
            <CardHeaderLeading
              title={name}
              prefix={
                <CardHeaderIcon
                  icon={() => <Icon src={reportTypeIconsMap[type] ?? defaultIcon} size="32px" />}
                />
              }
            />
          </CardHeader>
          <CardBody>
            <Box display="flex" flexDirection="column" justifyContent="space-between" height="100%">
              <Text
                truncateAfterLines={3}
                variant="body"
                weight="regular"
                color="surface.text.gray.muted"
              >
                {description}
              </Text>
              {isCustomConfig ? (
                <Box
                  display="flex"
                  justifyContent="flex-end"
                  width="100%"
                  marginTop="20px"
                  gap="spacing.4"
                >
                  {availableLinksArr.map((link) => (
                    <Tooltip key={link.type} content="Download Report">
                      <IconButton
                        icon={() => <DownloadIcon color="interactive.icon.primary.subtle" />}
                        size="medium"
                        accessibilityLabel={`${link.label} Button`}
                        onClick={() => onLinkClick(link.type)}
                      />
                    </Tooltip>
                  ))}

                  <Tooltip key="Edit" content="Edit Report">
                    <IconButton
                      key="Edit"
                      icon={() => <EditIcon color="interactive.icon.primary.subtle" />}
                      size="medium"
                      accessibilityLabel="Edit Icon"
                      onClick={() => {
                        handleEditOrClone(Action.Edit);
                      }}
                    />
                  </Tooltip>

                  <Tooltip key="Clone" content="Clone Report">
                    <IconButton
                      key="Clone"
                      icon={() => <CopyIcon color="interactive.icon.primary.subtle" />}
                      size="medium"
                      accessibilityLabel="Clone Icon"
                      onClick={() => {
                        handleEditOrClone(Action.Clone);
                      }}
                    />
                  </Tooltip>

                  <Tooltip key="Delete" content="Delete Report">
                    <IconButton
                      key="Delete"
                      icon={() => <TrashIcon color="interactive.icon.primary.subtle" />}
                      size="medium"
                      accessibilityLabel="Delete Icon"
                      onClick={() => {
                        setIsOpenExitPromptModal(true);
                      }}
                    />
                  </Tooltip>
                </Box>
              ) : (
                <Box marginTop="spacing.6" display="flex" justifyContent="space-between">
                  {availableLinksArr.map((link) => (
                    <Link
                      accessibilityLabel={`${link.label} Button`}
                      key={link.type}
                      onClick={() => onLinkClick(link.type)}
                      variant="button"
                    >
                      {link.label}
                    </Link>
                  ))}
                </Box>
              )}
            </Box>
          </CardBody>
        </Card>
      </CardContainer>
    </>
  );
});
