import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import moment from 'moment';

import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Amount from 'common/ui/Amount';
import DataTable from 'common/ui/Table/DataTable';
import Button from 'common/new-ui/Button';
import Withdrawals from 'merchant/models/Capital/Withdrawals';
import Repayments from 'merchant/models/Capital/Repayments';
import { fetchFunctionalWithdrawalConfigByMerchantID } from 'merchant/reducers/capital/withdrawals';
import {
  STATUS_LABELS,
  StatusPillClasses,
  REPAYMENT_CREATION_SOURCES,
  PAYMENT_MODES,
  COLLECTIONS_PAYMENT_REFERENCE_TYPE,
} from '../constants';
import { computePrincipalAndInterest } from '../utils';

@connect(
  (state) => {
    const {
      withdrawals: {
        withdrawalConfiguration: { loading, data = {}, errors = null },
      },
    } = state;
    return {
      user: state.session.user,
      loading,
      withdrawalConfigurationDetails: data,
      withdrawalConfigurationFetchError: errors,
    };
  },
  {
    fetchFunctionalWithdrawalConfigByMerchantID,
  },
)
class RepaymentDetails extends Component {
  state = {
    repayment: {},
    repaidWithdrawals: {},
    repaidById: {},
    isLoading: true,
    isError: false,
  };

  gaEventDispatcher = (eventObject) => {
    eventObject.eventCategory = 'Dashboard CA - Withdraw';
    window.rzpAnalytics?.(eventObject);
  };

  componentDidMount() {
    const { id } = this.props;

    this.fetchRepaymentDetails(id);
  }

  fetchRepaymentDetails = async (id) => {
    this.setState({ isLoading: true });

    const repayments = new Repayments();
    const withdrawals = new Withdrawals();
    const repaidById = {};
    const {
      user,
      withdrawalConfigurationDetails,
      // eslint-disable-next-line no-shadow
      fetchFunctionalWithdrawalConfigByMerchantID,
    } = this.props;

    try {
      const { data: repayment } = await repayments.fetchRepayment(id);

      if (!repayment || !repayment.id) {
        throw new Error('No repayment found');
      }

      const { breakups } = repayment;
      const withdrawalsIds = new Set();

      breakups.forEach(({ product_entity_type, product_entity_reference_id, breakup_amount }) => {
        if (product_entity_type === 'PRODUCT_ENTITY_TYPE_WITHDRAWAL') {
          if (repaidById[product_entity_reference_id]) {
            repaidById[product_entity_reference_id] += Number(breakup_amount);
          } else repaidById[product_entity_reference_id] = Number(breakup_amount);

          withdrawalsIds.add(product_entity_reference_id);
        }
      });

      // eslint-disable-next-line no-shadow
      const withdrawalsDetailsRequest = Array.from(withdrawalsIds).map((id) => {
        return withdrawals.fetchWithdrawalDetails({
          reference_type: 'ID',
          reference_id: id,
        });
      });

      if (!withdrawalConfigurationDetails) {
        await fetchFunctionalWithdrawalConfigByMerchantID({
          owner_id: user.current,
          owner_type: 'RZP_MERCHANT',
        });
      }

      const withdrawalResponse = await Promise.all(withdrawalsDetailsRequest);
      const repaidWithdrawals = withdrawalResponse.map(({ data = {} }) => data.withdrawal);

      this.setState({
        repayment,
        repaidWithdrawals,
        repaidById,
        isError: false,
        isLoading: false,
      });
    } catch (err) {
      this.setState({ isError: true, isLoading: false });
    }
  };

  componentDidUpdate(prevProps) {
    if (this.props.id !== prevProps.id) {
      this.fetchRepaymentDetails(this.props.id);
    }
  }

