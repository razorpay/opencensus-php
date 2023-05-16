import { render, screen, userEvent } from 'test-utils';
import { getLeafList } from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/__tests__/mocks/fixtures';

import {
  ACTION_REQUIRED,
  GREYED,
  REJECTED,
  REQUESTED,
} from 'merchant/views/Settings/PaymentMethods/constants';
import { titleCase } from 'common/utils/rzp-utils';

import InstrumentContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/index';

const renderComponent = (props = {}) => {
  return render(<InstrumentContainer {...props} />);
};

describe('<InstrumentContainer /> when containerStatus is GREYED/REQUESTABLE', () => {
  const leafList = getLeafList(GREYED);

  test('component should display name, description and list of instruments available', () => {
    //containerStatus is GREYED by default if not passed
    renderComponent({ leafList, showAction: false, buttonText: 'Activate' });

    //header and description should be visible
    expect(screen.getByText(leafList.listHeader)).toBeInTheDocument();
    expect(screen.getByText(leafList.listDescription)).toBeInTheDocument();

    //request button should be hidden
    expect(screen.queryByText('Activate')).not.toBeInTheDocument();

    leafList.list.forEach((item) => {
      expect(screen.getByText(item.name)).toBeInTheDocument();
      expect(screen.getByText(item.description)).toBeInTheDocument();
    });
  });

  test('component should show request button when showAction is passed true', async () => {
    const onButtonClick = jest.fn();

    renderComponent({
      leafList,
      showAction: true,
      showListAction: false,
      buttonText: 'Activate',
      onButtonClick,
    });

    //activate butto should be visible
    expect(screen.getByText('Activate')).toBeInTheDocument();

    //activate button click should call the passed onClick function
    await userEvent.click(screen.getByText('Activate'));
    expect(onButtonClick).toHaveBeenCalledTimes(1);
  });
});

describe('<InstrumentContainer /> when status is Requested', () => {
  test('When component displays name, description, status, TAT and list if instruments available', () => {
    const leafList = getLeafList(REQUESTED);

    renderComponent({
      leafList,
      showAction: true,
      showListAction: false,
      buttonText: 'Activate',
      containerStatus: REQUESTED,
    });

    //header, description and status should be visible
    expect(screen.getByText(leafList.listHeader)).toBeInTheDocument();
    expect(screen.getByText(leafList.listDescription)).toBeInTheDocument();
    expect(screen.getByText(titleCase(REQUESTED))).toBeInTheDocument();

    //request button should not be visible for the container
    expect(screen.queryByText('Activate')).not.toBeInTheDocument();
  });
});

describe('<InstrumentContainer /> when status is ActionRequired/Rejected', () => {
  test('When component displays error message and action with Status Rejected', () => {
    const leafList = getLeafList(ACTION_REQUIRED);

    renderComponent({
      leafList,
      showAction: true,
      showListAction: false,
      buttonText: 'Activate',
      containerStatus: REJECTED,
      error: {
        message: 'error message',
        action: 'error action',
      },
    });

    //rejected status and message should be visible
    expect(screen.getByText(titleCase(REJECTED))).toBeInTheDocument();
    expect(screen.getByText('error message')).toBeInTheDocument();
    expect(screen.getByText('error action')).toBeInTheDocument();

    //request button should not be visible for the container
    expect(screen.queryByText('Activate')).not.toBeInTheDocument();
  });

  test('Component should render without breaking if only error message is sent with rejected status', () => {
    const leafList = {
      listHeader: 'dummy header',
      listDescription: 'dummy description',
      list: [
        {
          name: 'dummy list instrument name 1',
          description: 'dummy list instrument description 1',
        },
      ],
    };

    expect(
      renderComponent({
        leafList,
        showAction: true,
        showListAction: false,
        buttonText: 'Activate',
        containerStatus: REJECTED,
        error: {
          message: 'error message',
        },
      }),
    ).toBeDefined();

    //rejected status and message should be visible
    expect(screen.getByText(titleCase(REJECTED))).toBeInTheDocument();
    expect(screen.getByText('error message')).toBeInTheDocument();

    //request button should not be visible for the container
    expect(screen.queryByText('Activate')).not.toBeInTheDocument();
  });

  test('Component should show html properly if sent as error message/action placeholder', async () => {
    const leafList = getLeafList(GREYED);
    const onContactClick = jest.fn();

    expect(
      renderComponent({
        leafList,
        showAction: true,
        showListAction: false,
        buttonText: 'Activate',
        containerStatus: REJECTED,
        error: {
          message: 'error message',
          action: (
            <p>
              <a onClick={onContactClick}>contact</a> support
            </p>
          ),
        },
      }),
    ).toBeDefined();

    //rejected status and message should be visible
    expect(screen.getByText(titleCase(REJECTED))).toBeInTheDocument();
    expect(screen.getByText('error message')).toBeInTheDocument();
    expect(screen.getByText('contact')).toBeInTheDocument();

    //request button should not be visible for the container
    expect(screen.queryByText('Activate')).not.toBeInTheDocument();

    //fire contact click event passed through props
    await userEvent.click(screen.getByText('contact'));
    expect(onContactClick).toHaveBeenCalledTimes(1);
  });
});
