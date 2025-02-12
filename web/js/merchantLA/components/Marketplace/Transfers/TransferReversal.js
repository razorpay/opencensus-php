import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import LoaderDots from 'common/ui/LoaderDots';
import DataTable from 'common/ui/Table/DataTable';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import { reversalId, amount, createdAt } from 'merchantLA/utils/item/pair';
import { openModal } from 'merchant_common/reducers/modals';

import RefundToCustomerModal from './RefundToCustomerModal';
import { trackClickReverseDetails, trackClickReversalID, trackClickRefundToCustomer } from './ga';

/*
 * Design:
 * https://projects.invisionapp.com/share/5ED7Z2RSM#/screens/249521201
 *
 * Inputs:
 * @param {Object} transfer
 * @param {Object} reversal
 *
 * Descrition:
 * Given `payment` and `reversal` parameters exactly the same as their
 * corresponding apis, this component will display
 * reversal amounts, status and actions according to the design
 */

const createdAtWithStyle = { columnClass: 'text-right', ...createdAt };

const NumReversals = ({ reversals, titleCase = false }) => {
  if (reversals.loading) {
    return <LoaderDots />;
  }

  const numReversals = reversals.items.length;
  const reversalSuffix = numReversals === 0 || numReversals > 1 ? 's' : '';

  return (
    <span>
      {numReversals} {titleCase ? 'R' : 'r'}eversal{reversalSuffix}
    </span>
  );
};

class ReversalsList extends React.Component {
  onToggleClick = (_) => {
    setTimeout(() => {
      const isOpen = document.querySelector('.reversals-list.full-width-item.sub-entity-list');
      trackClickReverseDetails(`${this.props.reversalStatus} | ${isOpen ? 'Open' : 'Close'}`);
    });
  };

  onClickReversalId = (_) => {
    trackClickReversalID(this.props.reversalStatus);
  };

  render() {
    const { transfer, reversals } = this.props;
    const reversalHeading = {
      title: 'Reversal Details',
      subTitle: <NumReversals reversals={reversals} titleCase={true} />,
    };

    const transferReversalId = {
      ...reversalId,
      value: (item) => (
        <Link to={`/transfers/${transfer.id}/${item.id}`} onClick={this.onClickReversalId}>
          <code>{item.id}</code>
        </Link>
      ),
    };

    return (
      <ContentToggler>
        <span onClick={this.onToggleClick}>Reversal Details</span>
        <div className="reversals-list full-width-item sub-entity-list">
          <DataTable
            title="Reversals"
            customClass="reversals-table"
            progressLoader={true}
            columns={[transferReversalId, amount, createdAtWithStyle]}
            items={reversals.items}
            loading={reversals.loading}
            showHeaders={false}
            noStripe={true}
            panelHeading={reversalHeading}
          />
        </div>
      </ContentToggler>
    );
  }
}

class TransferReversal extends React.PureComponent {
  openRefundToCustomerModal = (_) => {
    this.props.openModal({
      size: 'small',
      component: <RefundToCustomerModal transfer={this.props.transfer} />,
    });

    trackClickRefundToCustomer();
  };

  renderRefundToCustomerButton = () =>
    this.props.showRefundToCustomer && (
      <Button onClick={this.openRefundToCustomerModal} className="btn btn-default m-t">
        Refund to Customer
      </Button>
    );

  render() {
    const { transfer, reversals } = this.props;
    const reversedAmount = transfer.amount_reversed;
    const reversalStatus =
      reversedAmount === 0 ? null : transfer.amount === reversedAmount ? 'full' : 'partial';

    if (!reversalStatus) {
      return (
        <div>
          <p>No reversals created</p>
          {this.renderRefundToCustomerButton()}
        </div>
      );
    } else if (reversalStatus === 'full') {
      return (
        <div>
          <Definition>
            Fully Reversed
            <div>
              in <NumReversals reversals={reversals} />
            </div>
          </Definition>
          <ReversalsList
            reversalStatus={reversalStatus}
            transfer={transfer}
            reversals={reversals}
          />
        </div>
      );
    }

    return (
      <div>
        <div className="m-b">
          <Definition>
            <span>
              <Amount value={reversedAmount} currency={transfer.currency} /> Reversed
            </span>
            <span>
              Partially Reversed in <NumReversals reversals={reversals} />
            </span>
            {this.renderRefundToCustomerButton()}
          </Definition>
        </div>
        <p />
        <ReversalsList reversalStatus={reversalStatus} transfer={transfer} reversals={reversals} />
      </div>
    );
  }
}

export default connect((_) => ({}), { openModal })(TransferReversal);
