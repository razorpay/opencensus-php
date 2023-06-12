import React from 'react';
import { render, screen } from 'test-utils';
import { Card } from 'merchant_common/views/Reports/features/Overview/components/Card/index';
import { cardProp } from './fixtures/cardProps';
import { availableLinks } from 'merchant_common/views/Reports/features/Overview/components/Card/configs';

describe('OverviewCard', () => {
  const App = (props) => {
    return <Card {...props} />;
  };

  test('should render card component without any error', () => {
    render(<App data={cardProp} linkBasePath="" />);
    expect(
      screen.getByLabelText(`${availableLinks({ isSchedulesEnabled: false })[0].label} Button`),
    ).toBeInTheDocument();
    expect(screen.getByText(cardProp.name)).toBeInTheDocument();
    expect(screen.getByText(cardProp.description)).toBeInTheDocument();
  });
});
