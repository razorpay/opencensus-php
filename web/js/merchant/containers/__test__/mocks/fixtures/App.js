import App from 'merchant/containers/App';
import { render } from 'test-utils';
import store from 'merchant/store';
const globalState = store.getState();

jest.mock('merchant/components/HeaderNav', () => ({
  __esModule: true,
  default: () => {
    return <div>Header Nav</div>;
  },
}));

jest.mock('merchant/components/Announcements/AnnouncementBanner', () => ({
  __esModule: true,
  default: () => {
    return <div>Announcement Banner</div>;
  },
}));

jest.mock('common/ui/Header', () => ({
  __esModule: true,
  default: () => {
    return <div>Header</div>;
  },
}));

jest.mock('merchant/routes/Content', () => ({
  __esModule: true,
  default: () => {
    return <div>Content</div>;
  },
}));

const userDetails = {
  current: 'HNi06UzUsLuj8c',
  user: {
    id: 'HNi06MJBIOBKIB',
    name: 'Nikhil',
    email: 'nikhilesh.tripathi@razorpay.com',
    contact_mobile: '7905204669',
  },
  experiments: {
    issuinghq_wallet_dashboard_enabled: {
      result: 'on',
    },
  },
};
const orgDetails = {
  features: ['logout_admin_inactivity'],
  merchant_session_timeout_in_seconds: 2,
};

const renderApp = (initialState = {}, props = {}) => {
  return render(<App {...props} />, {
    showModal: true,
    initialState: {
      ...globalState,
      session: {
        ...globalState.session,
        user: initialState?.session?.user ?? globalState?.session?.user,
        org: initialState?.session?.org ?? globalState?.session?.org,
      },
    },
    renderOptions: {
      historyOptions: {
        initialEntries: ['/dashboard'],
      },
      path: '/dashboard',
    },
  });
};

export { renderApp, userDetails, orgDetails };
