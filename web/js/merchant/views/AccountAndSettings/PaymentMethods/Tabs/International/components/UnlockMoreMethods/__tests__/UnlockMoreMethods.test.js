import { MORE_PAYMENT_METHOD_STATUS } from 'merchant/reducers/unlockIntlPaymentMethods/initialState';
import UnlockMoreMethods from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/UnlockMoreMethods/UnlockMoreMethods';
import { DEFAULT_STATE } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/UnlockMoreMethods/labels';
import { render, screen, userEvent } from 'test-utils';

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/UnlockMoreMethods/TimelineView',
  () => ({
    __esModule: true,
    default: () => <div data-testid="timeline-view">Timeline View</div>,
  }),
);

const instrument = {
  header: 'Dummy header',
  listDescription: 'Dummy description',
};

const renderComponent = (props) => {
  return render(
    <UnlockMoreMethods instrument={instrument} user={{ business_type: '1' }} {...props} />,
  );
};

describe('Tests for UnlockMoreMethods component', () => {
  const fetchEddDetails = jest.fn();
  const productPaCbStatus = 'dummy_status';

  test('Should render without breaking', () => {
    expect(renderComponent({ fetchEddDetails })).toBeDefined();

    expect(screen.getByText(instrument.header)).toBeInTheDocument();
    expect(screen.getByText(instrument.listDescription)).toBeInTheDocument();
  });

  test('Should call fetchEddDetails on init', () => {
    renderComponent({ fetchEddDetails, productPaCbStatus });

    expect(fetchEddDetails).toHaveBeenCalledWith(productPaCbStatus);
  });

  test('Should show request for more methods button with status is INITIAL', () => {
    const status = MORE_PAYMENT_METHOD_STATUS.INITIAL;
    renderComponent({ fetchEddDetails, status });

    expect(screen.getByText('Request for more methods')).toBeInTheDocument();
  });

  test('Should call setIsMethodEnablementFormOpen with correct props when business type is valid on button click', async () => {
    const setIsMethodEnablementFormOpen = jest.fn();
    const status = MORE_PAYMENT_METHOD_STATUS.INITIAL;
    const user = { business_type: 1 };
    renderComponent({ fetchEddDetails, status, user, setIsMethodEnablementFormOpen });

    await userEvent.click(screen.getByText('Request for more methods'));

    await expect(setIsMethodEnablementFormOpen).toHaveBeenCalledWith({
      isOpen: true,
      defaultTab: 0,
    });
  });

  test('Should call setIsMethodEnablementFormOpen with correct props when business type is invalid on button click', async () => {
    const setIsMethodEnablementFormOpen = jest.fn();
    const status = MORE_PAYMENT_METHOD_STATUS.INITIAL;
    const user = { business_type: 13 };
    renderComponent({ fetchEddDetails, status, user, setIsMethodEnablementFormOpen });

    await userEvent.click(screen.getByText('Request for more methods'));

    await expect(setIsMethodEnablementFormOpen).toHaveBeenCalledWith({
      isOpen: true,
      defaultTab: 2,
    });
  });

  test('Should show alert when status is INITIAL', () => {
    const status = MORE_PAYMENT_METHOD_STATUS.INITIAL;
    renderComponent({ fetchEddDetails, status });

    expect(screen.getByText(DEFAULT_STATE)).toBeInTheDocument();

    //timeline view component shouldn't be visible
    expect(screen.queryByTestId('timeline-view')).not.toBeInTheDocument();
  });

  test('Should hide request more methods button if status is other than INITIAL', () => {
    const status = MORE_PAYMENT_METHOD_STATUS.IN_PROGRESS;
    renderComponent({ fetchEddDetails, status });

    expect(screen.queryByText('Request for more methods')).not.toBeInTheDocument();

    //timeline view component should be visible
    expect(screen.getByTestId('timeline-view')).toBeInTheDocument();
  });
});
