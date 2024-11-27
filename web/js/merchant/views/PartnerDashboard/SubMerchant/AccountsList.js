import { Fragment } from 'react';
import { Badge, Box, Button } from '@razorpay/blade/components';
import AddNewSubMerchants from 'assets/onboarding/add-new-sub-merchants.png';
import ShareReferralLink from 'assets/onboarding/share-referral-link.png';
import QueryString from 'query-string';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withI18Service } from 'common/i18';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Image from 'common/ui/Image';
import Loader from 'common/ui/Loader';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import DataTable from 'common/ui/Table/DataTable';
import Time from 'common/ui/Time';
import TwoFactorVerificationContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { getTime } from 'common/ui/item';
import {
  submerchant as submerchantColumn,
  submerchantId as id,
  email as emailColumn,
} from 'common/ui/item/pair';
import { analyticsTrack } from 'common/utils/analytics';
import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import {
  ActivationStatusLabel,
  SubmerchantSettlementLabel,
  XSubmerchantCAStatusLabel,
  CapitalSubMerchantStatusLabel,
} from 'merchant/components/StatusLabel';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchProducts } from 'merchant/reducers/capital';
import { fetchSubmerchants as fetchAll } from 'merchant/reducers/collection';
import { switchMerchant } from 'merchant/reducers/session';
import { downloadSubmerchants } from 'merchant/reducers/submerchant';
import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import { fetchBureauLink } from 'merchant/views/PartnerDashboard/SubMerchant/api';
import {
  getActivationStatusBulk,
  getFormattedCapitalResponse,
} from 'merchant/views/PartnerDashboard/SubMerchant/utils/activationStatusHelper';
import {
  PRODUCT_TYPE,
  CAPITAL_STATUS,
  CREATE_BUREAU_COUNTDOWN_TIME,
  PARTNERSHIPS_WEBSITE_LINKS,
  ADD_NEW_MERCHANT_ELIGIBLE_ROLES,
} from 'merchant/views/PartnerDashboard/constants';
import {
  trackSearchAnalytics,
  trackClearAnalytics,
  trackReferral,
  trackAddNewMerchantEvents,
} from 'merchant/views/PartnerDashboard/ga';
import withPartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hocs/withPartnerDashboardExperiments';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import AddMerchant from './AddMerchant';
import ListFilter from './ListFilter';
import { trackAcceptedInvitesClick, trackAllInvitesClick } from './analytics';
import ActionButtonKYC from './components/ActionButtonKYC';
import { fetchInvites } from './components/AllInvitesTable/api';
import ConfirmGenerateReport from './components/ConfirmGenerateReport';
import { CreateBureauLink } from './components/CreateBureauLink';
import InviteMerchantModal from './components/InviteMerchantModal';
import { INVITE_MERCHANT_STEPS } from './components/InviteMerchantModal/constants';
import InviteNavLinks from './components/InviteNavLinks';
import SubMerchantKycStatusLabel from './components/SubMerchantKycStatusLabel';
import { mediaWindowUrl } from './components/utils/social-share';
import { isInviteRecentlyAccepted } from './utils';

const email = {
  title: 'Registered Email',
  value: emailColumn.value,
};

const mobileAndEmail = {
  title: 'Contact',
  value: ({ user, email }) => (
    <>
      <div>{user?.contact_mobile || ''}</div>
      {email}
    </>
  ),
};

const addedOn = {
  title: 'Added On',
  value: getTime('created_at', 'll'),
};

const inviteAcceptedOn = {
  title: 'Invite Accepted On',
  value: (item) => (
    <>
      <Time value={item.created_at} format="ll" />
      {isInviteRecentlyAccepted(item.created_at) && (
        <Box display="inline-block">
          <Badge emphasis="intense" marginLeft="spacing.3" color="positive">
            NEW
          </Badge>
        </Box>
      )}
    </>
  ),
};

