import { createMemoryHistory } from 'history';
import growthServiceCTAHandler, {
  SUB_ASSET_TYPE,
  MODAL_VARIANT,
  PRICING_BUNDLE_VARIANT,
} from 'merchant/models/GrowthService/growthServiceCTAHandler';
import * as modalFn from 'merchant_common/reducers/modals';
import * as commonApi from 'common/utils/common-api';
import { extUrlData, intUrlData, sfData, modaldata, tracking_id } from './fixtures';
let history;

describe('growthServiceCTAHandler', () => {
  beforeAll(() => {
    history = createMemoryHistory();
  });

  it('Undefined CTA Handler array', () => {
    expect(growthServiceCTAHandler()).toBeUndefined();
  });

  it('Empty CTA Handler array', () => {
    expect(growthServiceCTAHandler([])).toBeUndefined();
  });

  it('CTA Handler Array with external URL event', () => {
    const spyWindowOpen = jest.spyOn(window, 'open');
    spyWindowOpen.mockImplementation(jest.fn());
    growthServiceCTAHandler(extUrlData);
    expect(window.open).toHaveBeenCalledTimes(1);
  });

  it('CTA Handler Array with internal URL event', () => {
    const spyHistory = jest.spyOn(history, 'push');
    spyHistory.mockImplementation(jest.fn());
    growthServiceCTAHandler(intUrlData, history);
    expect(history.push).toHaveBeenCalledTimes(1);
  });

  it('CTA Handler Array with Saleforce Event', () => {
    const spySF = jest.spyOn(commonApi, 'sendDataToSalesForce');
    const tracking = {
      getTrackingData: jest.fn(),
      trackEvent: jest.fn(),
    };
    growthServiceCTAHandler(sfData, history, tracking_id, tracking);
    expect(spySF).toHaveBeenCalledTimes(1);
  });

  it('CTA Handler Array with template handler to open modal', () => {
    const spyOpenModal = jest.spyOn(modalFn, 'openModal');
    growthServiceCTAHandler(
      modaldata(SUB_ASSET_TYPE.MODAL, MODAL_VARIANT.DEFAULT),
      history,
      tracking_id,
    );
    expect(spyOpenModal).toHaveBeenCalledTimes(1);
  });

  it('CTA Handler Array with template handler to open thank you modal', () => {
    const spyOpenModal = jest.spyOn(modalFn, 'openModal');
    growthServiceCTAHandler(
      modaldata(SUB_ASSET_TYPE.MODAL, MODAL_VARIANT.THANKYOU),
      history,
      tracking_id,
    );
    expect(spyOpenModal).toHaveBeenCalledTimes(1);
  });

  it('CTA Handler Array with template handler to open center cta modal', () => {
    const spyOpenModal = jest.spyOn(modalFn, 'openModal');
    growthServiceCTAHandler(
      modaldata(SUB_ASSET_TYPE.MODAL, MODAL_VARIANT.CENTERCTA),
      history,
      tracking_id,
    );
    expect(spyOpenModal).toHaveBeenCalledTimes(1);
  });

  it('CTA Handler Array with pricing_bundle handler to open pricing bundle modal', () => {
    const spyOpenModal = jest.spyOn(modalFn, 'openModal');
    growthServiceCTAHandler(
      modaldata(SUB_ASSET_TYPE.PRICINGBUNDLE, PRICING_BUNDLE_VARIANT.READ_ONLY),
      history,
      tracking_id,
    );
    expect(spyOpenModal).toHaveBeenCalledTimes(1);
  });
});
