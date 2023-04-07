import React from 'react';
import { sanitizeTabName, removeUnreconciledEntity } from 'merchant/views/Settlements/v2/util';
import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { connect } from 'react-redux';
import { TabsContainer, Tab, StyledDivTabText } from './styled';
import { Text, Badge } from '@razorpay/blade/components';
import { analyticsTrack } from 'common/utils/analytics';

const SECTION_TAB_MAPPING = {
  gross_settlements: ['payment', 'reversal'],
  deductions: [
    'adjustment',
    'transfer',
    'refund',
    'dispute',
    'funds',
    'settlement.ondemand',
    'credit_repayment',
  ],
};

const RenderTabs = ({
  tabsData,
  activeTab,
  handleTabChange,
  entityType,
  settlement,
}): JSX.Element => {
  const instrumentTabClick = (clickedTab) => {
    const entityView = entityType === 'credit' ? 'Gross Settlements' : 'Deductions';

    analyticsTrack({
      objectName: 'Merchant clicked',
      actionName: `${titleCase(clickedTab)}s tab in ${entityView} view`,
      screen: 'Settlements',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        page: 'Details View',
        settlements_experiment_name: 'v2',
        settlementId: settlement.id,
        settlementStatus: settlement.status,
        sessionId: window?.session_id ? window.session_id : undefined,
        component: `${titleCase(clickedTab)}s`,
      },
    });
  };

  return (
    <>
      {Object.keys(tabsData).map((tabKey, index) => {
        const isTabActive = tabKey === sanitizeTabName(activeTab);
        const tabCount = tabsData[tabKey].toString();
        return (
          <Tab
            key={index}
            onClick={() => {
              handleTabChange(tabKey);
              instrumentTabClick(tabKey);
            }}
            isActive={isTabActive}
          >
            <Badge size="small" variant={isTabActive ? 'blue' : 'neutral'}>
              {tabCount}
            </Badge>
            <StyledDivTabText isActive={isTabActive}>
              <Text weight="bold">{titleCase(tabKey)} </Text>
            </StyledDivTabText>
          </Tab>
        );
      })}
    </>
  );
};

type TabsData = Record<string, number>;

const Tabs = (props): JSX.Element => {
  let { items } = props.breakupDetails;
  const { activeTab, sectionType, settlement } = props;

  const getTabData = () => {
    items = removeUnreconciledEntity(items);

    return items.reduce((acc, tab) => {
      if (!tab.count) return acc;

      const tabName = sanitizeTabName(tab.component);
      if (sectionType) {
        if (!SECTION_TAB_MAPPING[sectionType].includes(tabName)) {
          return acc;
        }
      }
      if (!acc[tabName]) {
        acc[tabName] = tab.count;
      } else {
        acc[tabName] += tab.count;
      }

      return acc;
    }, {});
  };
  const tabData: TabsData = getTabData();

  return (
    <TabsContainer>
      <RenderTabs
        tabsData={tabData}
        activeTab={activeTab}
        handleTabChange={props.handleTabChange}
        entityType={props.entityType}
        settlement={settlement}
      />
    </TabsContainer>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  settlement: state.settlement.settlement,
});

export default connect(mapStateToProps, null)(Tabs);
