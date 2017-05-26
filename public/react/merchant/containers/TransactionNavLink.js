/*
 * This component will take care of opening the transaction details view
 * in the slider modal
 *
 * USAGE:
 *    Follows the same API signature of `NavLink` component.
 *
 *  ```jsx
 *    <TransactionNavLink to={`/app/payments/${payment.id}`}>
 *      {payment.id}
 *    </TransactionNavLink />
 *  ````
 */

import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { openSlider } from 'rzp/modules/slider';

import RefundDetails from 'merchant/containers/Refunds/Details';
import PaymentDetails from 'merchant/containers/Payments/Details';
import OrderDetails from 'merchant/containers/Orders/Details';
import PaymentLinkDetails from 'merchant/containers/PaymentLinks/Details';
import SettlementDetails from 'merchant/containers/Settlements/Details';

const TRANSACTION_COMPONENTS = {
  rfnd: RefundDetails,
  pay: PaymentDetails,
  order: OrderDetails,
  inv: PaymentLinkDetails,
  setl: SettlementDetails,
};

@connect(null, { openSlider })
export default class TransactionNavLink extends Component {
  handleClick = () => {
    let { to } = this.props;
    let id = to.split('/').slice(-1)[0];
    let type = id.split('_')[0];
    let TxnDetailsComponent = TRANSACTION_COMPONENTS[type];

    this.props.openSlider({
      component: <TxnDetailsComponent id={id} />,
      onOpenURL: to,
    });
  };

  render() {
    let {
      onClick,
      component,
      openSlider,
      children,
      ...otherProps
    } = this.props;
    return (
      <NavLink
        class="NavLink__transaction"
        {...otherProps}
        onClick={event => {
          if (!event.metaKey) {
            event.preventDefault();
            this.handleClick();
          }
        }}
      >
        {children}
      </NavLink>
    );
  }
}

TransactionNavLink.defaultProps = {
  onClick: () => {},
};
