import QuickGuideStep from 'merchant/views/RiskAndFraud/QuickGuide/QuickGuideStep';
import { render, screen, userEvent } from 'test-utils';

const renderApp = (props = {}) => {
  return render(<QuickGuideStep {...props} />);
};

describe('Tests for QuickGuideStep component', () => {
  test('Should render without breaking if no props are passed', () => {
    expect(renderApp).not.toThrowError();
  });

  test('Should show appropriate title and close button', () => {
    const props = { title: 'dummy title' };
    const { container } = renderApp(props);

    expect(screen.getByText(props.title)).toBeInTheDocument();
    expect(container.querySelector('button[aria-label="quick-guide-close"]')).toBeInTheDocument();
  });

  test('Should show data tiles if passed in props', () => {
    const props = {
      tiles: [
        { title: 'dummy title 1', content: 'dummy content 1' },
        { title: 'dummy title 2', content: 'dummy content 2' },
      ],
    };

    renderApp(props);

    props.tiles.forEach((tile) => {
      expect(screen.getByText(tile.title)).toBeInTheDocument();
      expect(screen.getByText(tile.content)).toBeInTheDocument();
    });
  });

  test('should call onCloseClick on close button click', async () => {
    const props = { onCloseClick: jest.fn() };
    const { container } = renderApp(props);

    const closeButton = container.querySelector('button[aria-label="quick-guide-close"]');
    await userEvent.click(closeButton);

    expect(props.onCloseClick).toHaveBeenCalled();
  });
});
