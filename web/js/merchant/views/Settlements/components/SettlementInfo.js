import { Link } from 'react-router-dom';
import { Fragment } from 'react';
import Time from 'common/ui/Time';
import { PaymentStatusLabel, SettlementStatusLabel } from 'merchant/components/StatusLabel';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import SettlementOverview from 'merchant/views/Transactions/Payments/components/SettlementOverview';
import { openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import SettlementDetail from '../Settlements/components/SettlementDetail';

@connect(
  (state) => {
    return {
      user: state.session.user,
      settlement_amount: state.home.settlement_amount,
    };
  },
  {
    openModal,
  },
)
export default class SettlementInfo extends React.Component {
  render() {
    const data = this.props.data;
    let status;
    if (data.transaction && data.transaction.settlement) {
      status = data.transaction.settlement.status;
    }
    if (data.transaction.on_hold) {
      status = 'on_hold';
    }

    let jsx;
    if (data.transaction.settlement) {
      jsx = (
        <div class="settlement-detail-toggle">
          <SettlementStatusLabel status={status} />
          {data.on_hold_until ? <a class="nav-link">Hold until {data.on_hold_until}</a> : null}

          {!data.transaction.on_hold ? (
            // false
            <Fragment>
              <br />
              <ContentToggler onToggleClick={this.props.viewSettlementOverview}>
                <span>
                  Settled on <Time value={data.transaction.settled_at} format="DD MMM YYYY" />
                </span>
                <SettlementOverview payment={data} />
              </ContentToggler>
            </Fragment>
          ) : //   <a
          //     class="nav-link"
          //     onClick={() => {
          //       this.props.openModal({
          //         size: 'medium',
          //         component: (
          //           <SettlementDetail user={this.props.user} settlementAmount={this.props.settlement_amount.data} />
          //         ),
          //       });
          //     }}
          //   >
          //     View Details
          // </a>
          null}
        </div>
      );
    } else {
      if (data.transaction.settled_at) {
        jsx = (
          <Fragment>
            {!(data.transaction && data.transaction.settlement) ? (
              <Fragment>
                <SettlementStatusLabel status={'scheduled'} /> <br />
              </Fragment>
            ) : null}
            <span class="link">
              To be settled on <Time value={data.transaction.settled_at} format="DD MMM YYYY" />
            </span>
          </Fragment>
        );
      } else {
        jsx = '--';
      }
    }

    return jsx;
  }
}
