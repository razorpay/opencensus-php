import React from 'react';
import { render } from 'test-utils';

import { RULE_GROUP } from 'merchant/views/Optimizer/__test__/mock';

import { RulesTable } from '../RulesTable';

describe('RulesTable', () => {
  const renderApp = () => render(<RulesTable ruleGroups={[RULE_GROUP]} />);

  it('should render RulesTable without any errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  it('should render RulesTable with correct content', () => {
    const { getByText, getByRole } = renderApp();

    expect(getByRole('columnheader', { name: 'Priority' })).toBeInTheDocument();
    expect(getByRole('columnheader', { name: 'Rule Name' })).toBeInTheDocument();
    expect(getByRole('columnheader', { name: 'Condition On' })).toBeInTheDocument();
    expect(getByRole('columnheader', { name: 'Provider' })).toBeInTheDocument();
    expect(getByText('test_prajwal')).toBeInTheDocument();
    expect(getByText('Payment Method, Custom Identifier 1, Amount')).toBeInTheDocument();
    expect(getByText('Priority 1')).toBeInTheDocument();
  });
});