const activationStatus = {
  title: (
    <Fragment>
      Activation Status&nbsp;
      <span>
        <i class="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>Current status of merchant's activation request</PopoverBody>
        </PopoverComponent>
      </span>
    </Fragment>
  ),
  value: (submerchant) =>
    submerchant.details && submerchant.details.activation_status ? (
      <>
        <ActivationStatusLabel status={submerchant.details.activation_status} />
        {submerchant.details.activation_status === 'instantly_activated' && (
          <>
            &nbsp;
            <i class="i i-info-circle" />
            <PopoverComponent align="right" theme="dark">
              <PopoverBody>
                The merchant can accept live payments but settlements will be on hold until KYC
                completion.
              </PopoverBody>
            </PopoverComponent>
          </>
        )}
      </>
    ) : (
      <span class="status-label label label-warning">Not Submitted</span>
    ),
};

const settlementStatus = {
  title: (
    <Fragment>
      Settlement Status&nbsp;
      <span>
        <i class="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>
            Current status of whether the merchant can receive the settlement
          </PopoverBody>
        </PopoverComponent>
      </span>
    </Fragment>
  ),
  value: (submerchant) => (
    <SubmerchantSettlementLabel
      status={
        submerchant.details &&
        submerchant.details.activation_status === 'activated' &&
        submerchant.hold_funds === false
          ? 'active'
          : 'inactive'
      }
    />
  ),
};

const xCurrentAccountStatus = {
  title: (
    <Fragment>
      Current Account Status&nbsp;
      <span>
        <i class="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>Current status of merchant's current account</PopoverBody>
        </PopoverComponent>
      </span>
    </Fragment>
  ),
  value: (submerchant) => (
    <XSubmerchantCAStatusLabel
      status={
        submerchant.banking_account && submerchant.banking_account.ca_status
          ? submerchant.banking_account.ca_status.toLowerCase()
          : 'inactive'
      }
    />
  ),
};

const capitalStatus = {
  title: (
    <Fragment>
      Activation Status&nbsp;
      <span>
        <i className="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>
            Click{' '}
            <a
              target="_blank"
              href={PARTNERSHIPS_WEBSITE_LINKS.CAPITAL_ADD_PARTNERS_KNOW_MORE_URL}
              rel="noopener noreferrer"
            >
              here
            </a>{' '}
            to know more
          </PopoverBody>
        </PopoverComponent>
      </span>
    </Fragment>
  ),
  value: (submerchant) => (
    <span>
      {submerchant.capitalActivationStatus ? (
        <CapitalSubMerchantStatusLabel status={submerchant.capitalActivationStatus} />
      ) : (
        <span>Not Available</span>
      )}
    </span>
  ),
};

const appId = {
  title: 'App Id',
  value: (item) => (
    <Link to={`/partners/applications/${item.application.id}`}>{item.application.id}</Link>
  ),
};

@RTracking(() => window.rzpQ.component('ProductSubMerchantsList'))
class ProductSubMerchantsList extends ListContainer {
  state = {
    capitalLoading: false,
    capitalItems: [],
    isDataLoaded: false,
    orgName: this.props?.org?.business_name || 'Razorpay',
    isPGInvitesEmpty: true,
    isPGInvitesEmptyCheckLoading: true,
    isInviteMerchantModalOpen: false,
    isCreateBureauButtonDisabled: false,
  };

  constructor(props) {
    super(props);
    const { product, user } = this.props;

    // if this feature is enabled - allows partner to perform submerchant kyc without requesting them
    this.isSubMerchantKYCAccess = user.isFeatureEnabled('partner_sub_kyc_access');
    this.isCapitalProduct = product === PRODUCT_TYPE.CAPITAL;
  }

  static contextType = TwoFactorVerificationContext;

  getActivationBulkData = (items) => {
    const { showNotification, products, loading } = this.props;
    const isDataFetched = !loading && !products?.loading;
    if (isDataFetched) {
      this.setState({ capitalLoading: true, isDataLoaded: true });
      if (products?.data?.length > 0 && items.length > 0) {
        const product = products.data.filter((item) => {
          return item.name === 'LOC_EMI';
        });
        const productId = product[0].id;
        const merchantIds = items.map((item) => item.id.replace('acc_', ''));
        getActivationStatusBulk(merchantIds, productId)
          .then((response) => {
            if (response?.data) {
              const { data } = response;
              const formattedData = getFormattedCapitalResponse(data, items);
              this.setState({ capitalItems: formattedData, capitalLoading: false });
            } else {
              this.setState({ capitalItems: items, capitalLoading: false });
            }
          })
          .catch(() => {
            this.setState({ capitalItems: items, capitalLoading: false });
            showNotification?.({
              type: 'error',
              message: 'There was an error while fetching Status',
            });
          });
      } else {
        this.setState({ capitalItems: items, capitalLoading: false });
      }
    }
  };

