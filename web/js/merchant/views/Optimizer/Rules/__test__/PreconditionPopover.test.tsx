import React from 'react';
import { render } from 'test-utils';

import { RULE_GROUP } from 'merchant/views/Optimizer/__test__/mock';

import { PreconditionPopover } from '../PreconditionPopover';

describe('PreconditionPopover', () => {
  const renderApp = () => render(<PreconditionPopover ruleGroup={RULE_GROUP} />);

  it('should render PreconditionPopover without any errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  it('should render PreconditionPopover with correct content', () => {
    const { getByText, getAllByText } = renderApp();

    expect(getByText('Payment Method is One Of upi_intent,upi_collect')).toBeInTheDocument();
    expect(getAllByText('AND')).toHaveLength(2);
    expect(getByText('Custom Identifier 1 is Equal to GIFT')).toBeInTheDocument();
    expect(getByText('Amount is Greater Than 300')).toBeInTheDocument();
  });
});
