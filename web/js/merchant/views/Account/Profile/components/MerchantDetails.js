import React from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import DetailRow from 'merchant/components/DetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import { analyticsTrack } from 'common/utils/analytics';
import Button from 'common/new-ui/Button';
import GenerateTnCPage from 'merchant/components/Home/GenerateTnCPage';
import {
  EMAIL_UPDATE,
  CONTACT_NUMBER_UPDATE,
  BILLING_LABEL,
  NC_UPDATE_WEBSITE,
  NC_ADD_WEBSITE,
  RR_UPDATE_WEBSITE,
  RR_ADD_WEBSITE,
  NC_ADD_ADDITIONAL_WEBSITE,
  RR_ADD_ADDITIONAL_WEBSITE,
} from 'merchant/views/Account/Profile/deeplink-constants';
import IntoView from 'common/ui/IntoView';
import TextHighlighter from 'common/ui/TextHighlighter';
import EditTransactionLimit from './EditTransactionLimit';
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import ContactDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/ContactDetails';
import BusinessDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/BusinessDetails';
import AccountDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails';
import BusinessWebsiteDetails from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails';

const MerchantDetails = ({ user, changeBillingLabel, openModal, closeModal, tracking }) => {
  const openNeedsClarificationModal = (data) => {
    analyticsTrack({
      objectName: 'needs clarification respond',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        flowName: data.workflowType,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    openModal({
      size: 'small',
      component: <NeedsClarificationModal {...data} />,
    });
  };

  const showGenerateTnCModal = (eventName) => {
    openModal({
      size: 'small',
      component: <GenerateTnCPage openModal={openModal} onCloseModal={closeModal} />,
    });
    tracking.trackEvent(
      window.rzpQ.onbr().initiated(`act.${eventName}`, {
        clickSource: 'My Account',
      }),
    );
    analyticsTrack({
      objectName: `Act ${eventName.replaceAll('_', ' ')}`,
      actionName: 'initiated',
      screen: 'My account screen',
      properties: {
        clickSource: 'My Account',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  return (
    <div className="list-group details-row-container">
      <IntoView hashedWith={[EMAIL_UPDATE, CONTACT_NUMBER_UPDATE]}>
        <ContactDetails isFlowRevamped={false} page="Profile" />
      </IntoView>

      <BusinessDetails isFlowRevamped={false} />

      <AccountDetails isFlowRevamped={false} />

      {user.isActivated && (
        <IntoView
          hashedWith={[
            NC_UPDATE_WEBSITE,
            NC_ADD_WEBSITE,
            RR_UPDATE_WEBSITE,
            RR_ADD_WEBSITE,
            NC_ADD_ADDITIONAL_WEBSITE,
            RR_ADD_ADDITIONAL_WEBSITE,
          ]}
        >
          <BusinessWebsiteDetails isFlowRevamped={false} />
        </IntoView>
      )}

      {changeBillingLabel &&
        user.activation_status == 'activated' &&
        user.business_type != 2 &&
        user.business_type != 11 && (
          <IntoView hashedWith={BILLING_LABEL}>
            <DetailRow
              label={() => (
                <div>
                  <TextHighlighter hashedWith={BILLING_LABEL}>Brand Name</TextHighlighter>
                  <small className="help-content">
                    <i className="i i-info-outline" />
                    <Popover align="top" theme="dark">
                      <PopoverBody>
                        <div>
                          <div>Brand Name changes would be reflected in the following places,</div>
                          <div>- Transaction Confirmation Email</div>
                          <div>- Refund Email</div>
                          <div>- Payment Pages</div>
                          <div>- Payment link</div>
                          <div>- Checkout</div>
                          <div>- Smart Collect</div>
                          <div>- Route</div>
                          <div>- Subscriptions</div>
                        </div>
                      </PopoverBody>
                    </Popover>
                  </small>
                </div>
              )}
              value={() =>
                user.billing_label ? (
                  <span>
                    {user.billing_label}
                    <a
                      className="p-l"
                      onClick={(e) => {
                        selfServeTrackInitiate({
                          selfServeAction: 'Brand Name Updated',
                          page: 'Profile',
                          screen: 'My Account',
                        });
                        analyticsTrack({
                          objectName: 'Brand name edit',
                          actionName: 'clicked',
                          screen: 'my account',
                          properties: {
                            currentBrandName: user.billing_label,
                            ...getCommonAnalyticsProperties(window.rzp_user),
                          },
                        });
                        changeBillingLabel(e);
                      }}
                      title="Edit Billing Label"
                    >
                      <i className="i i-edit" />
                    </a>
                  </span>
                ) : (
                  <a className="p-l" onClick={changeBillingLabel} title="Set Billing Label">
                    Set Billing Label
                  </a>
                )
              }
            />
          </IntoView>
        )}

      <EditTransactionLimit transactionType="domestic" replyHandler={openNeedsClarificationModal} />

      <ShowWhen additionalCondition={(_user) => !_user.findTag('i18_hide_international')}>
        <EditTransactionLimit
          transactionType="international"
          replyHandler={openNeedsClarificationModal}
        />
      </ShowWhen>

      {user.canGenerateTnCPage && !user.business_website && !user.isAccepted && (
        <DetailRow
          label="Terms and Conditions Page"
          value={() => (
            <div className="terms-and-cond">
              {!user.merchant_tnc ? (
                <a
                  onClick={() => {
                    const eventName = 'generate_page_now';
                    showGenerateTnCModal(eventName);
                  }}
                >
                  Generate
                </a>
              ) : (
                <>
                  <Button.Secondary
                    type="button"
                    onClick={() => {
                      const eventName = 'edit_tnc_page';
                      showGenerateTnCModal(eventName);
                    }}
                    children="EDIT DETAILS"
                    className="edit-details-btn"
                  />
                  <div>
                    <a href={user?.merchant_tnc?.link} target="_blank" rel="noopener noreferrer">
                      <span>{user?.merchant_tnc?.link}</span> <i className="i i-external-link" />
                    </a>
                  </div>
                </>
              )}
            </div>
          )}
        />
      )}
    </div>
  );
};

export default connect(
  (state) => ({
    workflows: state.workflows,
  }),
  {
    openModal: fnOpenModal,
    closeModal: fnCloseModal,
    fetchWorkflowStatus: fetchWorkflowStatusReducer,
  },
)(rTracking()(MerchantDetails));