  capitalSearchHandler = () => {
    this.setState({ isDataLoaded: false });
  };
  componentDidMount() {
    const { fetchProducts } = this.props;
    if (this.isCapitalProduct) {
      fetchProducts();
    }
    this.checkIfPGInvitesEmpty();
  }
  componentDidUpdate(prevProps) {
    const { products, loading, location, items } = this.props;
    const { isDataLoaded } = this.state;

    if (this.isCapitalProduct && (!products?.loading || !loading) && !isDataLoaded) {
      this.getActivationBulkData(items);
    }
    if (this.isCapitalProduct && prevProps?.location?.search !== location?.search) {
      this.capitalSearchHandler();
    }
  }

  checkIfPGInvitesEmpty = () => {
    const { product, experiments } = this.props;
    const isPGProductWithInviteFlow =
      (experiments.isPartnershipsInviteFlowEnabled ||
        experiments.isPlatformPartnerInviteFlowEnabled) &&
      product === PRODUCT_TYPE.PG;

    if (isPGProductWithInviteFlow) {
      // Note: This is a partially nonblocking network call to determine the welcome screen condition
      fetchInvites(this.props.user.id, {
        product: PRODUCT_TYPE.PG,
        skip: 0,
        count: 25,
      })
        .then((data) => {
          this.setState({
            isPGInvitesEmpty: (data.data?.items || []).length === 0,
            isPGInvitesEmptyCheckLoading: false,
          });
        })
        .catch(() => {
          this.setState({ isPGInvitesEmpty: true, isPGInvitesEmptyCheckLoading: false });
          showNotification?.({
            type: 'error',
            message: 'There was an error',
          });
        });
    } else {
      this.setState({ isPGInvitesEmptyCheckLoading: false });
    }
  };

  searchAnalytics = () => {
    const searchQuery = QueryString.parse(this.props.location.search);
    const { count, email: emailId, id: accountId, name: accountName } = searchQuery;
    const result = this.props.items?.length;
    this.trackUserEvent('partnerships.dashboard.affiliate_account.search', {
      searchRequestParams: {
        count,
        emailId,
        accountId,
        accountName,
      },
      result,
    });
  };

  getCurrentProduct = () => {
    const { product } = this.props;
    if (product === PRODUCT_TYPE.PG) {
      return 'Payments';
    }
    if (product === PRODUCT_TYPE.X) {
      return 'X';
    }
    if (this.isCapitalProduct) {
      return 'Capital';
    }
    return '';
  };