  getRepaymentBreakup = () => {
    const {
      repayment: { amount, breakups = [] },
    } = this.state;
    const { BALANCE_TYPE_PRINCIPAL = 0, BALANCE_TYPE_INTEREST = 0 } = computePrincipalAndInterest(
      breakups,
    );
    const {
      withdrawalConfigurationDetails: { configuration: { interest = 0 } = {} },
    } = this.props;

    const data = [
      {
        label: 'Total Repaid Amount',
        value: <Amount value={amount} />,
      },
      {
        label: 'Principal Repaid',
        value: <Amount value={BALANCE_TYPE_PRINCIPAL} />,
      },
      {
        label: 'Interest Repaid',
        value: (
          <div class="flex">
            <Amount value={BALANCE_TYPE_INTEREST} />
            {interest ? (
              <small class="text-faded">
                &nbsp;|&nbsp;{`Interest Rate ${Number(interest) / 100}%`}
              </small>
            ) : null}
          </div>
        ),
      },
    ];

    return (
      <React.Fragment>
        <div className="block-note purple m-b">
          <strong>Repayment Breakup</strong>
        </div>
        {data.map(({ label, value }) => (
          <EntityDetailRow key={label} label={label}>
            {value}
          </EntityDetailRow>
        ))}
      </React.Fragment>
    );
  };

  getRepaymentVia = () => {
    const {
      repayment: { payment_meta = null, payment_mode, payment_reference_type },
    } = this.state;

    if (payment_meta && payment_mode === PAYMENT_MODES.MANUAL) {
      return (
        <Fragment>
          <span style={{ textTransform: 'capitalize' }}>{payment_meta.method}</span> |{' '}
          {payment_meta.vpa ? (
            payment_meta.vpa
          ) : (
            <span style={{ textTransform: 'capitalize' }}>{payment_meta.bank}</span>
          )}
        </Fragment>
      );
    } else if (
      payment_mode === PAYMENT_MODES.AUTO_COLLECTION ||
      payment_reference_type === COLLECTIONS_PAYMENT_REFERENCE_TYPE.CREDIT_REPAYMENT
    ) {
      return 'Deducted from your settlement Balance';
    }

    return '--';
  };

  getRepaymentBy = () => {
    const {
      repayment: { payment_mode, creation_source_id, creation_source_type },
    } = this.state;
    const {
      user: { current, name, merchants },
    } = this.props;

    if (payment_mode === PAYMENT_MODES.AUTO_COLLECTION) return 'Razorpay (Automated)';
    else if (payment_mode === PAYMENT_MODES.MANUAL) {
      if (creation_source_type !== REPAYMENT_CREATION_SOURCES.USER) return '--';
      else if (creation_source_id === current) return name;

      const merchantKeys = Object.keys(merchants);
      const merchant = merchantKeys.find((merchantKey) => {
        // eslint-disable-next-line no-shadow
        const current = merchants[merchantKey] || {};
        return current.id === creation_source_id;
      });
      if (merchant) return merchant.name;
    }

    return '--';
  };

  getRepaymentMeta = () => {
    const {
      repayment: { status, id },
    } = this.state;

    const data = [
      {
        label: 'Repayment Status',
        value: (
          <span class={`status-label label ${StatusPillClasses[status]}`}>
            {STATUS_LABELS[status]}
          </span>
        ),
      },
      {
        label: 'Repayment ID',
        value: <span>{id}</span>,
      },
      {
        label: 'Repaid Via',
        value: <span>{this.getRepaymentVia()}</span>,
      },
      {
        label: 'Repaid By',
        value: <span style={{ textTransform: 'capitalize' }}>{this.getRepaymentBy()}</span>,
      },
    ];

    return (
      <React.Fragment>
        <div className="block-note warning m-b">
          <strong>Other Repayment Details</strong>
        </div>
        {data.map(({ label, value }) => (
          <EntityDetailRow key={label} label={label}>
            {value}
          </EntityDetailRow>
        ))}
      </React.Fragment>
    );
  };

  viewNextRepayment = (nextRepaymentId) => {
    // TODO: fix url
    this.props.history.push(`/${nextRepaymentId}`);
  };

