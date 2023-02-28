import { updateWebsitePath } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner/config';
import BusinessDetails from 'merchant/views/Settings/Configuration/Questionnaire/BusinessDetails';
import { getWebsiteDetailsInfo } from 'merchant/views/Settings/Configuration/Questionnaire/utils';
import { render, screen, userEvent } from 'test-utils';

jest.mock('formik', () => ({
  __esModule: true,
  ...jest.requireActual('formik'),
  useFormikContext: () => {
    return {
      touched: {
        products: [],
        goods_type: '',
        business_use_case: '',
        business_txn_size: '',
        about_us_link: '',
      },
      errors: {},
      status: {},
      values: {
        products: [],
        goods_type: '',
        business_use_case: '',
        business_txn_size: '',
        about_us_link: '',
      },
      setFieldValue: jest.fn(),
    };
  },
}));

describe('BusinessDetails', () => {
  const renderApp = ({ props, user } = {}) => {
    return render(
      <BusinessDetails
        isRevampFlow={true}
        saveFormData={jest.fn()}
        closeModal={jest.fn()}
        {...props}
      />,
      {
        showModal: true,
        initialState: {
          session: {
            user: {
              business_website: '',
              additional_websites: [],
              ...user,
            },
          },
        },
      },
    );
  };

  test('should render website details section when user have business websites', async () => {
    const websiteDetails = {
      business_website: 'https://www.google.com',
      additional_websites: ['https://www.business1.com'],
    };
    const websiteInfo = getWebsiteDetailsInfo(websiteDetails);
    const { history } = renderApp({
      user: websiteDetails,
    });
    expect(screen.getByText(`Website(s)`)).toBeInTheDocument();
    websiteInfo.websitesData.forEach((website) => {
      expect(screen.getByText(website)).toBeInTheDocument();
    });
    expect(
      screen.getByText(
        'You’ll be able to collect international card payments only on registered website(s). To register another website, use the link below:',
      ),
    ).toBeInTheDocument();
    const AddLink = screen.getByRole('button', {
      name: 'Add/Update website',
    });
    await userEvent.click(AddLink);
    expect(history.location.pathname).toEqual(updateWebsitePath);
  });

  test('should render checkbox incase of revamp flow', () => {
    renderApp({});
    expect(
      screen.getByText('Choose product(s) to collect international payments on'),
    ).toBeInTheDocument();
    const checkbox = screen.getAllByRole('combobox');
    expect(checkbox.length).toBe(2);
  });
});
