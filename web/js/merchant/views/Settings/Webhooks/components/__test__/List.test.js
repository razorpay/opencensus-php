import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import WebhooksList from 'merchant/views/Settings/Webhooks/components/List';
import { render, screen, fireEvent } from 'test-utils';
import {
  location,
  skip,
  paginate,
  modeFormatted,
  webhooks,
  webhooksUpdated,
  onNewWebhookClick,
} from 'merchant/views/Settings/Webhooks/components/__test__/mocks/fixtures/List';

describe('Webhooks/components - List.js', () => {
  const App = (props) => {
    return <WebhooksList {...props} />;
  };

  test('should render webhook list component correctly with no list data', () => {
    render(
      <App
        {...webhooks}
        location={location}
        skip={skip}
        paginate={paginate}
        modeFormatted={modeFormatted}
      />,
    );
    expect(screen.getByText('You have not setup any webhook')).toBeInTheDocument();
    expect(screen.getByText('Add new Webhook')).toBeInTheDocument();
  });

  test('should render webhook list footer component', () => {
    render(
      <App
        {...webhooks}
        location={location}
        skip={skip}
        paginate={paginate}
        modeFormatted={modeFormatted}
      />,
    );
    expect(
      screen.getByText('List of all your webhook setup will show up here.'),
    ).toBeInTheDocument();
  });

  test('should call add webhook modal on CTA click', () => {
    render(
      <App
        {...webhooks}
        location={location}
        skip={skip}
        paginate={paginate}
        modeFormatted={modeFormatted}
        onNewWebhookClick={onNewWebhookClick}
      />,
    );
    const CTA = screen.getByText('Add new Webhook');
    fireEvent.click(CTA);
    expect(onNewWebhookClick).toHaveBeenCalled();
  });

  test('should render columns for empty webhooks list', () => {
    render(
      <App
        {...webhooks}
        location={location}
        skip={skip}
        paginate={paginate}
        modeFormatted={modeFormatted}
        onNewWebhookClick={onNewWebhookClick}
      />,
    );
    const url = screen.getByText('URL');
    const status = screen.getByText('Status');
    expect(url).toBeInTheDocument();
    expect(status).toBeInTheDocument();
  });

  test('should render webhooks list', () => {
    render(
      <App
        {...webhooksUpdated}
        location={location}
        skip={skip}
        paginate={paginate}
        modeFormatted={modeFormatted}
        onNewWebhookClick={onNewWebhookClick}
      />,
    );
    const url = screen.getByText('https://www.test.com/');
    const statusEnabled = screen.getByText('Enabled');
    const statusDisabled = screen.getByText('Disabled');
    expect(url).toBeInTheDocument();
    expect(statusEnabled).toBeInTheDocument();
    expect(statusDisabled).toBeInTheDocument();
  });
});
