import { Component } from 'react';
import RepaymentCard from './RepaymentCard';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import { getURLQueryParams } from 'common/utils/rzp-utils';
import {
  fetchFunctionalWithdrawalConfigByMerchantID,
  fetchInstallments,
} from 'merchant/reducers/capital/withdrawals';
import {
  CASH_ADVANCE_SECTIONS,
  SCHEDULED_REPAYMENT_LINKS,
} from 'merchant/views/Capital/CashAdvance/constants';
import ScheduledRepaymentsList from './ScheduledRepaymentList';
import ListFilter from 'merchant/components/ListFilter';
import moment from 'moment';
import { getProductType } from 'merchant/views/Capital/utils';
import withEDIMigration from 'merchant/views/Capital/CashAdvance/withEDIMigration';

@connect(
  (state) => ({
    user: state.session.user,
    installments: state.withdrawals.installments,
    currentOutstanding: state.withdrawals.current_outstanding,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
  }),
  {
    fetchFunctionalWithdrawalConfigByMerchantID,
    fetchInstallments,
  },
)
class RepaymentsSchedule extends Component {
  componentDidMount() {
    this.fetch();
  }

  fetch = async () => {
    await this.fetchWC();
    this.fetchInstallments();
  };

  getCurrentOutstanding = () => {
    const { fetchCurrentOutstanding, user } = this.props;
    fetchCurrentOutstanding({
      product_type: getProductType(user),
      owner_id: user.current,
      from: moment().startOf('day').unix(),
      to: moment().add(30, 'days').unix(),
    });
  };

  fetchInstallments = () => {
    this.props.fetchInstallments({
      owner_id: this.props.user.current,
      product_type: getProductType(this.props.user),
      ...this.getInstallmentsRange(),
    });
  };

  fetchWC = () => {
    const {
      user: { current },
      fetchFunctionalWithdrawalConfigByMerchantID: fetchWCByMerchantID,
    } = this.props;
    return fetchWCByMerchantID({
      owner_type: 'RZP_MERCHANT',
      owner_id: current,
      status: 'ACTIVE',
      skip: 0,
    });
  };

  getInstallmentsRange = (period) => {
    let from, to;
    switch (period) {
      case 'today':
        from = moment().startOf('day').unix();
        to = moment().endOf('day').unix();
        break;

      case 'last-3-days':
        from = moment().subtract(2, 'day').startOf('day').unix();
        to = moment().endOf('day').unix();
        break;

      case 'upcoming-7-days':
        from = moment().unix();
        to = moment().add(6, 'day').endOf('day').unix();
        break;

      case 'upcoming-repayments':
        from = moment().unix();
        to = moment().add(30, 'day').endOf('day').unix();
        break;

      default:
        from = moment().subtract(2, 'day').startOf('day').unix();
        to = moment().add(30, 'day').endOf('day').unix();
    }

    return { from, to };
  };

  render() {
    const periods = [
      { label: 'All', value: '' },
      { label: 'Today', value: 'today' },
      { label: 'Last 3 days', value: 'last-3-days' },
      { label: 'Upcoming 7 days', value: 'upcoming-7-days' },
      { label: 'Upcoming Repayments', value: 'upcoming-repayments' },
    ];

    const { installments, currentOutstanding } = this.props;
    const { loading: isFetchingRepayments } = installments;
    let { data: repayments = [] } = installments;

    if (!isFetchingRepayments) {
      const period = this.getInstallmentsRange(getURLQueryParams(location.search).period);
      repayments = repayments.filter((i) => {
        const date = Number(i.repayment_date);
        return date >= period.from && date <= period.to;
      });
    }

    return (
      <div className="cash-advance-repayments-schedule">
        <div className="top-nav">
          <div class="left-section">
            <Link to={`/capital/cash-advance/${CASH_ADVANCE_SECTIONS.OVERVIEW}`}>
              <i className="i i-arrow-back" />
              &nbsp;
              <strong>Overview</strong>
            </Link>
            <i className="i i-chevron-right" /> Repayments Schedule
          </div>
          <div class="right-section">
            Go to
            <div class="links-wrapper">
              {SCHEDULED_REPAYMENT_LINKS.map(({ text, to }) => (
                <Link to={to} key={to}>
                  <strong>{text}</strong>
                </Link>
              ))}
            </div>
          </div>
        </div>
        <div class="repayments-schedule-repay-card flex">
          <RepaymentCard installments={installments} currentOutstanding={currentOutstanding} />
        </div>
        <tabbed-container class="no-padding">
          <content>
            <div className="content-wrapper cash-advance-repayments">
              <div className="cash-advance-body">
                <div className="repayments-schedule-filters-wrapper filters-wrapper flex">
                  <ListFilter form="ScheduledRepaymentsList" onSubmit={this.fetchInstallments}>
                    <div className="form-group list-filter-item">
                      <label>Select Period</label>
                      <Field name="period" component="select" class="form-control input-sm">
                        {periods.map(({ value, label }, idx) => (
                          <option value={value} key={idx}>
                            {label}
                          </option>
                        ))}
                      </Field>
                    </div>
                  </ListFilter>
                  <div class="float-right-note">
                    <div className="block-note right-border text-right">
                      <i>
                        The upcoming repayments indicate the amounts that
                        <br />
                        we will try to collect from the<strong>&nbsp;settlement balance.</strong>
                      </i>
                    </div>
                  </div>
                </div>
                <ScheduledRepaymentsList repayments={repayments} loading={isFetchingRepayments} />
              </div>
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

export default withEDIMigration(RepaymentsSchedule);
