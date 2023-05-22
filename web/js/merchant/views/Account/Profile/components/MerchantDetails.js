import React from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { BrandName } from './BrandName';
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
  NC_UPDATE_WEBSITE,
  NC_ADD_WEBSITE,
  RR_UPDATE_WEBSITE,
  RR_ADD_WEBSITE,
  NC_ADD_ADDITIONAL_WEBSITE,
  RR_ADD_ADDITIONAL_WEBSITE,
} from 'merchant/views/Account/Profile/deeplink-constants';
import IntoView from 'common/ui/IntoView';
import EditTransactionLimit from './EditTransactionLimit';
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import AccountDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v1';
import BusinessDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/BusinessDetails';
import ActivationDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/ActivationDetails';
import BusinessWebsiteDetails from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails';

const MerchantDetails = ({ user, openModal, closeModal, tracking }) => {
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
        <AccountDetails isFlowRevamped={false} page="Profile" />
      </IntoView>

      <BusinessDetails isFlowRevamped={false} />

      <ActivationDetails isFlowRevamped={false} />

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

      <BrandName />

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
