import React from 'react';
import { render, userEvent } from 'test-utils';
import SubText from 'merchant/views/Account/TrustedBadge/components/SubText';

describe('SubText', () => {
  const props = {
    text: 'Sample text',
    optOut: true,
    showDocTnCLink: true,
    showKnowMore: true,
    className: 'sample-class',
    handleOptOut: jest.fn(),
    trackEvent: jest.fn(),
  };

  const renderApp = (props) => {
    return render(<SubText {...props} />);
  };

  it('renders the text with opt-out link if optOut prop is true', () => {
    const { getByText } = renderApp(props);
    const optOutLink = getByText('Opt-out');
    expect(optOutLink).toBeInTheDocument();
  });

  it('renders the "Know More" and "View T&C" links if showDocTnCLink prop is true', () => {
    const { getByText } = renderApp(props);
    const knowMoreLink = getByText('Know More');
    const viewTnCLink = getByText('View T&C');
    expect(knowMoreLink).toBeInTheDocument();
    expect(viewTnCLink).toBeInTheDocument();
  });

  it('renders only the "Know More" link if showDocTnCLink prop is false and showKnowMore prop is true', () => {
    const newProps = { ...props, showDocTnCLink: false };
    const { getByText, queryByText } = renderApp(newProps);
    const knowMoreLink = getByText('Know More');
    const viewTnCLink = queryByText('View T&C');
    expect(knowMoreLink).toBeInTheDocument();
    expect(viewTnCLink).toBeNull();
  });

  it('does not render any links if showDocTnCLink and showKnowMore props are false', () => {
    const newProps = { ...props, showDocTnCLink: false, showKnowMore: false };
    const { queryByText } = renderApp(newProps);
    const knowMoreLink = queryByText('Know More');
    const viewTnCLink = queryByText('View T&C');
    expect(knowMoreLink).toBeNull();
    expect(viewTnCLink).toBeNull();
  });

  it('calls handleOptOut function when opt-out link is clicked', async () => {
    const { getByText } = renderApp(props);
    const optOutLink = getByText('Opt-out');
    await userEvent.click(optOutLink);
    expect(props.handleOptOut).toHaveBeenCalledTimes(1);
  });
});
