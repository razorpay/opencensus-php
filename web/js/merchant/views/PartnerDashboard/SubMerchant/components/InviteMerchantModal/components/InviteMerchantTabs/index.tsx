import React, { useEffect, useState } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { User } from 'common/typings';
import Tabs, { Tab, TabPane } from 'common/ui/ReactTabs';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import BulkInviteTab from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab';
import BulkAddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab/BulkAddMerchant';
import BulkOAuthInvite from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab/BulkOAuthInvite';
import PublicLinksTab from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/PublicLinksTab';
import PublicOAuthLinks from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/PublicLinksTab/PublicOAuthLinks';
import SingleInviteTab from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab';
import SingleAddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SingleAddMerchant';
import SingleOAuthInvite from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SingleOAuthInvite';

import { trackInviteFlowModalLoaded } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

import { INVITE_TAB_TYPES } from './constants';
import { StyledMerchantTabs } from './styled';
import { OAuthAppDetailsType } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

const { SINGLE_INVITE, BULK_UPLOAD, PUBLIC_LINK } = INVITE_TAB_TYPES;

type InviteMerchantTabsProps = {
  user: User;
  shouldShowHeaderAndTabs: boolean;
  setShowHeaderAndTabs: (args: boolean) => void;
  setShouldShowFooter: (args: boolean) => void;
  onInviteTabsBackClick: () => void;
  goToAppSelectionStep: () => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  productType: string;
  selectedApp?: OAuthAppDetailsType;
};
const InviteMerchantTabs = ({
  user,
  productType,
  selectedApp,
  shouldShowHeaderAndTabs,
  setShowHeaderAndTabs,
  setShouldShowFooter,
  onInviteTabsBackClick,
  goToAppSelectionStep,
  onDismiss,
  onAddSuccess,
}: InviteMerchantTabsProps): JSX.Element => {
  const [activeTabId, setActiveTabId] = useState(SINGLE_INVITE);
  const { isEasierAccessToSubmerchantKycEnabled, isPlatformPartnerInviteFlowEnabled } =
    usePartnerDashboardExperiments();
  useEffect(() => {
    setShowHeaderAndTabs(true);
    setShouldShowFooter(activeTabId !== PUBLIC_LINK);
  }, [activeTabId]);

  useEffect(() => {
    trackInviteFlowModalLoaded({ activeTabId, productType });
  }, [activeTabId, productType]);

  const tabPanes = [
    {
      id: SINGLE_INVITE,
      title: 'Using Email',
      content: (
        <>
          {isPlatformPartnerInviteFlowEnabled ? (
            <SingleOAuthInvite
              goToAppSelectionStep={goToAppSelectionStep}
              setShowHeaderAndTabs={setShowHeaderAndTabs}
              selectedApp={selectedApp}
            />
          ) : null}
          {!isPlatformPartnerInviteFlowEnabled ? (
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
            )
          ) : null}
        </>
      ),
      isVisible: productType !== PRODUCT_TYPE.CAPITAL,
    },
    {
      id: BULK_UPLOAD,
      title: 'Bulk Upload',
      content: (
        <>
          {isPlatformPartnerInviteFlowEnabled ? (
            <BulkOAuthInvite
              selectedApp={selectedApp}
              goToAppSelectionStep={goToAppSelectionStep}
              setShowHeaderAndTabs={setShowHeaderAndTabs}
            />
          ) : null}
          {!isPlatformPartnerInviteFlowEnabled ? (
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
            )
          ) : null}
        </>
      ),
      isVisible: true,
    },
    {
      id: PUBLIC_LINK,
      title: 'Public Link',
      content: (
        <>
          {isPlatformPartnerInviteFlowEnabled ? (
            <PublicOAuthLinks
              goToAppSelectionStep={goToAppSelectionStep}
              setShowHeaderAndTabs={setShowHeaderAndTabs}
              selectedApp={selectedApp}
            />
          ) : null}
          {!isPlatformPartnerInviteFlowEnabled ? (
            <PublicLinksTab productType={productType} />
          ) : null}
        </>
      ),
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
