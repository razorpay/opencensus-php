import * as Formik from 'formik';

import { mockContextData } from './mocks';

import * as actions from 'merchant/reducers/unlockIntlPaymentMethods/actions';
import VideoKyc from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/components/VideoKyc';
import { FORM_INITIAL_VALUES } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import { render, screen, userEvent, waitFor } from 'test-utils';

const useFormikContextSpy = jest.spyOn(Formik, 'useFormikContext');
const formikDefaultReturnValues = {
  errors: {},
  values: FORM_INITIAL_VALUES,
  setFieldValue: jest.fn(),
};

const renderComponent = (props = {}, initialState = {}) =>
  render(<VideoKyc {...props} />, { initialState });

const promoterPanName = 'Dummy name';

describe('Tests for VideoKyc component - MethodEnablementForm', () => {
  beforeEach(() => {
    useFormikContextSpy.mockReturnValue(formikDefaultReturnValues);
  });

  test('Should render without breaking', () => {
    renderComponent();

    expect(screen.getByText('Authorised Signatory')).toBeInTheDocument();
  });

  test('Should show Authorised Signatory by default with radio buttons', () => {
    mockContextData({ isLoading: false });
    renderComponent({}, { session: { user: { promoter_pan_name: promoterPanName } } });

    expect(screen.getByLabelText('the authorized signatory')).not.toBeChecked();
    expect(screen.getByLabelText('not the authorized signatory')).not.toBeChecked();
  });

  test('Should conditionally render Get Link button if second radio button is checked', async () => {
    useFormikContextSpy.mockReturnValue({
      ...formikDefaultReturnValues,
      values: { signatory: '0' },
    });
    mockContextData({ isLoading: false });
    renderComponent({}, { session: { user: { promoter_pan_name: promoterPanName } } });

    await expect(screen.getByRole('button', { name: 'Get Link' })).toBeVisible();
    await expect(screen.queryByText('Video KYC link')).not.toBeInTheDocument();
  });

  test('Should show link when Get Link button is clicked', async () => {
    const weblink = 'Dummy link';
    const createVCipLinkMock = jest.fn(() => () => ({ payload: { details: { weblink } } }));
    jest.spyOn(actions, 'createVCipLink').mockImplementation(createVCipLinkMock);
    useFormikContextSpy.mockReturnValue({
      ...formikDefaultReturnValues,
      values: { signatory: '0' },
    });
    mockContextData({ isLoading: false });
    renderComponent({}, { session: { user: { promoter_pan_name: promoterPanName } } });

    await expect(screen.getByRole('button', { name: 'Get Link' })).toBeVisible();
    await userEvent.click(screen.getByRole('button', { name: 'Get Link' }));

    await expect(createVCipLinkMock).toHaveBeenCalledWith(promoterPanName);
    await waitFor(() => expect(screen.getByDisplayValue(weblink)).toBeInTheDocument());
  });

  test('Should show pre-requisites when authorised signatory is checked', async () => {
    useFormikContextSpy.mockReturnValue({
      ...formikDefaultReturnValues,
      values: { signatory: '1' },
    });
    mockContextData({ isLoading: false });
    renderComponent({}, { session: { user: { promoter_pan_name: promoterPanName } } });

    await expect(screen.queryByRole('button', { name: 'Get Link' })).not.toBeInTheDocument();
    await expect(
      screen.getByText('Please ensure the following before proceeding for your video KYC:'),
    ).toBeVisible();

    await expect(
      screen.getByRole('checkbox', {
        name: 'I confirm that I possess all the documents mentioned above and am ready to proceed for video KYC',
      }),
    ).toBeInTheDocument();
  });
});
