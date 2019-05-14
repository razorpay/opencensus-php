import { Route, Switch, Redirect } from 'react-router-dom';
import MainNavLink from 'admin/components/MainNavLink';
import user, { org, isOrgRazorpay } from 'admin/user';
import { ShowWhenRoute } from 'admin/components/ShowWhen';

import Profile from 'admin/profile';

import MerchantList from 'admin/merchants/MerchantList';
import PlanList from 'admin/plans/List';
import GatewayRulesList from 'admin/gatewayrules/List';
import GatewayDowntimes from 'admin/gatewaydowntimes/List';
import Entities from 'admin/entities/List';
import ActionsList from 'admin/adminActions/ActionsList';
import EmailLogsList from 'admin/emailLogs/EmailLogsList';
import PublicFeatures from 'admin/publicfeatures/List';

import MerchantEntity from 'admin/merchants/entity/MerchantEntity';
import GenericEntity from 'admin/entities/Entity';

import MerchantActivationForm from 'admin/merchants/entity/MerchantActivationForm';
import MerchantAnalyticStats from 'admin/merchants/entity/MerchantAnalyticStats';
import MerchantReportConfig from 'admin/merchants/entity/MerchantReportConfig/List';
import PartnerConfig from 'admin/merchants/entity/PartnerConfig/List/index.js';

import WorkflowEntity from 'admin/workflows/Entity';
import WorkflowList from 'admin/workflows/List';

import RequestEntity from 'admin/requests/Entity';
import RequestList from 'admin/requests/List';

import GroupList from 'admin/groups/List';
import UserList from 'admin/users/List';
import UserEntity from 'admin/users/Entity';
import OrgsList from 'admin/organizations/List';
import FieldMaps from 'admin/fieldmaps/List';
import RoleList from 'admin/roles/List';
import PermissionsList from 'admin/permissions/List';
import AuditLog from 'admin/auditlog/List';
import OrgEntity from 'admin/organizations/Entity';
import InvitesList from 'admin/invites/List';

import ActivationList from 'admin/activations/List';
import InstantActivationList from 'admin/instantactivations/List';

import OperationsDashboard from 'admin/operations/List';
import ScroogeReports from 'admin/scrooge/Reports';
import ScroogeRefunds from 'admin/scrooge/Refunds';
import ScroogeActions from 'admin/scrooge/Actions';
import ScroogeRefund from 'admin/scrooge/Refund';

import Reports from 'admin/reports';
import BankFileUpload from 'admin/banks/file-upload';

const links = [
  [
    // title, url, permission, icon
    ['Merchants', '/merchants', 'view_all_merchants', 'user-manager'],
    ['Activations', '/activation', 'view_activation_form'],
    ['Instant Activations', '/instant-activation', 'view_activation_form'],
    ['Pricing Plans', '/pricing-plans', 'view_pricing_list', 'rupee'],
    ['Gateway Rules', '/gateway-rules', 'view_gateway_rule'],
    ['Downtimes', '/downtimes', 'view_gateway_downtime', 'pulse'],
    ['Entities', '/entities', 'view_all_entity'],
    ['Actions', '/actions', 'view_actions'],
    ['Email Logs', '/email-logs', 'view_email_logs', 'email'],
    [
      'Public Features',
      '/public-features',
      'manage_onboarding_submissions',
      'magic-hat',
    ],
    ['Ops Dashboard', '/operations', ''],
    ['Refunds', '/scrooge/refunds', ''],
  ],

  // workflow
  [
    ['Workflows', '/workflows', 'view_all_workflow', 'yes'],
    ['Requests', '/requests', 'view_workflow_requests'],
  ],

  // user access management
  [
    ['Reports', '/reports', 'download_non_merchant_report', 'books'],
    ['Invites', '/invites', 'view_merchant_invite', 'user-plus'],
    ['Organisations', '/orgs', 'view_all_org', 'building'],
    ['Users', '/users', 'view_all_admin', 'user'],
    ['Roles', '/roles', 'view_all_role', 'user-support'],
    ['Permissions', '/permissions', 'view_all_permission', 'hold'],
    ['Groups', '/groups', 'view_group', 'group'],
    ['Audit Log', '/audit-log', 'view_auditlog'],
  ],

  // banking dashboard
  [['Upload File', '/bank-file-upload', 'admin_bank_file_upload']],
];

