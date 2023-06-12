import React from 'react';
import defaultIcon from 'assets/reports/default.svg';
import { CardPropsType } from 'merchant_common/views/Reports/features/Overview/types';
import { CardWrapper, Header, Footer, CardLink, CardIcon, TextWrapper } from './style';
import { Heading, ReportModal, Text } from 'merchant_common/views/Reports/components';
import { availableLinks, reportTypeIconsMap } from './configs';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { trackOverviewSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { ClickableButton } from 'merchant_common/views/Reports/components/styled';
import { NON_OWNED_CONFIG_TYPE } from 'merchant_common/views/Reports/constants';

const mapStateToProps = ({ session }) => {
  const isSchedulesEnabled = Boolean(session.user.isRevampedReportsEnabled?.schedules);
  return { isSchedulesEnabled };
};

const mapDispatchToProps = (dispatch) => ({
  openModal: (modal) => dispatch(openModal(modal)),
});

export const Card = connect(
  mapStateToProps,
  mapDispatchToProps,
)(({ data, openModal, isSchedulesEnabled }: CardPropsType): JSX.Element => {
  const { name, description, id, type } = data;
  const { theme } = useTheme();
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
    <CardWrapper aria-label={`${name} Card`} theme={theme}>
      <div>
        <Header theme={theme}>
          <CardIcon src={reportTypeIconsMap[type] ?? defaultIcon} size="32px" />
          <Heading variant="regular" weight="bold" type="subtle" contrast="low">
            {name}
          </Heading>
        </Header>
        <TextWrapper theme={theme}>
          <Text
            truncateAfterLines={3}
            variant="body"
            type="subdued"
            weight="regular"
            contrast="low"
          >
            {description}
          </Text>
        </TextWrapper>
      </div>
      <Footer count={availableLinksArr.length} theme={theme}>
        {availableLinksArr.map((link) => (
          // eslint-disable-next-line
          //@ts-ignore
          <ClickableButton
            key={link.label}
            aria-label={`${link.label} Button`}
            onClick={() => onLinkClick(link.type)}
          >
            <Text variant="body" type="normal" weight="bold">
              <CardLink theme={theme}>{link.label}</CardLink>
            </Text>
          </ClickableButton>
        ))}
      </Footer>
    </CardWrapper>
  );
});
