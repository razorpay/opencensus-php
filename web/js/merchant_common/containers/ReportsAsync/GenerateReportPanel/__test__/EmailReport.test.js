import { render, screen, fireEvent } from 'common/services/test/test-utils';
import EmailReport from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/EmailReport';
import { getByRole } from '@testing-library/dom';
import * as analytics from 'common/utils/analytics';
import * as selfServeAnalytics from 'common/utils/selfServeAnalytics';
import RolesList from 'merchant/helpers/permissions/roles-list';
import { storeWithInitialState } from 'merchant/store';
import { Provider } from 'react-redux';
import ModalDialog from 'common/ui/ModalDialog';

describe('Email Report', () => {
  const emails = ['test1@gmail.com', 'test2@gmail.com', 'test3@gmail.com'];

  let appRef = {};
  const analyticsSpy = jest.spyOn(analytics, 'analyticsTrack');
  const selfServeTrackSpy = jest.spyOn(selfServeAnalytics, 'selfServeTrackInitiate');

  const defaultProps = {
    selectedEmails: [],
    emails,
    isFormDisabled: false,
  };

  const App = (props = {}) => {
    return (
      <Provider store={storeWithInitialState({ session: { user: { role: RolesList.OWNER } } })}>
        <>
          <ModalDialog />
          <EmailReport ref={(ref) => (appRef = ref)} {...defaultProps} {...props} />
        </>
      </Provider>
    );
  };

  test('should render Email report', () => {
    render(<App />);
    expect(screen.getByText('Email Report To')).toBeInTheDocument();
    expect(screen.getByText(emails[0])).toBeInTheDocument();
    expect(screen.queryByText(emails[1])).not.toBeInTheDocument();
    expect(screen.getByText('Choose email')).toBeInTheDocument();
  });

  test('should open ChooseEmail modal and validate onChange', () => {
    render(<App />);
    fireEvent.click(screen.getByRole('button', { name: 'Choose email' }));
    // validate against openModal content as openModal cannot be jest.spy'ed
    expect(document.querySelector('h3.modal-title')).toHaveTextContent('Choose Email');

    const modalContainer = document.getElementsByClassName('ReactModalPortal')[0];
    const emailOneElement = getByRole(modalContainer, 'checkbox', { name: emails[0] });
    const emailTwoElement = screen.getByRole('checkbox', { name: emails[1] });
    const emailThreeElement = screen.getByRole('checkbox', { name: emails[2] });
    fireEvent.click(emailOneElement);
    expect(analyticsSpy).toHaveBeenCalledWith({
      objectName: 'email selection',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        emailSelected: true,
      },
    });
    expect(selfServeTrackSpy).toHaveBeenCalledWith({
      selfServeAction: 'Report Downloaded',
      page: 'Reports',
      screen: 'Reports',
    });

    // test multiple emails selection
    fireEvent.click(emailTwoElement);
    fireEvent.click(emailThreeElement);
    expect(screen.getByText('3 Emails selected')).toBeInTheDocument();

    expect(appRef.wrappedInstance.getValue()).toStrictEqual(emails);

    // test unchecking
    fireEvent.click(emailThreeElement);
    expect(screen.getByText('2 Emails selected')).toBeInTheDocument();
    expect(analyticsSpy).toHaveBeenCalledWith({
      objectName: 'email selection',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        emailSelected: false,
      },
    });
  });

  test('should show Add Email button and open AddEmail Modal', () => {
    render(<App emails={[]} />);
    const addEmailButton = screen.getByRole('button', { name: 'Add Email' });
    fireEvent.click(addEmailButton);

    expect(analyticsSpy).toHaveBeenCalledWith({
      objectName: 'add email',
      actionName: 'clicked',
      screen: 'reports',
      properties: {},
    });
  });

  test('should check for formDisabled class', () => {
    const { container } = render(<App isFormDisabled />);
    expect(container.querySelector('.Input--disabled')).toBeInTheDocument();
  });
});
