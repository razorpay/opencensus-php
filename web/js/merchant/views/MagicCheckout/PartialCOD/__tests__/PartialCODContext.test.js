import React from 'react';
import { render, waitFor } from 'test-utils';

import {
  PartialCODContext,
  PartialCODContextProvider,
} from 'merchant/views/MagicCheckout/PartialCOD/context/PartialCODContext';
import { PARTIAL_COD_TYPE } from 'merchant/views/MagicCheckout/PartialCOD/types';
import {
  NEW_PARTIAL_COD_CONFIGS,
  NOTIFICATION_MSGS,
} from 'merchant/views/MagicCheckout/PartialCOD/constants';

const mockUpdateConfigs = jest.fn().mockResolvedValue({});
const mockShowNotification = jest.fn();

const defaultProps = {
  isPartialCODEnabled: false,
  partialCODConfigs: NEW_PARTIAL_COD_CONFIGS,
  platform: 'web',
  shopId: 'shop123',
  showNotification: mockShowNotification,
  updateConfigs: mockUpdateConfigs,
};

const customRender = (ui, providerProps = defaultProps) => {
  return render(<PartialCODContextProvider {...providerProps}>{ui}</PartialCODContextProvider>);
};

describe('PartialCODContextProvider', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should provide default context values', () => {
    let contextValues;
    customRender(
      <PartialCODContext.Consumer>
        {(value) => {
          contextValues = value;
          return null;
        }}
      </PartialCODContext.Consumer>,
    );
    expect(contextValues.configsLocal).toEqual(NEW_PARTIAL_COD_CONFIGS);
    expect(contextValues.isPartialCODEnabledLocal).toBe(false);
    expect(typeof contextValues.handleAddSlab).toBe('function');
  });
  it('should update configs when handleAddSlab is called', async () => {
    let contextValues;
    customRender(
      <PartialCODContext.Consumer>
        {(value) => {
          contextValues = value;
          return null;
        }}
      </PartialCODContext.Consumer>,
    );
    const newSlab = {
      amount: 100,
      rules: { customer_risk_category: [] },
    };
    contextValues.handleAddSlab(newSlab, {});
    await waitFor(() => {
      expect(mockUpdateConfigs).toHaveBeenCalledWith({
        platform: defaultProps.platform,
        shop_id: defaultProps.shopId,
        one_cc_partial_payments_cod: {
          enabled: false,
          configs: {
            ...NEW_PARTIAL_COD_CONFIGS,
            prepaid_payment_amount: [...NEW_PARTIAL_COD_CONFIGS.prepaid_payment_amount, newSlab],
          },
        },
      });
    });

    expect(mockShowNotification).toHaveBeenCalledWith({
      type: 'success',
      message: NOTIFICATION_MSGS.slabSavedSuccess,
      className: 'magic-partial-cod-notification',
    });
  });

  it('should remove a slab when handleRemoveSlab is called', async () => {
    let contextValues;
    customRender(
      <PartialCODContext.Consumer>
        {(value) => {
          contextValues = value;
          return null;
        }}
      </PartialCODContext.Consumer>,
      { ...defaultProps, showNotification: mockShowNotification },
    );
    contextValues.handleRemoveSlab(0, {});
    await waitFor(() => {
      expect(mockUpdateConfigs).toHaveBeenCalledWith({
        platform: defaultProps.platform,
        shop_id: defaultProps.shopId,
        one_cc_partial_payments_cod: {
          enabled: false,
          configs: {
            ...NEW_PARTIAL_COD_CONFIGS,
            prepaid_payment_amount: [],
          },
        },
      });
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'success',
        message: NOTIFICATION_MSGS.slabRemovedSuccess,
        className: 'magic-partial-cod-notification',
      });
    });
  });

  it('should call handleUpdateSlabType and update the type in the config', async () => {
    let contextValues;
    customRender(
      <PartialCODContext.Consumer>
        {(value) => {
          contextValues = value;
          return null;
        }}
      </PartialCODContext.Consumer>,
      { ...defaultProps, showNotification: mockShowNotification },
    );

    contextValues.handleUpdateSlabType(PARTIAL_COD_TYPE.BASIC, {});

    await waitFor(() => {
      expect(mockUpdateConfigs).toHaveBeenCalledWith({
        platform: defaultProps.platform,
        shop_id: defaultProps.shopId,
        one_cc_partial_payments_cod: {
          enabled: false,
          configs: {},
        },
      });
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'success',
        message: NOTIFICATION_MSGS.slabRemovedSuccess,
        className: 'magic-partial-cod-notification',
      });
    });
  });

  it('should update partial COD status when handleUpdatePartialCODStatus is called', async () => {
    const status = true;
    const callbacks = {};
    let contextValues;
    customRender(
      <PartialCODContext.Consumer>
        {(value) => {
          contextValues = value;
          return null;
        }}
      </PartialCODContext.Consumer>,
      { ...defaultProps, showNotification: mockShowNotification },
    );
    contextValues.handleUpdatePartialCODStatus(status, callbacks);
    await waitFor(() => {
      expect(mockUpdateConfigs).toHaveBeenCalledWith({
        platform: defaultProps.platform,
        shop_id: defaultProps.shopId,
        one_cc_partial_payments_cod: {
          enabled: true,
          configs: {
            prepaid_payment_amount: [],
            type: 'basic',
          },
        },
      });
    });
  });
});