  trackUserEvent = (eventName, properties = {}) => {
    const { user, tracking } = this.props;
    const productGroup = this.getCurrentProduct();
    tracking.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        productGroup,
        ...properties,
      }),
    );
  };

  name = (isPurePlatform) => ({
    ...submerchantColumn,
    value: (item) => (
      <Link
        to={`/partners/submerchants/${item.id}`}
        onClick={() =>
          this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
            submerchantId: item.id,
          })
        }
      >
        {item.name}
      </Link>
    ),
    ...(isPurePlatform && {
      value: (item) => (
        <Link
          to={`/partners/submerchants/${item.id}/${item.application.id}`}
          onClick={() =>
            this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
              submerchantId: item.id,
              applicationId: item.application.id,
            })
          }
        >
          {item.name}
        </Link>
      ),
    }),
  });

  xName = () => ({
    ...submerchantColumn,
    value: (item) => (
      <Link
        to={`/partners/submerchants/x/${item.id}`}
        onClick={() =>
          this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
            submerchantId: item.id,
          })
        }
      >
        {item.name}
      </Link>
    ),
  });

  capitalName = () => ({
    ...submerchantColumn,
    value: (item) => <Link to={`/partners/submerchants/capital/${item.id}`}>{item.name}</Link>,
  });

  getActivationStatus_NEW = () => {
    return {
      title: (
        <Fragment>
          Activation Status&nbsp;
          <span>
            <i class="i i-info-circle" />
            &nbsp;
            <PopoverComponent align="top" theme="dark">
              <PopoverBody>Current status of merchant's activation request</PopoverBody>
            </PopoverComponent>
          </span>
        </Fragment>
      ),
      value: (submerchant) => (
        <SubMerchantKycStatusLabel
          activation_status={submerchant.details.activation_status}
          kyc_access={submerchant.kyc_access}
        />
      ),
    };
  };

  handleAddMerchant = () => {
    trackAddNewMerchantEvents('Click - Welcome Screen');
    analyticsTrack({
      objectName: 'Add New Merchant',
      actionName: 'Clicked',
      screen: 'affiliate accounts',
      properties: {
        location: 'welcome screen',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toCleverTap: true,
    });
    const {
      openModal,
      experiments,
      product,
      i18: { isConfigTagEnabled },
    } = this.props;
    const { isPartnershipsInviteFlowEnabled, isPlatformPartnerInviteFlowEnabled, is2FaEnabled } =
      experiments;
    const isPlatformPartnerWithPGInviteFlow =
      isPlatformPartnerInviteFlowEnabled && product === PRODUCT_TYPE.PG;
    if (isPlatformPartnerWithPGInviteFlow || isPartnershipsInviteFlowEnabled) {
      this.setState({ isInviteMerchantModalOpen: true });
    } else if (is2FaEnabled) {
      this.context.criticalFlow({
        enforceVerifyOtp: true,
        modes: ['live', 'test'],
        onUserTwoFaVerified: () => {
          openModal({
            size: 'med-large',
            component: (
              <AddMerchant
                closeModal={this.props.closeModal}
                org={this.props?.org}
                isConfigTagEnabled={isConfigTagEnabled}
              />
            ),
          });
        },
      });
    } else {
      openModal({
        size: 'med-large',
        component: (
          <AddMerchant
            closeModal={this.props.closeModal}
            org={this.props?.org}
            isConfigTagEnabled={isConfigTagEnabled}
          />
        ),
      });
    }
  };

  handleSwitchMerchant = (merchantId) => () => {
    this.props
      .switchMerchant(merchantId)
      .then(() => {
        window.location.reload();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  confirmAndDownload = () => {
    this.props.openModal({
      size: 'large',
      component: (
        <ConfirmGenerateReport onDownload={this.onDownload} closeModal={this.props.closeModal} />
      ),
    });
  };

  onDownload = () => {
    this.trackUserEvent('partnerships.dashboard.affiliate_account.export');
    const { user } = this.props;
    this.props.showNotification({
      type: 'info',
      message: 'Your file will downloaded shortly',
      hidePrevious: true,
    });
    this.setState({ affiliatesDownloading: true });
    return downloadSubmerchants(user.isPartner('pure_platform'), user.id)
      .then((response) => {
        if (response.error) {
          this.props.showNotification({
            type: 'error',
            message: 'Oops!, Unable to export data of submerchants',
            hidePrevious: true,
          });
          return;
        }
        window.location = response.data.signed_url;
      })
      .catch(() => {
        this.props.showNotification({
          type: 'error',
          message: 'Oops!, Unable to export data of submerchants',
          hidePrevious: true,
        });
      })
      .finally(() => {
        this.setState({ affiliatesDownloading: false });
      });
  };
  shareReferralOn(platform, referralUrl) {
    mediaWindowUrl({
      type: platform,
      url: referralUrl,
      title: 'Sign up on Razorpay!',
      description:
        "Start using a wide range of Razorpay's payment solutions and unlock growth for your business with just a few clicks. Go live in less than 10 minutes.",
    });
    const productGroup = this.getCurrentProduct();
    if (this.props.product === PRODUCT_TYPE.X) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.x.social', {
          partnerID: this.props.user.id,
        }),
      );

      // new event
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.product_group.social', {
          productGroup,
          partnerID: this.props.user.id,
          socialMedia: platform,
          source: 'welcome screen',
        }),
      );
    }
    if (this.props.product === PRODUCT_TYPE.PG) {
      analyticsTrack({
        objectName: 'Social Share Referral Link',
        actionName: 'clicked',
        screen: 'affiliate accounts',
        properties: {
          location: 'welcome screen',
          socialMedia: platform,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
        toCleverTap: true,
      });
    }
  }

  handleCopyReferralLink = () => {
    const productGroup = this.getCurrentProduct();

    if (this.props.product === PRODUCT_TYPE.PG) {
      fireAnalyticsEvents({
        fbData: 'partner_copy_link',
        liData: 1764844,
      });
      trackReferral();
      analyticsTrack({
        objectName: 'Copy Referal Link',
        actionName: 'clicked',
        screen: 'affiliate accounts',
        properties: {
          location: 'welcome screen',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
        toCleverTap: true,
      });
    }
    if (this.props.product === PRODUCT_TYPE.X) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.x.copy', {
          partnerID: this.props.user.id,
        }),
      );
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.product_group.copy', {
          productGroup,
          partnerID: this.props.user.id,
          source: 'welcome screen',
        }),
      );
    }
  };

  handleCapitalPaginate = (params) => {
    this.paginate(params);
    this.capitalSearchHandler();
  };

  switchAccountLabel = (itemDetails) => {
    if (itemDetails.dashboard_access) {
      const isDeactivatedCurlecMerchantAccount =
        this.props?.user?.isOrgCurlec && !itemDetails.activated;
      if (isDeactivatedCurlecMerchantAccount) {
        return false;
      }
      return true;
    }
    return false;
  };

  switchMerchantActionBtn = (handleSwitchMerchant) => ({
    title: 'Switch Account',
    value: (item) => {
      if (this.switchAccountLabel(item)) {
        return (
          <button
            class="btn btn-default btn-xs"
            onClick={handleSwitchMerchant(item.id.replace('acc_', ''))}
          >
            Switch
          </button>
        );
      }
      return 'No Access';
    },
  });

  handleCreateBureauLinkClick = (item) => {
    fetchBureauLink(this.props.user.id, item.id.replace('acc_', ''))
      .then((response) => {
        const { data } = response;
        const bureauLinkData = {
          bureauLink: data?.bureau_link || '',
          partnerId: this.props?.user?.id || '',
          merchantId: item?.id?.replace('acc_', '') || '',
          smsCount: data.sms_count || 0,
        };
        // Todo use blade modal without props.openModal
        this.props.openModal({
          size: 'med-large',
          component: (
            <CreateBureauLink
              closeModal={this.props.closeModal}
              bureauLinkData={bureauLinkData}
              showNotification={this.props.showNotification}
            />
          ),
        });
        this.setState({ [`isCreateBureauButtonDisabled-${item.id}`]: true });
        setTimeout(() => {
          this.setState({ [`isCreateBureauButtonDisabled-${item.id}`]: false });
        }, CREATE_BUREAU_COUNTDOWN_TIME);
      })
      .catch((_err) => {
        this.props.showNotification?.({
          type: 'error',
          message: _err.errors,
        });
      });
  };
  createBureauLinkBtn = (handleCreateBureauLinkClick) => ({
    title: 'Actions',
    value: (item) => {
      return (
        <Button
          variant="secondary"
          onClick={() => {
            handleCreateBureauLinkClick(item);
          }}
          size="small"
          isDisabled={
            this.state[`isCreateBureauButtonDisabled-${item.id}`] ||
            item?.capitalActivationStatus?.toLowerCase() !== CAPITAL_STATUS.bureau_submission
          }
        >
          Create Bureau Link
        </Button>
      );
    },
  });

  render() {
    // prettier-ignore
    const { user, experiments, product, referralData, location, org } = this.props;
    const {
      isPartnershipsInviteFlowEnabled,
      isPlatformPartnerInviteFlowEnabled,
      isPartnershipCapitalBureauLinkEnabled,
    } = experiments;
    const { capitalLoading, capitalItems, isPGInvitesEmpty, isPGInvitesEmptyCheckLoading } =
      this.state;

    const referralUrl = referralData ? referralData[product]?.url : '';
    const isNonEmptyList = Array.isArray(this.props.items) && this.props.items.length > 0;
    const isNonEmptyCapitalList = Array.isArray(capitalItems) && capitalItems?.length > 0;
    const isFilterSearchUsed = location.search !== '';

    const isPlatformPartnerWithPGInviteFlow =
      experiments.isPlatformPartnerInviteFlowEnabled && product === PRODUCT_TYPE.PG;
    const isPGProductWithInviteFlow =
      isPlatformPartnerWithPGInviteFlow ||
      (experiments.isPartnershipsInviteFlowEnabled && product === PRODUCT_TYPE.PG);
    const isCombinedContactFilterEnabled = user.isOrgRZP && product === PRODUCT_TYPE.PG;

    const shouldShowWelcomeScreen =
      (!isPGProductWithInviteFlow || isPGInvitesEmpty) &&
      !isNonEmptyList &&
      !isFilterSearchUsed &&
      !user.isPartner('pure_platform');

    if (user.isPartnerIntent()) {
      this.props.openModal({
        size: 'xlarge',
        disableClose: true,
        component: <PartnerOnbr disableClose={true} />,
      });
    }

    if (
      this.props.loading ||
      capitalLoading ||
      (!isNonEmptyList && !isFilterSearchUsed && isPGInvitesEmptyCheckLoading)
    ) {
      return (
        <tabbed-container>
          <div class="sub-merchants-list">
            <Loader />
          </div>
        </tabbed-container>
      );
    }
    const actionsColumn = {
      title: 'Actions',
      value: (submerchant) => <ActionButtonKYC productType={product} submerchant={submerchant} />,
    };
    let conditionalAppIdColumn = [];
    let conditionalSwitchMerchantColumn = [];
    if (user.isPartner('pure_platform')) {
      conditionalAppIdColumn = [appId];
      conditionalSwitchMerchantColumn = [this.switchMerchantActionBtn(this.handleSwitchMerchant)];
    } else if (user.isPartner('aggregator', 'fully_managed')) {
      conditionalSwitchMerchantColumn = [this.switchMerchantActionBtn(this.handleSwitchMerchant)];
    }
    const getPGInviteFlowColumnsForRZP = () => {
      const conditionalActionsColumn =
        user.isPartner('pure_platform') && !this.isSubMerchantKYCAccess ? [] : [actionsColumn];

      return [
        this.name(user.isPartner('pure_platform')),
        id,
        mobileAndEmail,
        ...conditionalAppIdColumn,
        activationStatus,
        ...conditionalActionsColumn,
        ...conditionalSwitchMerchantColumn,
        inviteAcceptedOn,
      ];
    };

    const getTableColumns_PG = () => {
      const emailOrContact = isCombinedContactFilterEnabled ? mobileAndEmail : email;
      let columns = [
        this.name(user.isPartner('pure_platform')),
        id,
        emailOrContact,
        ...conditionalAppIdColumn,
        addedOn,
        activationStatus,
        settlementStatus,
        ...conditionalSwitchMerchantColumn,
      ];

      if (user.isSubMerchantKycEnabled && user.isPartner('reseller')) {
        const orgCode = org?.custom_code || 'rzp';
        const ORG_COLUMNS = {
          rzp: [
            this.name(user.isPartner('pure_platform')),
            id,
            emailOrContact,
            ...conditionalAppIdColumn,
            this.getActivationStatus_NEW(),
            actionsColumn,
            // settlementStatus,
            addedOn,
            ...conditionalSwitchMerchantColumn,
          ],
          curlec: [
            this.name(user.isPartner('pure_platform')),
            id,
            email,
            ...conditionalAppIdColumn,
            this.getActivationStatus_NEW(),
            addedOn,
            ...conditionalSwitchMerchantColumn,
          ],
        };
        columns = ORG_COLUMNS[orgCode];
      }

      return columns;
    };

    const currentProduct = product === PRODUCT_TYPE.PG ? 'page-pg' : 'page-x';

    const capitalColumns = [this.capitalName(), id, email, addedOn, capitalStatus];
    if (isPartnershipCapitalBureauLinkEnabled) {
      capitalColumns.push(this.createBureauLinkBtn(this.handleCreateBureauLinkClick));
    }
    return (
      <tabbed-container class="sub-merchants-tab">
        <div className={`sub-merchants-list ${currentProduct}`}>
          {!shouldShowWelcomeScreen && isPGProductWithInviteFlow ? (
            <InviteNavLinks
              productType={PRODUCT_TYPE.PG}
              onAcceptedInvitesClick={trackAcceptedInvitesClick}
              onAllInvitesClick={trackAllInvitesClick}
            />
          ) : null}
          <div className={`content-wrapper ${shouldShowWelcomeScreen ? 'partner-welcome' : ''}`}>
            {!shouldShowWelcomeScreen ? (
              <div
                className={`submerchant-filter-wrapper ${
                  isPGProductWithInviteFlow ? 'invite-flow-enabled' : ''
                } ${isCombinedContactFilterEnabled ? 'combined-filter-enabled' : ''} ${
                  user.isPartner('pure_platform') ? 'show-app-id-filter' : ''
                }`}
              >
                <ListFilter
                  form="SubmerchantListFilter"
                  count={this.state.count}
                  onSearchAnalytics={trackSearchAnalytics}
                  onClearAnalytics={trackClearAnalytics}
                  showAppIdFilter={user.isPartner('pure_platform')}
                  showActivationStatusFilter={isCombinedContactFilterEnabled}
                  showContactFilter={isCombinedContactFilterEnabled}
                  showEmailIdFilter={!isCombinedContactFilterEnabled}
                  showPhoneNumberFilter={
                    product === PRODUCT_TYPE.PG && !isCombinedContactFilterEnabled
                  }
                />
                <button
                  class="btn btn-default export-all-btn"
                  onClick={this.confirmAndDownload}
                  disabled={this.state.affiliatesDownloading}
                >
                  {!this.state.affiliatesDownloading ? (
                    <>
                      <i className="i i-download" />
                      <span>Export All (CSV)</span>
                    </>
                  ) : (
                    <>Exporting Affiliates...</>
                  )}
                </button>
              </div>
            ) : null}
            {isPGProductWithInviteFlow && !shouldShowWelcomeScreen ? (
              <DataTable
                title="Sub Merchants"
                count={this.state.count}
                skip={this.state.skip}
                paginate={this.paginate}
                empty_placeholder={
                  isFilterSearchUsed ? (
                    <div class="empty-table-message">
                      <h4>No Search results found</h4>
                    </div>
                  ) : (
                    <div class="empty-table-message">
                      <h4>All Accepted Invites</h4>
                      <p className="m-t">
                        All accepted invites will be visible here once the client has accepted the
                        invite sent by you.
                      </p>
                    </div>
                  )
                }
                columns={getPGInviteFlowColumnsForRZP()}
                {...this.props}
              />
            ) : null}

            {!isPGProductWithInviteFlow ? (
              <>
                {isFilterSearchUsed &&
                  (!isNonEmptyList || (this.isCapitalProduct && !isNonEmptyCapitalList)) && (
                    <div style={{ flex: 2, textAlign: 'center' }}>
                      <div>
                        <h3 class="sub-title">No Search results found</h3>
                      </div>
                    </div>
                  )}
                {isNonEmptyList && product === PRODUCT_TYPE.PG && (
                  <DataTable
                    title="Sub Merchants"
                    count={this.state.count}
                    skip={this.state.skip}
                    paginate={this.paginate}
                    columns={getTableColumns_PG()}
                    {...this.props}
                  />
                )}
              </>
            ) : null}
            {isNonEmptyList && product === PRODUCT_TYPE.X && (
              <DataTable
                title="Sub Merchants"
                count={this.state.count}
                skip={this.state.skip}
                paginate={this.paginate}
                columns={[
                  this.xName(),
                  id,
                  email,
                  ...conditionalAppIdColumn,
                  xCurrentAccountStatus,
                  addedOn,
                ]}
                {...this.props}
              />
            )}
            {isNonEmptyCapitalList && this.isCapitalProduct ? (
              <DataTable
                title="Sub Merchants"
                count={this.state.count}
                skip={this.state.skip}
                paginate={(params) => this.handleCapitalPaginate(params)}
                columns={capitalColumns}
                items={capitalItems}
              />
            ) : null}
            {shouldShowWelcomeScreen && (
              <>
                <div style={{ flex: 2, textAlign: 'center' }}>
                  <div>
                    <h1 class="main-title"> Welcome to Partner Dashboard</h1>
                    <h3 class="sub-title">
                      Get started by adding merchants to {this.state.orgName}
                    </h3>
                  </div>
                </div>
                <div style={{ flex: 3 }} class="action-area">
                  <div>
                    <ShowWhen
                      myRole={ADD_NEW_MERCHANT_ELIGIBLE_ROLES}
                      additionalCondition={(currentUser) =>
                        currentUser.isPartner() &&
                        (isPlatformPartnerWithPGInviteFlow ||
                          !currentUser.isPartner('pure_platform'))
                      }
                    >
                      <div>
                        <div>
                          <Image src={AddNewSubMerchants} isWebP />
                        </div>
                        <p>
                          <strong>Invite a merchant</strong> by adding their details
                        </p>
                        <div style={{ paddingTop: '20px' }}>
                          <button
                            class="btn btn-primary pull-right m-l"
                            onClick={this.handleAddMerchant}
                          >
                            <i class="i i-plus line-height-9" /> Add New Merchant
                          </button>
                        </div>
                      </div>
                    </ShowWhen>
                    <ShowWhen
                      additionalCondition={(currentUser) =>
                        ((currentUser.isPartner() && currentUser.isPartner('reseller')) ||
                          this.isCapitalProduct) &&
                        !this.props?.i18?.isConfigTagEnabled('partnership.referral_links')
                      }
                    >
                      <div>
                        <div>
                          <Image src={ShareReferralLink} isWebP />
                        </div>
                        <p>
                          Share the <strong>invite link</strong> on social media
                        </p>

                        <div class="social-share-btn-grp">
                          <CustomClipboard value={referralUrl}>
                            <button
                              class="btn btn-primary pull-right m-l"
                              onClick={this.handleCopyReferralLink}
                            >
                              <i class="i i-link line-height-9" /> Copy Link
                            </button>
                          </CustomClipboard>
                          <img
                            src="/img/social-media/fb.png"
                            onClick={() => this.shareReferralOn('fb', referralUrl)}
                          />
                          <img
                            src="/img/social-media/twitter.png"
                            onClick={() => this.shareReferralOn('twitter', referralUrl)}
                          />
                          <img
                            src="/img/social-media/whatsapp.png"
                            onClick={() => this.shareReferralOn('whatsapp', referralUrl)}
                          />
                        </div>
                      </div>
                    </ShowWhen>
                  </div>
                </div>
              </>
            )}
          </div>
        </div>

        {isPartnershipsInviteFlowEnabled || isPlatformPartnerWithPGInviteFlow ? (
          <InviteMerchantModal
            initialProductType={product}
            initialStep={
              isPlatformPartnerInviteFlowEnabled
                ? INVITE_MERCHANT_STEPS.CHOOSE_OAUTH_APP
                : INVITE_MERCHANT_STEPS.INVITE_TABS
            }
            isOpen={this.state.isInviteMerchantModalOpen}
            onDismiss={() => this.setState({ isInviteMerchantModalOpen: false })}
          />
        ) : null}
      </tabbed-container>
    );
  }
}

const getDispatchToProps = (productType = PRODUCT_TYPE.PG) => {
  return {
    fetchAll: (params) => {
      return fetchAll({
        ...params,
        product: productType,
      });
    },
    openModal,
    closeModal,
    switchMerchant,
    showNotification,
    fetchProducts,
  };
};

export const PrimarySubMerchantList = compose(
  withPartnerDashboardExperiments,
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
      org: state.session.org,
      ...state.submerchants,
    }),
    getDispatchToProps(PRODUCT_TYPE.PG),
  ),
  withRouter,
  withI18Service,
)(ProductSubMerchantsList);

export const XSubMerchantList = compose(
  withPartnerDashboardExperiments,
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
      ...state.submerchants,
    }),
    getDispatchToProps(PRODUCT_TYPE.X),
  ),
  withRouter,
  withI18Service,
)(ProductSubMerchantsList);

export const CapitalSubMerchantList = compose(
  withPartnerDashboardExperiments,
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
      products: state.loanApplicationDetails.products,
      ...state.submerchants,
    }),
    getDispatchToProps(PRODUCT_TYPE.CAPITAL),
  ),
  withRouter,
  withI18Service,
)(ProductSubMerchantsList);
