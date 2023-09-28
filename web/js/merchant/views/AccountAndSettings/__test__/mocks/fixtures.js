import * as Breadcrumb from 'common/components/Breadcrumb';
import { accountAndSettingsLink } from 'merchant/views/AccountAndSettings/constants/constants';
import { screen, getByRole } from 'test-utils';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';

jest.mock('react-router-dom', () => ({
  __esModule: true,
  ...jest.requireActual('react-router-dom'),
  Route: ({ path, element }) => <div data-testid={path}>{element}</div>,
  Routes: ({ children }) => children,
}));

jest.mock('common/components/Breadcrumb', () => ({
  __esModule: true,
  default: () => <div>Breadcrumb</div>,
}));

jest.mock('common/ui/DashboardBanner', () => ({
  __esModule: true,
  default: () => <>Dashboard Banner</>,
}));

jest.mock('merchant/components/TestModeBanner', () => ({
  __esModule: true,
  default: () => <>Test Mode Banner</>,
}));

// do not forget to mock default breadcrumb
export const testBreadCrumb = (renderApp, linkLabel, pathLink, isRenderAsync = false) => {
  describe('Breadcrumb links', () => {
    beforeAll(() => {
      // eslint-disable-next-line no-import-assign
      Breadcrumb.default = jest.requireActual('common/components/Breadcrumb').default;
    });

    test('should have both account and settings link and current route', async () => {
      if (isRenderAsync) await renderApp();
      else renderApp();
      const breadCrumbElement = screen.getByTestId('breadcrumb');
      const accountAndSettingsLinkElement = getByRole(breadCrumbElement, 'link', {
        name: accountAndSettingsLink.label,
      });
      expect(accountAndSettingsLinkElement).toBeInTheDocument();
      expect(accountAndSettingsLinkElement).toHaveAttribute('href', accountAndSettingsLink.link);
      const currentPathLink = getByRole(breadCrumbElement, 'link', { name: linkLabel });
      expect(currentPathLink).toBeInTheDocument();
      expect(currentPathLink).toHaveAttribute('href', pathLink);
    });

    afterAll(() => {
      // eslint-disable-next-line no-import-assign
      Breadcrumb.default = () => <div>Breadcrumb</div>;
    });
  });
};

// Do not forget to mock the functions in conditionalUtils
export const testConditionalLinks = (
  renderApp,
  links,
  mockTwice = false,
  isRenderAsync = false,
) => {
  test.each(links)('should render %s link when %s', async (linkName, condition, linkPath) => {
    conditionalUtils[condition].mockReturnValueOnce(true);
    if (mockTwice) {
      conditionalUtils[condition].mockReturnValueOnce(true);
    }
    if (isRenderAsync) await renderApp();
    else renderApp();
    const link = screen.getByRole('link', { name: linkName });
    expect(link).toBeInTheDocument();
    expect(link).toHaveAttribute('href', linkPath);
  });
};

// make sure renderApp accepts pathname and user as arguments
export const testRedirectionWhenAccountAndSettingsIsNotEnabled = (
  renderApp,
  paths,
  isRenderAsync = false,
) => {
  test.each(paths)('should redirect to %s when %s is accessed', async (redirectPath, path) => {
    let renderOutput;
    if (isRenderAsync) {
      renderOutput = await renderApp({
        pathname: path,
        user: { isAccountAndSettingsRevampEnabled: false },
      });
    } else {
      renderOutput = renderApp({
        pathname: path,
        user: { isAccountAndSettingsRevampEnabled: false },
      });
    }
    const { history } = renderOutput;
    expect(history.location.pathname).toBe(redirectPath);
  });
};

// make sure renderApp accepts isFlowRevamped as argument
export const testNewStylesUsingFlowRevamped = (renderApp, sectionTestId) => {
  test('should use newer styles when isFlowRevamped is true', () => {
    renderApp({ isFlowRevamped: true });
    const section = screen.getByTestId(sectionTestId);
    expect(section).toHaveClass('list-group details-row-container');
  });

  test('should not use newer styles when isFlowRevamped is false', () => {
    renderApp({ isFlowRevamped: false });
    const section = screen.getByTestId(sectionTestId);
    expect(section).not.toHaveClass('list-group details-row-container');
  });
};
