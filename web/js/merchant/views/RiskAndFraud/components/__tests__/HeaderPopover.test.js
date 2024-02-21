import { HeaderPopover } from 'merchant/views/RiskAndFraud/components';
import { render, screen, userEvent } from 'test-utils';

describe('HeaderPopover', () => {
  const defaultProps = {
    text: 'Your Text',
    title: 'Your Title',
    content: 'Your Content',
    contentImage: 'your-image-url.jpg',
    imageAlt: 'Your Image Alt Text',
  };

  const renderComponent = (props = {}) => {
    render(<HeaderPopover {...defaultProps} {...props} />);
  };

  test('render HeaderPopover without issue', () => {
    renderComponent();
    expect(screen.getByText(defaultProps.text)).toBeInTheDocument();
  });

  test('render popover onclick', async () => {
    renderComponent();
    const popoverText = screen.getByText(defaultProps.text);
    // Click on the popover text to open the popover
    await userEvent.click(popoverText);

    // Check if the expected content is present in the rendered component
    expect(screen.getByText(defaultProps.title)).toBeInTheDocument();
    expect(screen.getByText(defaultProps.content)).toBeInTheDocument();
    expect(screen.getByAltText(defaultProps.imageAlt)).toBeInTheDocument();
  });

  test('render popover onclick without image', async () => {
    renderComponent({ contentImage: null });
    const popoverText = screen.getByText(defaultProps.text);
    // Click on the popover text to open the popover
    await userEvent.click(popoverText);

    // Check if the expected content is present in the rendered component
    expect(screen.getByText(defaultProps.title)).toBeInTheDocument();
    expect(screen.getByText(defaultProps.content)).toBeInTheDocument();
    expect(screen.queryByAltText(defaultProps.imageAlt)).toBeNull();
  });
});
