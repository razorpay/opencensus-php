import { render, screen, userEvent } from 'test-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { handleInfo } from 'merchant/views/PaymentHandle/__test__/mocks/fixtures/common';
import { App } from 'merchant/views/PaymentHandle/__test__/mocks/fixtures/Banner';

describe('Banner Component Unit Test', () => {
  const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');
  const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');

  beforeEach(() => {
    showNotificationSpy.mockClear();
    openModalSpy.mockClear();
    closeModalSpy.mockClear();
  });

  const renderApp = (props = {}) => {
    return render(
      <App
        {...props}
        handleInfo={handleInfo}
        isMobile={props.isMobile || false}
        isTestMode={false}
      />,
      { showModal: true },
    );
  };

  it('app component should be defined', () => {
    expect(App).toBeDefined();
  });

  it('should have payment handle details in the document', () => {
    renderApp();
    expect(
      screen.getByText(
        'Share your Razorpay.me link with customers as many times as you need to accept payments',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('@bhaskar1298')).toBeInTheDocument();
  });

  it('should render mobile view in Banner component', () => {
    const props = {
      isMobile: true,
    };
    renderApp(props);
    expect(screen.getByText('Get paid instantly with your Razorpay.me link')).toBeInTheDocument();
  });

  it('should render payment handle modal', async () => {
    renderApp();
    const shareBtn = screen.getByRole('button', { name: 'Share' });
    expect(shareBtn).toBeInTheDocument();
    await userEvent.click(shareBtn);
    expect(
      screen.getByText(
        'You can add a specific amount for your customer to pay. This will not affect your default link.',
      ),
    ).toBeInTheDocument();
  });
});
