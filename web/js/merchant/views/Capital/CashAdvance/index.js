import React, { Component } from 'react';
import { Navigate } from 'react-router-dom';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import Onboarding from './onboarding';
import axios from 'axios';
import {
  fetchWithdrawalConfiguration,
  fetchWithdrawals,
  fetchFunctionalWithdrawalConfigByMerchantID,
} from 'merchant/reducers/capital/withdrawals';
import { CASH_ADVANCE_BASE_URL, CASH_ADVANCE_SECTIONS, LINE_OF_CREDIT_BASE_URL } from './constants';
import {
  getProductType,
  canViewCashAdvanceProduct,
  isCashAdvanceProductActive,
} from 'merchant/views/Capital/utils';
import { withRouter } from 'common/deprecated/withRouter';
@connect(
  (state) => ({
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    list: state.withdrawals.list,
  }),
  {
    openModal,
    fetchWithdrawals,
    fetchWithdrawalConfiguration,
    fetchFunctionalWithdrawalConfigByMerchantID,
  },
)
class WithdrawalsRoot extends Component {
  state = {
    // eslint-disable-next-line react/no-unused-state
    leadGenerated: false,
  };

  productType = getProductType(this.props.user);

  gaEventDispatcher = (eventObject) => {
    const { state: { eventCategory = null } = {} } = this.props.location;
    // eslint-disable-next-line dot-notation
    eventObject['eventCategory'] = eventCategory ? eventCategory : 'Dashboard CA - Apply';
    window.rzpAnalytics?.(eventObject);
  };

  componentDidMount() {
    const { user } = this.props;

    if (isCashAdvanceProductActive(user)) {
      this.fetchWithdrawalConfiguration();
      this.props.fetchWithdrawals({
        product_type: this.productType,
        order_by: 'CREATED_AT',
        order_direction: 'desc',
        reference: [
          {
            reference_id: this.props.user.current,
            reference_type: 'OWNER_ID',
          },
        ],
        limit: 20,
      });
    }

    this.gaEventDispatcher({
      eventAction: 'Flash Credit Tab',
      eventLabel: `${this.props.user.current} | loc_stage_1`,
    });
  }

  fetchWithdrawalConfiguration = () => {
    const { fetchFunctionalWithdrawalConfigByMerchantID, user } = this.props;

    fetchFunctionalWithdrawalConfigByMerchantID({
      owner_id: user.current,
      owner_type: 'RZP_MERCHANT',
      product_type: this.productType,
    });
  };

  static createCapitalFDTicket = (content, user) => {
    const TSYS_AUTH_TOKEN = '4d482bcf908b56771a86db388bae8ee7639b0f81';
    const apiUrl = 'https://support-tsa.razorpay.com/api/fd/ticket/create';

    return axios({
      method: 'post',
      baseURL: apiUrl,
      headers: {
        sendImmediately: true,
        Authorization: `Basic ${TSYS_AUTH_TOKEN}`,
        'Content-Type': 'application/json',
      },
      data: {
        priority: 3,
        email: user.email,
        phone: user.contact_mobile,
        subject: `Merchant[${user.current}] - Line Of Credit`,
        description: content,
        env: 'capital',
        custom_fields: {
          cf_requester_category: 'Merchant',
          cf_requestor_subcategory: 'Other',
          cf_merchant_id: user.current,
        },
      },
    }).then((res) => {
      return res;
    });
  };

  onRaiseRequest = () => {
    this.setState({
      // eslint-disable-next-line react/no-unused-state
      leadGenerated: true,
    });
  };

  render() {
    const {
      user,
      withdrawalConfigurationDetails: { loading: configLoading, error: wcError },
      list,
    } = this.props;

    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const hasLOCStage2Feature = user.isCashAdvanceStage2Enabled;
    const isCashAdvanceEligible = canViewCashAdvanceProduct(user);
    const hasWC = !!withdrawalConfigurationDetails;

    if (list.loading || configLoading)
      return (
        <div className="spinner center">
          <div className="double-bounce1" />
          <div className="double-bounce2" />
        </div>
      );

    if (isCashAdvanceProductActive(user)) {
      if (wcError) {
        return 'Error while loading WC.';
      }
      return <Navigate to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.OVERVIEW}`} replace />; // nosemgrep : https://semgrep.dev/s/w48P
    }
    if (isCashAdvanceEligible && hasLOCStage2Feature) {
      // Edge Case: update the URL to capital/cash-advance incase cash advance merchant directly visited line-of-credit. rare scenario so not handling it to avoid complexity
      return (
        <Onboarding
          leadGenerated={true}
          hasLOCStage2Feature={true}
          hasWithdrawalConfiguration={hasWC}
          createFDTicket={WithdrawalsRoot.createCapitalFDTicket}
          onRaiseRequest={this.onRaiseRequest}
          withdrawalConfiguration={withdrawalConfigurationDetails}
          trackGA={this.gaEventDispatcher}
        />
      );
    }
    if (isCashAdvanceEligible) {
      return <Navigate to={`${CASH_ADVANCE_BASE_URL}apply`} replace />; // nosemgrep : https://semgrep.dev/s/w48P
    }
    return <Navigate to={`${LINE_OF_CREDIT_BASE_URL}apply`} replace />;
  }
}

export default withRouter(WithdrawalsRoot);
