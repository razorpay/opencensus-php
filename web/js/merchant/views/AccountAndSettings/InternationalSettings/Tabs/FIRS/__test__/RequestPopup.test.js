import { dateObject } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures';
import { mockContextData } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures/mocks';
import RequestPopup from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/RequestModals/RequestPopup';
import {
  PopupTitle,
  PopupType,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import * as services from 'merchant/views/AccountAndSettings/InternationalSettings/services';
import * as utils from 'merchant/views/AccountAndSettings/InternationalSettings/utils';
import { render, screen, userEvent, waitFor } from 'test-utils';

const renderApp = (props = {}) => {
  render(<RequestPopup {...props} />);
};

describe('Tests for RequestPopup component', () => {
  const { month, year } = dateObject;
  const popupData = {
    isOpen: true,
    type: PopupType.INTERNAL_FIRS,
    month,
    year,
  };
  const setPopupData = jest.fn();
  const setError = jest.fn();

  test('Modal info should be visible properly when opened for the first time', () => {
    mockContextData({ popupData, setPopupData, setError });
    renderApp();

    expect(screen.getByText(PopupTitle[PopupType.INTERNAL_FIRS])).toBeInTheDocument();
    expect(screen.getByText('Requesting for Razorpay statement')).toBeInTheDocument();
    expect(
      screen.getByText('Please wait for a few seconds. Your request is being processed...', {
        exact: false,
      }),
    ).toBeInTheDocument();
  });

  test('requestInternalFirs should be called on initial load when isOpen is true and popup type is internalFirs', () => {
    mockContextData({ popupData, setPopupData, setError });
    renderApp();

    expect(services.requestInternalFirs).toHaveBeenCalled();
    expect(services.requestInternalFirs).toHaveBeenCalledWith(month, year);
  });

  test('requestInternalFirs should not be called if isOpen is false', () => {
    mockContextData({ popupData: { ...popupData, isOpen: false }, setPopupData, setError });
    renderApp();

    expect(services.requestInternalFirs).not.toHaveBeenCalled();
  });

  test('requestInternalFirs should not be called if popupType is not internalFirs', () => {
    mockContextData({
      popupData: { ...popupData, type: PopupType.DOWNLOAD_FIRS },
      setPopupData,
      setError,
    });
    renderApp();

    expect(services.requestInternalFirs).not.toHaveBeenCalled();
  });

  test('updateFirsDataObject should be called if api call is sucessfull', async () => {
    const setFirsData = jest.fn((callback) => callback());
    const updateFirsDataObjectSpy = jest.spyOn(utils, 'updateFirsDataObject');
    services.requestInternalFirs.mockReturnValue(Promise.resolve('Success'));
    mockContextData({
      popupData,
      setPopupData,
      setError,
      setFirsData,
    });
    renderApp();

    await waitFor(() => expect(services.requestInternalFirs).toHaveBeenCalled());
    expect(updateFirsDataObjectSpy).toHaveBeenCalledWith(month, year, undefined, 'Success');
    expect(setPopupData).toHaveBeenCalled();

    updateFirsDataObjectSpy.mockRestore();
  });

  test('setError should be called if api call fails', async () => {
    services.requestInternalFirs.mockReturnValue(Promise.reject({ error: 'error' }));
    mockContextData({
      popupData,
      setPopupData,
      setError,
    });
    renderApp();

    await waitFor(() => expect(services.requestInternalFirs).toHaveBeenCalled());
    expect(setError).toHaveBeenCalled();
    expect(setError).toHaveBeenCalledWith({ error: 'error' }.toString());

    //to close the popup
    expect(setPopupData).toHaveBeenCalled();
  });

  test('Close icon should be visible and functional', async () => {
    mockContextData({
      popupData,
      setPopupData,
      setError,
    });
    renderApp();

    //close icon should be visible
    expect(screen.getByLabelText('Close')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Close'));
    await waitFor(() => expect(setPopupData).toHaveBeenCalled());
  });
});
