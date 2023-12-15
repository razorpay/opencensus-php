import './styles/error-alert.styl';
import React from 'react';
import { connect } from 'react-redux';
import { NavLink, Navigate } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { Alert as BladeAlert } from '@razorpay/blade/components';
import {
  CASH_ADVANCE_BASE_URL,
  CASH_ADVANCE_SECTIONS,
  HOTJAR_TRIGGER,
  NOOP,
  COLLECTIONS_PRODUCT_TYPES,
  REPAYMENT_FREQUENCY_TYPES,
} from './constants';
import Withdrawals from './withdrawals';
import Overview from './Overview';
import Repayments from './Repayments/Repayments';
import {
  fetchSeedData,
  fetchFunctionalWithdrawalConfigByMerchantID,
  fetchWithdrawals,
} from 'merchant/reducers/capital/withdrawals';
import { fetchAccountProductConfig } from 'merchant/reducers/capital/accounts';
import { fetchRepayments } from 'merchant/reducers/capital/repayments';
import { fetchMerchantDetails } from 'merchant/reducers/capital/migrations';
import LoaderDots from 'common/ui/LoaderDots';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import GromorAgreementModal from 'merchant/views/Capital/components/Modals/GromorAgreementModal';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import LegalSignIcon from '../../../../../icons/merchant/legal.svg';
import RoundTick from '../../../../../icons/merchant/tick-round.svg';
import { getItem, removeItem } from 'common/utils/localStorage';
import { checkifDateExpired, getProductType } from 'merchant/views/Capital/utils';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import moment from 'moment';
import Settings from './views/Settings';
import { isADayAgo, isLenderLiquiloans, isMerchantNew, showSettings } from './utils';
import Alert from 'common/new-ui/Alert';
import LlDashboard from './LlDashboard';
import withEDIMigration from 'merchant/views/Capital/CashAdvance/withEDIMigration';

const Loader = () => {
  return (
    <div className="custom-loader">
      <LoaderDots />
    </div>
  );
};

@connect(
  (state) => {
    const {
      session: { user },
      withdrawals: { withdrawalConfiguration, list, seedData },
      migrations: { merchantGromorEsignDetails },
      repayments: { list: repaymentsList },
      productConfig: { productConfig },
    } = state;

    return {
      user,
      withdrawalConfiguration,
      list,
      seedData,
      merchantGromorEsignDetails,
      repaymentsList,
      productConfig,
    };
  },
  {
    fetchFunctionalWithdrawalConfigByMerchantID,
    fetchSeedData,
    fetchWithdrawals,
    fetchRepayments,
    fetchMerchantDetails,
    fetchAccountProductConfig,
    openModal,
    closeModal,
  },
)
class CashAdvance extends React.Component {
  constructor(props) {
    super(props);

    const { list: { data = null } = {} } = props;

    this.state = {
      isLoading: !data,
      isGromorModalOpen: true,
      withdrawalSkip: 0,
    };
  }

  componentDidMount() {
    const {
      user: { current },
    } = this.props;
    const productType = getProductType(this.props.user);

    this.props
      .fetchFunctionalWithdrawalConfigByMerchantID({
        owner_id: current,
        owner_type: 'RZP_MERCHANT',
        product_type: productType,
      })
      .then(
        ({
          data: {
            withdrawal_config: {
              configuration: { custom_partner_fields: { partner_id = '' } = {} } = {},
            } = {},
          } = {},
        }) => {
          const locEsignEnabled = this.props.user.isFeatureEnabled('loc_esign');

          if (locEsignEnabled && partner_id !== 'GROMOR')
            this.props
              .fetchMerchantDetails({
                owner_id: current,
              })
              .then(({ data: { due_at } = {} } = {}) => {
                const isDateExpired = checkifDateExpired(new Date(due_at));
                if (!isDateExpired) {
                  this.setState({ isGromorModalOpen: true });
                  this.openGromorSignModal();
                }
              });
        },
      );

    this.props
      .fetchWithdrawals({
        reference: [
          {
            reference_id: current,
            reference_type: 'OWNER_ID',
          },
        ],
        skip: 0,
        count: 25,
        order_by: 'CREATED_AT',
        order_direction: 'desc',
        product_type: productType,
      })
      .finally(() => this.setState({ isLoading: false }));

    this.props
      .fetchRepayments({
        product_type: productType,
        credit_id: current,
        order_by_type: 'ORDER_BY_TYPE_DESC',
        order_by_field: 'ORDER_BY_FIELD_CREATED_AT',
        count: 1,
      })
      .catch(NOOP)
      .finally(() => this.setState({ isLoading: false }));

    this.props
      .fetchAccountProductConfig({
        merchant_id: current,
        product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
      })
      .catch(NOOP)
      .finally(() => this.setState({ isLoading: false }));
  }

