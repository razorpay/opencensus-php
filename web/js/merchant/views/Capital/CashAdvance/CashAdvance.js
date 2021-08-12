import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { NavLink, withRouter, Redirect } from 'react-router-dom';
import { CASH_ADVANCE_BASE_URL, CASH_ADVANCE_SECTIONS, NOOP } from './constants';
import Withdrawals from './withdrawals';
import Overview from './Overview';
import Repayments from './Repayments/Repayments';
import {
  fetchSeedData,
  fetchFunctionalWithdrawalConfigByMerchantID,
  fetchWithdrawals,
} from 'merchant/reducers/capital/withdrawals';
import { fetchMerchantDetails } from 'merchant/reducers/capital/migrations';
import LoaderDots from 'common/ui/LoaderDots';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import GromorAgreementModal from 'merchant/views/Capital/components/Modals/GromorAgreementModal';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import LegalSignIcon from '../../../../../icons/merchant/legal.svg';
import RoundTick from '../../../../../icons/merchant/tick-round.svg';
import LocalStorageService from 'common/utils/localStorage';
import { checkifDateExpired } from 'merchant/views/Capital/utils';

const Loader = () => {
  return (
    <div className="custom-loader">
      <LoaderDots />
    </div>
  );
};

@withRouter
@connect(
  (state) => {
    const {
      session: { user },
      withdrawals: { withdrawalConfiguration, list, seedData },
      migrations: { merchantGromorEsignDetails },
    } = state;

    return {
      user,
      withdrawalConfiguration,
      list,
      seedData,
      merchantGromorEsignDetails,
    };
  },
  {
    fetchFunctionalWithdrawalConfigByMerchantID,
    fetchSeedData,
    fetchWithdrawals,
    fetchMerchantDetails,
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
    };
  }

  componentDidMount() {
    const {
      user: { current },
      fetchWithdrawals,
      fetchFunctionalWithdrawalConfigByMerchantID,
      fetchMerchantDetails,
    } = this.props;

    fetchFunctionalWithdrawalConfigByMerchantID({
      owner_id: current,
      owner_type: 'RZP_MERCHANT',
    }).then(
      ({
        data: {
          withdrawal_config: {
            configuration: { custom_partner_fields: { partner_id = '' } = {} } = {},
          } = {},
        } = {},
      }) => {
        const locEsignEnabled = this.props.user.isFeatureEnabled('loc_esign');

        if (locEsignEnabled && partner_id !== 'GROMOR')
          fetchMerchantDetails({
            owner_id: current,
          }).then(({ data: { due_at } = {} } = {}) => {
            const isDateExpired = checkifDateExpired(new Date(due_at));
            if (!isDateExpired) {
              this.setState({ isGromorModalOpen: true });
              this.openGromorSignModal();
            }
          });
      },
    );

    fetchWithdrawals({
      reference: [
        {
          reference_id: current,
          reference_type: 'OWNER_ID',
        },
      ],
      skip: 0,
      count: 20,
      order_by: 'CREATED_AT',
      order_direction: 'desc',
    }).finally(() => this.setState({ isLoading: false }));
  }

  openGromorSignModal = () => {
    const {
      closeModal,
      openModal,
      withdrawalConfiguration,
      merchantGromorEsignDetails: {
        data: { due_at, email_id = '', name = 'You', leegality_url = '' } = {},
      } = {},
    } = this.props;

    const handleModalClose = () => {
      this.setState({ isGromorModalOpen: false });
      closeModal();
    };

    openModal({
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

    if (state === 'signed') {
      setTimeout(() => {
        handleClose();
      }, 2000);
    }

    const handleClose = () => {
      LocalStorageService.removeItem('loc_esign_clicked');
    };

    return (
      <div className="cash-advance-gromor-esign-wrapper">
        <AnnouncementBanner theme="primary" className={state} onClose={() => handleClose()} card_id="cash-advance-banner">
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

  renderSection() {
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
        return <Withdrawals />;
      }
      case CASH_ADVANCE_SECTIONS.REPAYMENTS: {
        return <Repayments />;
      }
    }
  }

  render() {
    const {
      list: { data: withdrawalsData = null, loading: withdrawalsLoading } = {},
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
      return <Redirect to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.WITHDRAWALS}`} />;

    const showLoader = isLoading || withdrawalsLoading || withdrawalConfigurationLoading;

    const locEsignClicked = LocalStorageService.getItem('loc_esign_clicked');
    const isDateExpired = checkifDateExpired(new Date(due_at));
    const partner_id =
      this.props.withdrawalConfiguration && this.props.withdrawalConfiguration.data
        ? this.props.withdrawalConfiguration.data.configuration.custom_partner_fields.partner_id
        : '';
    const showSuccess = locEsignClicked && partner_id === 'GROMOR';
    const showBanner = (showSuccess || !isGromorModalOpen) && !isDateExpired;

    return (
      <div className="cash-advance-container">
        {showBanner && this.renderBanner(showSuccess ? 'signed' : 'unsigned')}

        <tabbed-container>
          <h1 className="cash-advance-title">Cash Advance</h1>
          <header>
            {withdrawalsData && (
              <NavLink exact to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.OVERVIEW}`}>
                Overview
              </NavLink>
            )}
            <NavLink exact to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.WITHDRAWALS}`}>
              Withdrawals
            </NavLink>
            {withdrawalsData && (
              <NavLink exact to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.REPAYMENTS}`}>
                Repayments
              </NavLink>
            )}
          </header>
          {showLoader ? (
            <Loader />
          ) : (
            <content className="cash-advance-body">{this.renderSection()}</content>
          )}
        </tabbed-container>
      </div>
    );
  }
}

CashAdvance.propTypes = {};

export default CashAdvance;
