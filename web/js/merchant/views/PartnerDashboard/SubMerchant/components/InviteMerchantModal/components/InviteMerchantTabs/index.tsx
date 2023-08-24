import React, { useEffect, useState } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { User } from 'common/typings';
import Tabs, { Tab, TabPane } from 'common/ui/ReactTabs';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import BulkInviteTab from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab';
import BulkAddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab/BulkAddMerchant';
import PublicLinksTab from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/PublicLinksTab';
import SingleInviteTab from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab';
import SingleAddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SingleAddMerchant';
import { trackInviteFlowModalLoaded } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import { INVITE_TAB_TYPES } from './constants';
import { StyledMerchantTabs } from './styled';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

const { SINGLE_INVITE, BULK_UPLOAD, PUBLIC_LINK } = INVITE_TAB_TYPES;

type InviteMerchantTabsProps = {
  user: User;
  shouldShowHeaderAndTabs: boolean;
  setShowHeaderAndTabs: (args: boolean) => void;
  onInviteTabsBackClick: () => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  productType: string;
};
const InviteMerchantTabs = ({
  user,
  productType,
  shouldShowHeaderAndTabs,
  setShowHeaderAndTabs,
  onInviteTabsBackClick,
  onDismiss,
  onAddSuccess,
}: InviteMerchantTabsProps): JSX.Element => {
  const [activeTabId, setActiveTabId] = useState(SINGLE_INVITE);
  const { isEasierAccessToSubmerchantKycEnabled } = usePartnerDashboardExperiments();
  useEffect(() => {
    setShowHeaderAndTabs(true);
  }, [activeTabId]);

  useEffect(() => {
    trackInviteFlowModalLoaded({ activeTabId, productType });
  }, [activeTabId, productType]);

  const tabPanes = [
    {
      id: SINGLE_INVITE,
      title: 'Using Email',
      content:
        isEasierAccessToSubmerchantKycEnabled && productType === PRODUCT_TYPE.PG ? (
          // TODO v2: consider lazy loading with suspense here.
          <SingleInviteTab
            productType={productType}
            onInviteTabsBackClick={onInviteTabsBackClick}
            onDismiss={onDismiss}
            onAddSuccess={onAddSuccess}
            setShowHeaderAndTabs={setShowHeaderAndTabs}
          />
        ) : (
          <SingleAddMerchant
            productType={productType}
            onInviteTabsBackClick={onInviteTabsBackClick}
            onDismiss={onDismiss}
            onAddSuccess={onAddSuccess}
            setShowHeaderAndTabs={setShowHeaderAndTabs}
          />
        ),
      isVisible: productType !== PRODUCT_TYPE.CAPITAL,
    },
    {
      id: BULK_UPLOAD,
      title: 'Bulk Upload',
      content:
        isEasierAccessToSubmerchantKycEnabled && productType === PRODUCT_TYPE.PG ? (
          <BulkInviteTab
            productType={productType}
            onDismiss={onDismiss}
            onAddSuccess={onAddSuccess}
            setShowHeaderAndTabs={setShowHeaderAndTabs}
          />
        ) : (
          <BulkAddMerchant
            productType={productType}
            onInviteTabsBackClick={onInviteTabsBackClick}
            onDismiss={onDismiss}
            onAddSuccess={onAddSuccess}
          />
        ),
      isVisible: true,
    },
    {
      id: PUBLIC_LINK,
      title: 'Public Link',
      content: <PublicLinksTab productType={productType} />,
      isVisible: !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.ReferalLinks),
    },
  ].filter(({ isVisible }) => isVisible);
  const selectedTabIndex = tabPanes.findIndex(({ id }) => id === activeTabId);
  return (
    <StyledMerchantTabs>
      <Box>
        <Tabs
          className={`invite-merchant-form ${shouldShowHeaderAndTabs ? '' : 'hide-tabs'}`}
          justified={true}
          selectedTabIndex={selectedTabIndex === -1 ? 0 : selectedTabIndex}
          onSelect={(tabIndex) => setActiveTabId(tabPanes[tabIndex].id)}
        >
          {tabPanes.map((tab) => (
            <TabPane key={tab.id}>
              <Box
                marginTop={shouldShowHeaderAndTabs ? 'spacing.8' : 'spacing.0'}
                minHeight="425px"
              >
                {tab.content}
              </Box>
            </TabPane>
          ))}
          {tabPanes.map((tab) => (
            <Tab key={tab.id} title={tab.title}>
              <Text weight="bold" size="small">
                {tab.title}
              </Text>
            </Tab>
          ))}
        </Tabs>
      </Box>
    </StyledMerchantTabs>
  );
};

export default compose<TODO_PD>(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({}, dispatch),
  ),
)(InviteMerchantTabs);
