import React from "react";
import Popover, { PopoverBody, PopoverTitle } from 'common/ui/Popover';

import { getCurrency } from 'common/ui/Amount';

export default class DynamicAmount extends React.Component {
  render() {
    const { field, currency, isDonationsTemplate, children } = this.props;

    const minAmount = `${getCurrency(currency).symbol} ${Number(
      field.min_amount || 0
    ).toFixed(2)}`;
    const maxAmount = field.max_amount
      ? `${getCurrency(currency).symbol} ${Number(field.max_amount).toFixed(2)}`
      : 'No Limit';

    return (
      <React.Fragment>
        {children}
        <Popover
          align="top"
          theme="dark"
          parentQuerySelector=".Modal-content--PaymentButton-CreateForm"
        >
          <PopoverBody>
            {isDonationsTemplate
              ? 'Supporters can fill custom amount'
              : 'Customers can fill custom amount'}
            <br />
            {/* TODO: As per the actual limits */}
            (Min: {minAmount}, Max: {maxAmount})
          </PopoverBody>
        </Popover>
      </React.Fragment>
    );
  }
}
