import { render, screen, userEvent } from 'test-utils';
import { getInstrumentData } from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/__tests__/mocks/fixtures';

import {
  REQUESTABLE,
  REQUESTED,
  ACTIVATED,
  REJECTED,
} from 'merchant/views/Settings/PaymentMethods/constants';
import { titleCase } from 'common/utils/rzp-utils';

import * as instrumentIcons from 'merchant/views/Settings/PaymentMethods/components/InstrumentIcons';
import Instrument from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/Instrument';

const renderComponent = (props = {}) => {
  return render(<Instrument {...props} />);
};

describe('<Instrument /> initial state when status is GREYED/REQUESTABLE', () => {
  const data = getInstrumentData(REQUESTABLE);

  test('Component should display correct info when data is passed correctly with default props', () => {
    //spy method for getIcon
    const getIcon = jest.spyOn(instrumentIcons, 'getIcon');

    renderComponent({ data });

    //getIcon function should be called
    expect(getIcon).toHaveBeenCalledTimes(1);
    expect(getIcon).toHaveBeenCalledWith(data.icon);

    //name and description should be displayed
    expect(screen.getByText(data.name)).toBeInTheDocument();
    expect(screen.getByText(data.description)).toBeInTheDocument();

    //button and status should be hidden when showInstrumentAction is not passed
    expect(screen.queryByText('Request')).not.toBeInTheDocument(); //button
    expect(screen.queryByText(titleCase(data.status))).not.toBeInTheDocument(); //status
    expect(screen.queryByText('TAT: ')).not.toBeInTheDocument(); //tat
  });

  test('Component should display request button when showInstrumentAction is True', async () => {
    const onInstrumentRequest = jest.fn();
    renderComponent({ data, showInstrumentAction: true, onInstrumentRequest });

    //button should be visible
    expect(screen.getByText('Request')).toBeInTheDocument();

    //status and tat should be hidden/not-visible
    expect(screen.queryByText(titleCase(data.status))).not.toBeInTheDocument(); //status
    expect(screen.queryByText('TAT: ')).not.toBeInTheDocument(); //tat

    //button should be clickable
    await userEvent.click(screen.getByText('Request'));

    //should call the onInstrumentRequest function passed through props
    expect(onInstrumentRequest).toHaveBeenCalledTimes(1);
    expect(onInstrumentRequest).toHaveBeenCalledWith(data);
  });

  test('Component should display passed component when showInstrumentAction is True and rightButton is passed', async () => {
    const onInstrumentRequest = jest.fn();
    const rightButton = () => <div>Click Me</div>;
    renderComponent({ data, showInstrumentAction: true, onInstrumentRequest, rightButton });

    //passed in button should be visible
    expect(screen.getByText('Click Me')).toBeInTheDocument();

    //default request button should be hidden
    expect(screen.queryByText('Request')).not.toBeInTheDocument();

    //button should be clickable
    await userEvent.click(screen.getByText('Click Me'));

    //should not call the onInstrumentRequest function passed through props
    expect(onInstrumentRequest).toHaveBeenCalledTimes(0);
  });
});

describe('<Instrument /> initial state when status is REQUESTED', () => {
  const data = getInstrumentData(REQUESTED);
  const instrumentsTat = {
    'pg.international.ach': 2,
  };
  const leafInstrument = {
    slug: 'international',
  };

  test('component should show REQUESTED state', () => {
    renderComponent({ data, showInstrumentAction: true });

    //state should be requested and visible
    expect(screen.getByText(titleCase(data.status))).toBeInTheDocument();

    //button should be hidden
    expect(screen.queryByText('Request')).not.toBeInTheDocument();
  });

  test('component should show TAT when all required data is passed correctly', () => {
    renderComponent({ data, showInstrumentAction: true, instrumentsTat, leafInstrument });

    //TAT should be visible
    expect(screen.getByText('TAT: < 2 Days')).toBeInTheDocument();
  });

  test('component should hide TAT when showTat is passed false', () => {
    renderComponent({
      data,
      showInstrumentAction: true,
      showTat: false,
      instrumentsTat,
      leafInstrument,
    });

    //TAT should be hidden
    expect(screen.queryByText('TAT: < 2 Days')).not.toBeInTheDocument();
  });

  test('component should render without breaking if tat data is not passed properly and should be hidden', () => {
    expect(
      renderComponent({
        data,
        showInstrumentAction: true,
        instrumentsTat,
      }),
    ).toBeDefined();

    //TAT should be hidden
    expect(screen.queryByText('TAT: < 2 Days')).not.toBeInTheDocument();
  });
});

describe('<Instrument /> initial state when status is ACTIVATED', () => {
  const data = getInstrumentData(ACTIVATED);

  test('component should show ACTIVATED state', () => {
    renderComponent({ data, showInstrumentAction: true });

    //state should be activated and visible
    expect(screen.getByText(titleCase(data.status))).toBeInTheDocument();

    //button and tat should be hidden
    expect(screen.queryByText('Request')).not.toBeInTheDocument();
    expect(screen.queryByText('TAT:')).not.toBeInTheDocument();
  });
});

describe('<Instrument /> initial state when status is REJECTED', () => {
  const data = getInstrumentData(REJECTED);

  test('component should show REJECTED state', () => {
    renderComponent({ data, showInstrumentAction: true });

    //state should be rejected and visible
    expect(screen.getByText(titleCase(data.status))).toBeInTheDocument();

    //button and tat should be hidden
    expect(screen.queryByText('Request')).not.toBeInTheDocument();
    expect(screen.queryByText('TAT:')).not.toBeInTheDocument();
  });
});
