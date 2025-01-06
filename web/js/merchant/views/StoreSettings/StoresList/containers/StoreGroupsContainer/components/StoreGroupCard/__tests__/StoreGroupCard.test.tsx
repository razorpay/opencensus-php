import React from 'react';

import StoreGroupCard from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreGroupCard';
import { screen, render } from 'test-utils';

const App = ({ props }) => <StoreGroupCard {...props} />;

const STORE_GROUP_CARD_PROPS = {
  onCardSelect: jest.fn(),
  storeGroupInfo: {
    id: '123',
    name: 'Test Store Group Name',
    description: 'Test Store Group Description',
    storesCount: 1,
  },
  activeStoreGroup: false,
};

describe('StoreGroupCard', () => {
  test('should render StoreGroupCard with passed in name, description and storesCount', () => {
    render(<App props={STORE_GROUP_CARD_PROPS} />);
    const groupName = screen.getByText('Test Store Group Name');
    expect(groupName).toBeInTheDocument();
    expect(screen.getByText('Test Store Group Description')).toBeInTheDocument();
    expect(screen.getByText('1 stores')).toBeInTheDocument();

    // Since, 'activeStoreGroup' prop is false, card should not be highlighted
    const cardElement = groupName.closest('div[data-blade-component=base-box]');
    expect(cardElement).toHaveStyle('background-color: hsla(0,0%,100%,1)');
  });

  test("should render stores count with respect to passed in 'storesCount' prop and 'activeStoreGroup' card should be highlighted", () => {
    render(
      <App
        props={{
          ...STORE_GROUP_CARD_PROPS,
          activeStoreGroup: true,
          storeGroupInfo: { ...STORE_GROUP_CARD_PROPS.storeGroupInfo, storesCount: 3 },
        }}
      />,
    );
    expect(screen.getByText('3 stores')).toBeInTheDocument();

    const cardElement = screen
      .getByText('Test Store Group Name')
      .closest('div[data-blade-component=base-box]');
    expect(cardElement).toHaveStyle('background-color: hsla(213, 47%, 96%, 1)');
  });
});
