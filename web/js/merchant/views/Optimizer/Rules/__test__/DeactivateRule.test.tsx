import React from 'react';
import { render } from 'test-utils';

import DeactivateRule from '../DeactivateRule';

describe('DeactivateRule', () => {
  const renderApp = () => {
    return render(<DeactivateRule ruleGroups={[]} onSuccess={() => {}} />, {
      initialState: {
        navigator: {
          deactivate_loading: false,
        },
      },
    });
  };

  it('should render without any errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  it('should render the fields', () => {
    const { getByText, getByRole } = renderApp();
    expect(getByText('Deactivate a rule to publish')).toBeInTheDocument();
    expect(
      getByText(
        'You already have maximum (25) custom rules. Deactivate one of the rules to proceed.',
      ),
    ).toBeInTheDocument();
    expect(getByRole('button', { name: 'Deactivate Rule' })).toBeInTheDocument();
  });
});
