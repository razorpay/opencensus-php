import DetailRow from 'merchant/components/DetailRow';
import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import Button from 'common/new-ui/Button';
import { roles, agentRole, RBLRoles, RegistrationLinkRoles } from 'merchant/helpers/data';
import { connect } from 'react-redux';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import rolesList from 'merchant/helpers/permissions/roles-list';
import AddEmailModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail';
import { analyticsTrack } from 'common/utils/analytics';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

const LoggedInUserDetails = ({
  isOrgRZP,
  loggedInUser,
  loggedInUserRole,
  handleUpdateClick,
  isEmailSelfServeEnabled,
  openModal,
}) => {
  const ROLES = { ...roles, ...agentRole, ...RBLRoles, ...RegistrationLinkRoles };

  const openAddEmailModal = () => {
    analyticsTrack({
      objectName: 'add email',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'profile',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    selfServeTrackInitiate({
      selfServeAction: 'Login Details Updated',
      page: 'Profile',
      screen: 'My Account',
    });
    openModal({
      size: 'small',
      component: <AddEmailModal screen="my account" />,
    });
  };

  return (
    <div class="panel panel-default">
      <div class="list-group details-row-container">
        <DetailRow label="User Name" value={() => <span>{titleCase(loggedInUser.name)}</span>} />
        <DetailRow
          label="Login Email"
          value={() =>
            loggedInUser.email ? (
              <span>
                {loggedInUser.email}
                {isOrgRZP && isEmailSelfServeEnabled && loggedInUserRole === 'owner' ? (
                  <Button.Transparent
                    onClick={handleUpdateClick}
                    data-testid="loggedin-user-email-edit"
                  >
                    <i class="i i-edit p-l" />
                  </Button.Transparent>
                ) : null}
              </span>
            ) : loggedInUserRole === rolesList.OWNER && !loggedInUser.signup_via_email ? (
              <span>
                <Button.Transparent onClick={openAddEmailModal}>Add Email</Button.Transparent>
              </span>
            ) : null
          }
        />
        {/* Added check to verify if loggedInUserRole is there or if its a valid role (part of role-list) */}
        <DetailRow label="Role" value={() => <span>{ROLES[loggedInUserRole]?.label}</span>} />
      </div>
    </div>
  );
};

export default connect(null, { openModal: fnOpenModal })(LoggedInUserDetails);
