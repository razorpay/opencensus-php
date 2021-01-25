import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { NavLink, withRouter, Redirect } from 'react-router-dom';

import { CASH_ADVANCE_BASE_URL, CASH_ADVANCE_SECTIONS } from './constants';
import Withdrawals from './withdrawals';
import Overview from './Overview';
import Repayments from './Repayments/Repayments';
import {
  fetchSeedData,
  fetchWithdrawalConfigurationByMerchantID,
  fetchWithdrawals,
} from 'merchant/reducers/capital/withdrawals';
import LoaderDots from 'common/ui/LoaderDots';

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
    } = state;

    return {
      user,
      withdrawalConfiguration,
      list,
      seedData,
    };
  },
  {
    fetchWithdrawalConfigurationByMerchantID,
    fetchSeedData,
    fetchWithdrawals,
  },
)
class CashAdvance extends React.Component {
  constructor(props) {
    super(props);

    const { list: { data = null } = {} } = props;

    this.state = {
      isLoading: !data,
    };
  }

  componentDidMount() {
    const {
      user: { current },
      fetchWithdrawals,
      fetchWithdrawalConfigurationByMerchantID,
    } = this.props;

    fetchWithdrawalConfigurationByMerchantID({
      owner_type: 'RZP_MERCHANT',
      owner_id: current,
      status: 'ACTIVE',
      skip: 0,
    });

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
    } = this.props;

    if (wcError) {
      //TODO: render broken image here.
      return 'Error while loading WC.';
    }

    const { isLoading } = this.state;
    const isNonWithdrawalScreenAndEmptyData =
      !isLoading &&
      !withdrawalsData &&
      !!(
        section === CASH_ADVANCE_SECTIONS.OVERVIEW || section === CASH_ADVANCE_SECTIONS.REPAYMENTS
      );

    if (isNonWithdrawalScreenAndEmptyData)
      return <Redirect to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.WITHDRAWALS}`} />;

    const showLoader = isLoading || withdrawalsLoading || withdrawalConfigurationLoading;

    return (
      <div className="cash-advance-container">
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
