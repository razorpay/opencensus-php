import React from 'react';
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
  Card,
  CardBody,
  CardHeader,
  CardHeaderLeading,
  CardHeaderIcon,
  ReportModal,
  Text,
  Box,
  Link,
} from 'merchant_common/views/Reports/components';
import { Icon } from 'merchant_common/views/Reports/components/styled';

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
)(({ data, openModal, isSchedulesEnabled }: CardPropsType) => {
  const { name, description, id, type } = data;
  const availableLinksArr = availableLinks({
    isSchedulesEnabled: type === NON_OWNED_CONFIG_TYPE ? false : isSchedulesEnabled,
  });
  const dashboardType = useDashboardType() as DashboardType;

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
              type="subdued"
              weight="regular"
              contrast="low"
            >
              {description}
            </Text>
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
          </Box>
        </CardBody>
      </Card>
    </CardContainer>
  );
});
