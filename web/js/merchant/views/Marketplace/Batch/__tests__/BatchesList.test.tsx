import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import BatchesList from 'merchant/views/Marketplace/Batch/List';

const location = {
  search: '',
};

jest.mock('merchant/containers/BatchNew/ListV2', () => ({
  __esModule: true,
  default: () => {
    return (
      <div>
        <div>Batch Upload</div>
      </div>
    );
  },
}));
describe('Reversal List', () => {
  const renderApp = () => {
    render(<BatchesList location={location} />);
  };
  test('should render Batch Upload', () => {
    renderApp();
    expect(screen.getByText('Batch Upload')).toBeInTheDocument();
  });
});