  openGromorSignModal = () => {
    const {
      withdrawalConfiguration,
      merchantGromorEsignDetails: {
        data: { due_at, email_id = '', name = 'You', leegality_url = '' } = {},
      } = {},
    } = this.props;

    const handleModalClose = () => {
      this.setState({ isGromorModalOpen: false });
      this.props.closeModal();
    };

    this.props.openModal({
      component: (
        <GromorAgreementModal
          onClose={handleModalClose}
          withdrawalConfigurationDetails={withdrawalConfiguration.data}
          eSignUrl={leegality_url}
          name={name}
          email_id={email_id}
          due_at={due_at}
        />
      ),
      size: 'medium',
    });
  };

  renderBanner = (state) => {
    const unsignedText =
      'Kindly review and sign the new lender agreement by June 10th, to continue using your Cash Advance withdrawals';
    const signedText = 'Great job on signing the new lender agreement. ';

    const handleClose = () => {
      removeItem('loc_esign_clicked');
    };

    if (state === 'signed') {
      setTimeout(() => {
        handleClose();
      }, 2000);
    }

    return (
      <div className="cash-advance-gromor-esign-wrapper">
        <AnnouncementBanner
          theme="primary"
          className={state}
          onClose={() => handleClose()}
          card_id="cash-advance-banner"
        >
          <img
            src={state === 'signed' ? RoundTick : LegalSignIcon}
            alt="legal-sign"
            className={state === 'signed' ? 'round-tick-icon' : 'legal-sign-icon'}
          />
          <p>
            {state === 'signed' ? (
              signedText
            ) : state === 'unsigned' ? (
              <>
                <span>New lender agreement:</span> {unsignedText}
              </>
            ) : (
              ''
            )}
          </p>
          {state !== 'signed' && (
            <a
              className={`Button--secondary Button btn-border ${state}-btn`}
              onClick={this.openGromorSignModal}
              rel="noopener noreferrer"
            >
              {'View & Sign Agreement'}
              <i class="i i-arrow-forward" />
            </a>
          )}
        </AnnouncementBanner>
      </div>
    );
  };

  setSkip = ({ skip }, callback) => {
    this.setState(
      (prev) => ({
        ...prev,
        withdrawalSkip: skip,
      }),
      callback,
    );
  };

  getIsNWithdrawalsCompleted = (n) => {
    return this.props?.list?.data?.length === n;
  };

  getIsFirstRepaymentCompleted = () => {
    return this.props?.repaymentsList?.data?.length === 1;
  };

  getIsMerchantNew = () => {
    const liveByDate = this?.props?.productConfig?.data?.configuration?.live_by_date;
    if (liveByDate) {
      const diff = moment().diff(liveByDate, 'days');
      return diff <= 7;
    }
    return false;
  };

  getRepaymentFrequency = () => {
    return this.props?.withdrawalConfiguration?.data?.repayment_frequency;
  };

  isRepaymentFrequencyCustom = () => {
    return this.getRepaymentFrequency() === REPAYMENT_FREQUENCY_TYPES.CUSTOM;
  };

  showSettings = () => {
    return showSettings(this.props.user, this.getRepaymentFrequency());
  };

  isMerchantNew = () => {
    return isMerchantNew(this.props.productConfig?.data?.configuration?.live_by_date);
  };

