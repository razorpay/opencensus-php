import { getInitialState } from 'merchant/views/PartnerDashboard/Settings/configuration/utils';
import {
  responseDataEmpty,
  initialState,
  config,
  initialStateWithResponse,
} from './mocks/fixtures';

describe('utils', () => {
  test('should return default values if isError is true', () => {
    expect(getInitialState({ isError: true, data: responseDataEmpty })).toStrictEqual(initialState);
  });

  test('should return default values with configId if partner_metadata is null', () => {
    expect(getInitialState({ isError: false, data: responseDataEmpty })).toStrictEqual({
      ...initialState,
      config_id: responseDataEmpty.data.id,
    });
  });

  test('should return id, and default state with passed params', () => {
    const {
      id,
      partner_metadata: { brand_color, brand_name, text_color, logo_url },
    } = config;
    expect(
      getInitialState({
        isError: false,
        data: {
          data: {
            id,
            partner_metadata: {
              brand_name,
            },
          },
        },
      }),
    ).toStrictEqual({
      ...initialState,
      config_id: id,
      brand_name: initialStateWithResponse.brand_name,
    });

    expect(
      getInitialState({
        isError: false,
        data: {
          data: {
            id,
            partner_metadata: {
              brand_color,
            },
          },
        },
      }),
    ).toStrictEqual({
      ...initialState,
      config_id: id,
      brand_color: initialStateWithResponse.brand_color,
    });

    expect(
      getInitialState({
        isError: false,
        data: {
          data: {
            id,
            partner_metadata: {
              text_color,
            },
          },
        },
      }),
    ).toStrictEqual({
      ...initialState,
      config_id: id,
      text_color: initialStateWithResponse.text_color,
    });

    expect(
      getInitialState({
        isError: false,
        data: {
          data: {
            id,
            partner_metadata: {
              logo_url,
            },
          },
        },
      }),
    ).toStrictEqual({
      ...initialState,
      config_id: id,
      brand_logo: logo_url,
    });
  });

  test('should return all data if partner_metadata is passed', () => {
    expect(getInitialState({ isError: false, data: { data: config } })).toStrictEqual(
      initialStateWithResponse,
    );
  });
});
