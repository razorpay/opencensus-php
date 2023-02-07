import React from 'react';
import { Provider } from 'react-redux';
import { render, screen } from 'test-utils';
import { storeWithInitialState } from 'merchant/store';
import SettlementCard from 'merchant/views/Settlements/components/SettlementCard';

describe('Settlement Card', () => {
  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <SettlementCard {...rest} />
      </Provider>
    );
  };

  test('should render Settlement Card', () => {
    const heading = 'sample heading';
    const headingInfo = 'sample heading info';
    const content = 'sample content';
    const footer = 'sample footer';

    render(<App heading={heading} content={content} footer={footer} headingInfo={headingInfo} />);
    expect(screen.getByText('sample heading')).toBeInTheDocument();
    expect(screen.getByText('sample heading info')).toBeInTheDocument();
    expect(screen.getByText('sample content')).toBeInTheDocument();
    expect(screen.getByText('sample footer')).toBeInTheDocument();
  });
});