// restrict routes to other orgs
const Heimdall_restrictRoutes = [
  '/activation',
  '/instant-activation',
  '/operations',
  '/scrooge/refunds',
];

export default ({ location }) => (
  <Switch location={location}>
    <Route
      path="/merchants/:id/activation"
      component={MerchantActivationForm}
    />
    <Route path="/merchants/:id/stats" component={MerchantAnalyticStats} />
    <Route
      path="/merchants/:id/report_config"
      component={MerchantReportConfig}
    />
    <Route path="/merchants/:id/partner_config" component={PartnerConfig} />
    <Route path="/merchants/:id" component={MerchantEntity} />
    <Route path="/merchants" component={MerchantList} />
    <Route path="/pricing-plans" component={PlanList} />
    <Route path="/gateway-rules" component={GatewayRulesList} />
    <Route path="/downtimes" component={GatewayDowntimes} />
    <Route path="/entities/:mode?/:selectedEntity?" component={Entities} />
    <Route path="/actions" component={ActionsList} />
    <Route path="/email-logs" component={EmailLogsList} />
    <Route path="/public-features" component={PublicFeatures} />

    <Route path="/workflows/:id" component={WorkflowEntity} />
    <Route path="/workflows" component={WorkflowList} />

    <Route path="/requests/:id" component={RequestEntity} />
    <Route path="/requests" component={RequestList} />
    <Route path="/groups" component={GroupList} />
    <Route path="/users/:id" component={UserEntity} />
    <Route path="/users" component={UserList} />
    <Route path="/orgs/:orgId" component={OrgEntity} />
    <Route path="/orgs" component={OrgsList} />
    <Route path="/fieldmaps/:orgId" component={FieldMaps} />
    <Route path="/roles" component={RoleList} />
    <Route path="/profile" component={Profile} />
    <Route path="/permissions" component={PermissionsList} />
    <Route path="/audit-log" component={AuditLog} />

    <Route path="/bank-file-upload" component={BankFileUpload} />

    <Route
      path="/entity/:type/:mode(live|test)/:id"
      component={GenericEntity}
    />
    <Route path="/entity/:type/:id" component={GenericEntity} />

    <Route path="/invites" component={InvitesList} />

    <RZPRoute path="/activation" component={ActivationList} />
    <RZPRoute path="/instant-activation" component={InstantActivationList} />
    <RZPRoute path="/operations" component={OperationsDashboard} />
    <RZPRoute path="/scrooge/reports" component={ScroogeReports} />
    <RZPRoute path="/scrooge/refunds" component={ScroogeRefunds} />
    <Route path="/scrooge/actions" component={ScroogeActions} />
    <RZPRoute
      path="/scrooge/refund/:mode(live|test)/:id"
      component={ScroogeRefund}
    />

    <Route path="/reports" component={Reports} />
    {user.permissions.indexOf('view_all_merchants') > -1 && (
      <Redirect to="/merchants" />
    )}
  </Switch>
);

export const Sidebar = ({ props }) => (
  <aside className={`org-${org.custom_code}`}>
    <a
      id="org-logo"
      href="/admin"
      style={{ backgroundImage: `url("${org.main_logo_url}")` }}
    />
    {links.map((linkGroup, i) => (
      <div key={i}>
        {linkGroup.map((l, i) => {
          // For other orgs, don't render restricted routes.
          // (FE only solution. BE doesn't support.)
          if (
            !isOrgRazorpay() &&
            Heimdall_restrictRoutes.find(route => route === l[1])
          ) {
            return null;
          }

          return (
            <MainNavLink
              key={i}
              to={l[1]}
              permission={l[2]}
              icon={l[3] || 'layers'}
            >
              {l[0]}
            </MainNavLink>
          );
        })}
      </div>
    ))}
  </aside>
);

function RZPRoute(props) {
  return (
    <ShowWhenRoute {...props} additionalCondition={_ => isOrgRazorpay()} />
  );
}
