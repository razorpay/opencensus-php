import { Provider } from 'react-redux';
import { render, fireEvent, screen } from 'test-utils';
import { storeWithInitialState } from 'merchant/store';
import BatchListContainer from 'merchant/views/Transactions/v1/BatchRefunds/List';
import { analyticsTrack } from 'common/utils/analytics';
import { selfServerTrack } from 'merchant/views/Transactions/v1/AnalyticsTrack';

const App = () => {
  return (
    <Provider
      store={storeWithInitialState({
        app: {
          isMobileResolution: true,
        },
      })}
    >
      <BatchListContainer
        location={{
          search: '',
        }}
      />
    </Provider>
  );
};

describe('BatchRefunds - List.js', () => {
  test('should render BatchList Component', () => {
    render(<App />);
    expect(screen.getByTestId('batchrefunds-batchlist')).toBeInTheDocument();
  });

  // TODO there is a problem with mock response will pick it soon
  test.skip('should call analyticsTrack onSubmit invoke', () => {
    render(<App />);
    const submitBtn = screen.getByRole('button', {
      name: 'Search',
    });
    expect(submitBtn).toBeInTheDocument();
    fireEvent.click(submitBtn);
    expect(selfServerTrack).toHaveBeenCalled();
    expect(selfServerTrack).toHaveBeenCalledWith({ type: 'batchRefund', actionType: 'search' });
    expect(analyticsTrack).toHaveBeenCalled();
    expect(analyticsTrack).toHaveBeenCalledWith({
      objectName: 'batch refunds search',
      actionName: 'clicked',
      screen: 'transactions',
      properties: expect.anything(),
    });
  });
});