  renderSection() {
    //Hotjar Events
    if (this.getIsMerchantNew() && this.getIsNWithdrawalsCompleted(0)) {
      triggerHotjarRecording(HOTJAR_TRIGGER.CASH_ADVANCE_LIVE);
    }
    if (this.getIsNWithdrawalsCompleted(1)) {
      triggerHotjarRecording(HOTJAR_TRIGGER.CASH_ADVANCE_FIRST_WITHDRAWAL);
    }
    if (this.getIsFirstRepaymentCompleted()) {
      triggerHotjarRecording(HOTJAR_TRIGGER.CASH_ADVANCE_FIRST_REPAYMENT);
    }

    const {
      match: {
        params: { section = CASH_ADVANCE_SECTIONS.OVERVIEW },
      },
    } = this.props;

    switch (section) {
      default:
      case CASH_ADVANCE_SECTIONS.OVERVIEW: {
        return <Overview />;
      }
      case CASH_ADVANCE_SECTIONS.WITHDRAWALS: {
        return <Withdrawals skip={this.state.withdrawalSkip} setSkip={this.setSkip} />;
      }
      case CASH_ADVANCE_SECTIONS.REPAYMENTS: {
        return <Repayments />;
      }
      case CASH_ADVANCE_SECTIONS.SETTINGS: {
        if (this.showSettings()) {
          return <Settings />;
        }
        return null;
      }
    }
  }

  render() {
    const {
      list: { data: withdrawalsData = {}, loading: withdrawalsLoading } = {},
      match: {
        params: { section },
      },
      withdrawalConfiguration: { loading: withdrawalConfigurationLoading, error: wcError },
      merchantGromorEsignDetails: { data: { due_at = '' } = {} } = {},
    } = this.props;

    if (wcError) {
      //TODO: render broken image here.
      return 'Error while loading WC.';
    }

    const { isLoading, isGromorModalOpen } = this.state;
    const isNonWithdrawalScreenAndEmptyData =
      !isLoading &&
      !withdrawalsData &&
      !!(
        section === CASH_ADVANCE_SECTIONS.OVERVIEW || section === CASH_ADVANCE_SECTIONS.REPAYMENTS
      );

    if (isNonWithdrawalScreenAndEmptyData)
      return (
        <Navigate to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.WITHDRAWALS}`} replace />
      );

    const showLoader = isLoading || withdrawalsLoading || withdrawalConfigurationLoading;

    const locEsignClicked = getItem('loc_esign_clicked');
    const isDateExpired = checkifDateExpired(new Date(due_at));
    const partnerId =
      this.props?.withdrawalConfiguration?.data?.configuration?.custom_partner_fields?.partner_id;
    const showSuccess = locEsignClicked && partnerId === 'GROMOR';
    const showBanner = (showSuccess || !isGromorModalOpen) && !isDateExpired;

    const hasDisbursedForADayWithdrawals = withdrawalsData?.find(
      (item) => item.status === 'DISBURSED' && isADayAgo(item?.disbursed_at),
    );

    return (
      <div className="cash-advance-container">
        {showBanner && this.renderBanner(showSuccess ? 'signed' : 'unsigned')}
        {isLenderLiquiloans(this.props.withdrawalConfiguration) ? (
          <LlDashboard />
        ) : (
          <tabbed-container>
            {hasDisbursedForADayWithdrawals ? (
              <BladeAlert
                contrast="low"
                title="Repayment for your last withdrawal is unavailable"
                description="Withdrawals and repayments are temporarily down. While we work on fixing this, please reach out to us at capital-support@razorpay.com to repay and clear any pending dues."
                intent="notice"
                isFullWidth
              />
            ) : null}
            <h1 className="cash-advance-title">Cash Advance</h1>
            {showLoader ? (
              <Loader />
            ) : (
              <>
                <header>
                  {withdrawalsData && (
                    <NavLink end to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.OVERVIEW}`}>
                      Overview
                    </NavLink>
                  )}
                  <NavLink end to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.WITHDRAWALS}`}>
                    Withdrawals
                  </NavLink>
                  {withdrawalsData && (
                    <NavLink end to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.REPAYMENTS}`}>
                      Repayments
                    </NavLink>
                  )}
                  {this.showSettings() && (
                    <NavLink end to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.SETTINGS}`}>
                      Settings
                    </NavLink>
                  )}
                </header>
                <content className="cash-advance-body">
                  {this.isMerchantNew() && (
                    <Alert.Error iconBefore="i-triangle-alert">
                      The withdrawals are temporarily unavailable due to technical downtime, we are
                      working to fix this and will reach out to you once this is resolved
                    </Alert.Error>
                  )}
                  {this.renderSection()}
                </content>
              </>
            )}
          </tabbed-container>
        )}
      </div>
    );
  }
}

CashAdvance.propTypes = {};

export default withEDIMigration(withRouter(CashAdvance));