  getNextRepayment = () => {
    // TODO: apply date filter to listOrSearch endpoint. the start date will be
    //  beggining of next day
    return {
      id: 12,
    };
  };

  getRepaidWithdrawalsTable = () => {
    const { repaidWithdrawals, repaidById } = this.state;
    const withdrawalId = {
      title: 'Withdrawal ID',
      value: (withdrawal) => (
        <Link to={`/capital/cash-advance/withdrawals/${withdrawal.id}`}>{withdrawal.id}</Link>
      ),
    };
    const withdrawalAmount = {
      title: 'Withdrawal',
      value: (withdrawal) => <Amount value={Number(withdrawal.amount)} />,
    };
    const repaidAgainst = {
      title: 'Repaid Against',
      value: (withdrawal) => <Amount value={repaidById[withdrawal.id]} />,
    };
    const currentStatus = {
      title: 'Current Status',
      value: ({ status }) => (
        <span class={`status-label label ${StatusPillClasses[status]}`}>
          {STATUS_LABELS[status]}
        </span>
      ),
    };

    return (
      <React.Fragment>
        <div className="block-note success m-b">
          <strong>Repaid Withdrawals</strong>
        </div>
        <div>
          <DataTable
            columns={[withdrawalId, withdrawalAmount, repaidAgainst, currentStatus]}
            title="Repaid Withdrawals"
            items={repaidWithdrawals}
            loading={!repaidWithdrawals}
            showHeaders={true}
          />
        </div>
      </React.Fragment>
    );
  };

  getPayFailedRepaymentPrompt = () => {
    return (
      <div>
        <strong>Next Automatic Repayment</strong>
        <div class="flex">
          <div class="left-section">
            <Amount value={1292000} />
            <div class="block-note purple">
              Scheduled for {moment().format('MMMM Do YYYY, h:mm a')}
            </div>
          </div>
        </div>
        <Button.Primary>Repay now</Button.Primary>
      </div>
    );
  };

  renderContent() {
    const { id } = this.props;
    const { repaidWithdrawals } = this.state;
    // const nextRepayment = this.getNextRepayment();

    return (
      <div className="panel panel-default SliderPanel">
        <div className="panel-heading">
          <div className="settlement-actions-wrapper p-r-32">
            <span className="flex-occupy no-margin">
              <strong>{id}</strong>
            </span>
            {/* {nextRepayment && (
            <button
              className="btn btn-outline"
              onClick={() => this.viewNextRepayment(nextRepayment.id)}
            >
              View Next
            </button>
          )} */}
          </div>
        </div>
        <div className="SliderPanel__Body">
          <div className="">
            <div className="list-group details-row-container">
              <div className="m-all p-all">
                {this.getRepaymentBreakup()}
                <hr />
                {this.getRepaymentMeta()}
                {repaidWithdrawals.length > 0 && (
                  <React.Fragment>
                    <hr />
                    {this.getRepaidWithdrawalsTable()}
                  </React.Fragment>
                )}
              </div>
            </div>
          </div>
        </div>
        {/* {nextRepayment.status === STATUSES.FAILED &&
        this.getPayFailedRepaymentPrompt(nextRepayment)} */}
      </div>
    );
  }

  render() {
    const { isLoading, isError, withdrawalConfigurationFetchError } = this.state;

    return (
      <div className="content-wrapper content-sm txn-details CA--entity-details repayment-details-modal">
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : isError || withdrawalConfigurationFetchError ? (
          <div
            className="flex"
            style={{
              justifyContent: 'center',
              alignItems: 'center',
              fontSize: '20px',
              marginTop: '150px',
            }}
          >
            <i className="i i-info-circle text-danger" style={{ marginRight: '4px' }} />
            <p>Oh snap! Something went wrong.</p>
          </div>
        ) : (
          this.renderContent()
        )}
      </div>
    );
  }
}

export default RepaymentDetails;
