import { Fragment } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import QueryString from 'query-string';
import { Badge, Box } from '@razorpay/blade/components';

import ListContainer from 'merchant/containers/ListContainer';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchSubmerchants as fetchAll } from 'merchant/reducers/collection';
import { switchMerchant } from 'merchant/reducers/session';
import { downloadSubmerchants } from 'merchant/reducers/submerchant';
import RTracking from 'react-tracking';

import DataTable from 'common/ui/Table/DataTable';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';

import ShowWhen from 'merchant/components/ShowWhen';
import Time from 'common/ui/Time';
import { getTime } from 'common/ui/item';
import {
  ActivationStatusLabel,
  SubmerchantSettlementLabel,
  XSubmerchantCAStatusLabel,
  CapitalSubMerchantStatusLabel,
} from 'merchant/components/StatusLabel';
import {
  submerchant as submerchantColumn,
  submerchantId as id,
  email as emailColumn,
} from 'common/ui/item/pair';

import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import AddMerchant from './AddMerchant';
import ConfirmGenerateReport from './components/ConfirmGenerateReport';
import ListFilter from './ListFilter';
import {
  trackSearchAnalytics,
  trackClearAnalytics,
  trackReferral,
  trackAddNewMerchantEvents,
} from 'merchant/views/PartnerDashboard/ga';
import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import { mediaWindowUrl } from './components/SocialShare';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import Loader from 'common/ui/Loader';
import ActionButtonKYC from './components/ActionButtonKYC';
import SubMerchantKycStatusLabel from './components/SubMerchantKycStatusLabel';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import AddNewSubMerchants from 'assets/onboarding/add-new-sub-merchants.png';
import ShareReferralLink from 'assets/onboarding/share-referral-link.png';
import Image from 'common/ui/Image';
import {
  getActivationStatusBulk,
  getFormattedCapitalResponse,
} from 'merchant/views/PartnerDashboard/SubMerchant/utils/activationStatusHelper';
import { fetchProducts } from 'merchant/reducers/capital';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import PGInvitesNavLinks from './components/PGInviteNavLinks';
import { isInviteRecentlyAccepted } from './utils';
import { fetchInvites } from './components/AllInvitesTable/api';

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
          <Badge contrast="high" fontWeight="bold" marginLeft="spacing.3" variant="positive">
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
              href="https://betasite.razorpay.com/docs/razorpay/add-partners-capital-doc/partners/capital/#track-leads-status"
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
  };

  constructor(props) {
    super(props);
    const { product, user } = this.props;

    // if this feature is enabled - allows partner to perform submerchant kyc without requesting them
    this.isSubMerchantKYCAccess = user.isFeatureEnabled('partner_sub_kyc_access');
    this.isCapitalProduct = product === PRODUCT_TYPE.CAPITAL;
    this.isPGProductWithInviteFlow =
      user.isPartnershipsInviteFlowEnabled && product === PRODUCT_TYPE.PG;
  }

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
    if (this.isPGProductWithInviteFlow) {
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

  clickableId = (isPurePlatform) => ({
    ...id,
    value: (item) => (
      <Link
        to={`/partners/submerchants/${item.id}`}
        onClick={() =>
          this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
            submerchantId: item.id,
          })
        }
      >
        {item.id}
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
          {item.id}
        </Link>
      ),
    }),
  });

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

  actions = {
    title: 'Actions',
    value: (submerchant) => (
      <ActionButtonKYC
        activation_status={submerchant.details.activation_status}
        kyc_access={submerchant.kyc_access}
        submerchant={submerchant}
        trackUserEvent={this.trackUserEvent}
        isSubMerchantKYCAccess={this.isSubMerchantKYCAccess}
        isPGProductWithInviteFlow={this.isPGProductWithInviteFlow}
        showNotification={this.props.showNotification}
      />
    ),
  };

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
          isSubMerchantKYCAccess={this.isSubMerchantKYCAccess}
        />
      ),
    };
  };

  handleAddMerchant = () => {
    this.trackUserEvent('partnerships.submerchant.add', {
      source: 'welcome screen',
    });
    trackAddNewMerchantEvents('Click - Welcome Screen');
    analyticsTrack({
      objectName: 'Add New Merchant',
      actionName: 'clicked',
      screen: 'affiliate accounts',
      properties: {
        location: 'welcome screen',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toCleverTap: true,
    });
    this.props.openModal({
      size: 'med-large',
      component: <AddMerchant closeModal={this.props.closeModal} org={this.props?.org} />,
    });
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
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.social', {
          partnerID: this.props.user.id,
        }),
      );
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
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.product_group.social', {
          productGroup,
          partnerID: this.props.user.id,
          socialMedia: platform,
          source: 'welcome screen',
        }),
      );
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
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.copy', {
          partnerID: this.props.user.id,
        }),
      );
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
      // new event
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.product_group.copy', {
          productGroup,
          partnerID: this.props.user.id,
          source: 'welcome screen',
        }),
      );
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

  render() {
    // prettier-ignore
    const { user, product, referralData, location, org } = this.props;
    const { capitalLoading, capitalItems, isPGInvitesEmpty, isPGInvitesEmptyCheckLoading } =
      this.state;
    let appIdColumn = [];
    let switchMerchantColumn = [];
    const referralUrl = referralData ? referralData[product]?.url : '';
    const isNonEmptyList = Array.isArray(this.props.items) && this.props.items.length > 0;
    const isNonEmptyCapitalList = Array.isArray(capitalItems) && capitalItems?.length > 0;
    const isFilterSearchUsed = location.search !== '';

    const isPGProductWithInviteFlow =
      user.isPartnershipsInviteFlowEnabled && product === PRODUCT_TYPE.PG;

    const isCombinedContactFilterEnabled =
      isPGProductWithInviteFlow ||
      (user.isPartnershipsContactFilterEnabled && user.isOrgRZP && product === PRODUCT_TYPE.PG);

    const shouldShowWelcomeScreen =
      (!isPGProductWithInviteFlow || isPGInvitesEmpty) &&
      !isNonEmptyList &&
      !isFilterSearchUsed &&
      !user.isPartner('pure_platform');

    if (user.isPartner('pure_platform')) {
      appIdColumn = [appId];
    } else if (user.isPartner('aggregator', 'fully_managed')) {
      switchMerchantColumn = [this.switchMerchantActionBtn(this.handleSwitchMerchant)];
    }

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

    const getResellerInviteFlowColumnsForRZP = () => [
      this.clickableId(user.isPartner('pure_platform')),
      mobileAndEmail,
      this.name(user.isPartner('pure_platform')),
      ...appIdColumn,
      activationStatus,
      this.actions,
      inviteAcceptedOn,
    ];

    const getTableColumns_PG = () => {
      const emailOrContact = isCombinedContactFilterEnabled ? mobileAndEmail : email;
      let columns = [
        this.name(user.isPartner('pure_platform')),
        id,
        emailOrContact,
        ...appIdColumn,
        addedOn,
        activationStatus,
        settlementStatus,
        ...switchMerchantColumn,
      ];
      if (this.props.isSubMerchantKycResellerEnabled && user.isPartner('reseller')) {
        const orgCode = org?.custom_code || 'rzp';
        const ORG_COLUMNS = {
          rzp: [
            this.name(user.isPartner('pure_platform')),
            id,
            emailOrContact,
            ...appIdColumn,
            this.getActivationStatus_NEW(),
            this.actions,
            // settlementStatus,
            addedOn,
            ...switchMerchantColumn,
          ],
          curlec: [
            this.name(user.isPartner('pure_platform')),
            id,
            email,
            ...appIdColumn,
            this.getActivationStatus_NEW(),
            addedOn,
            ...switchMerchantColumn,
          ],
        };
        columns = ORG_COLUMNS[orgCode];
      }
      return columns;
    };

    const currentProduct = product === PRODUCT_TYPE.PG ? 'page-pg' : 'page-x';

    return (
      <tabbed-container class="sub-merchants-tab">
        <div className={`sub-merchants-list ${currentProduct}`}>
          {!shouldShowWelcomeScreen && isPGProductWithInviteFlow ? (
            <PGInvitesNavLinks prefix="/partners/submerchants" />
          ) : null}
          <div className={`content-wrapper ${shouldShowWelcomeScreen ? 'partner-welcome' : ''}`}>
            {!shouldShowWelcomeScreen ? (
              <div
                className={`submerchant-filter-wrapper ${
                  isPGProductWithInviteFlow ? 'invite-flow-enabled' : ''
                } ${isCombinedContactFilterEnabled ? 'combined-filter-enabled' : ''}`}
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
            {isPGProductWithInviteFlow ? (
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
                columns={getResellerInviteFlowColumnsForRZP()}
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
                columns={[this.xName(), id, email, ...appIdColumn, xCurrentAccountStatus, addedOn]}
                {...this.props}
              />
            )}
            {isNonEmptyCapitalList && this.isCapitalProduct ? (
              <DataTable
                title="Sub Merchants"
                count={this.state.count}
                skip={this.state.skip}
                paginate={(params) => this.handleCapitalPaginate(params)}
                columns={[this.capitalName(), id, email, addedOn, capitalStatus]}
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
                      myRole="owner manager admin"
                      additionalCondition={(currentUser) =>
                        currentUser.isPartner() && !currentUser.isPartner('pure_platform')
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
                        !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.ReferalLinks)
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

export const PrimarySubMerchantList = connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    org: state.session.org,
    isSubMerchantKycResellerEnabled: state.session.user.isSubMerchantKycResellerEnabled,
    ...state.submerchants,
  }),
  getDispatchToProps(PRODUCT_TYPE.PG),
)(ProductSubMerchantsList);

export const XSubMerchantList = connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    ...state.submerchants,
  }),
  getDispatchToProps(PRODUCT_TYPE.X),
)(ProductSubMerchantsList);

export const CapitalSubMerchantList = connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    products: state.loanApplicationDetails.products,
    ...state.submerchants,
  }),
  getDispatchToProps(PRODUCT_TYPE.CAPITAL),
)(ProductSubMerchantsList);
