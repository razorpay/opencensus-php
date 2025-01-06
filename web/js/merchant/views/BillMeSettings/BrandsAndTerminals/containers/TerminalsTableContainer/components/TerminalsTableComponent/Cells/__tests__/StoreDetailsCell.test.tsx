import React from 'react';

import StoreDetailsCell from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsTableComponent/Cells/StoreDetailsCell';
import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';
import { screen, render } from 'test-utils';

const App = ({ props }) => <StoreDetailsCell {...props} />;

const OFFLINE_STORE = {
  storeInfo: {
    storeType: 'OFFLINE',
    storeCode: '123',
  },
  name: 'Test Name',
};

describe('StoreDetailsCell', () => {
  test("should render 'StoreDetailsCell' component content with tooltip as expected", async () => {
    const props = {
      store: OFFLINE_STORE,
    };

    render(<App props={props} />);
    const offlineLabel = screen.getByText(STORE_TYPE_MAP.OFFLINE.label);
    expect(offlineLabel).toBeInTheDocument();
    expect(offlineLabel).toHaveStyle('color: hsla(150,100%,27%,1)');
    expect(screen.getByText('123 - Test Name')).toBeInTheDocument();
  });

  test("should render 'StoreDetailsCell' component content with tooltip as expected for 'Online' store", () => {
    const props = {
      store: { ...OFFLINE_STORE, storeInfo: { ...OFFLINE_STORE.storeInfo, storeType: 'ONLINE' } },
    };

    render(<App props={props} />);
    const onlineLabel = screen.getByText(STORE_TYPE_MAP.ONLINE.label);
    expect(onlineLabel).toBeInTheDocument();
    expect(onlineLabel).toHaveStyle('color: hsla(200, 84%, 37%, 1)');
  });

  test("should render 'StoreDetailsCell' component content with default values", () => {
    const props = {
      store: { ...OFFLINE_STORE, storeInfo: { ...OFFLINE_STORE.storeInfo, storeType: null } },
    };

    render(<App props={props} />);
    expect(screen.getByText('-')).toBeInTheDocument();
  });
});
