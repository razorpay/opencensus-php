import React from 'react';
import { screen, render } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import ConfigItem from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/ConfigItem';

import { DB_CATEGORY_WITH_ZONE } from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/fixtures';
import { MAPPING_TYPES } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/constants';

jest.mock('common/ui/Table/DataTable', () => () => <div>Data table</div>);

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <ConfigItem {...props} />
    </Provider>
  );
};

describe('test config item component', () => {
  test('should render properly', () => {
    const props = {
      type: MAPPING_TYPES.CATEGORY,
      item: {
        ...DB_CATEGORY_WITH_ZONE,
        type: 'serviceable',
      },
    };
    render(<App {...props} />);

    expect(screen.getByText('Category 1')).toBeInTheDocument();
    expect(screen.getByText('Data table')).toBeInTheDocument();
  });

  test('should show that COD is Blocked if category type is blacklisted', () => {
    const props = {
      type: MAPPING_TYPES.CATEGORY,
      item: {
        ...DB_CATEGORY_WITH_ZONE,
        type: 'blacklisted',
      },
    };
    render(<App {...props} />);
    expect(screen.getByText('COD is blocked')).toBeInTheDocument();
  });
});
