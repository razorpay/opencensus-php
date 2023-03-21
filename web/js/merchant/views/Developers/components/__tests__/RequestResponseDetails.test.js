import React from 'react';
import RequestResponseDetails from 'merchant/views/Developers/components/RequestResponseDetails';
import { userEvent, render, screen } from 'test-utils';
import SyntaxHighlighter from 'react-syntax-highlighter';

const requestHeaders = {
  host: {
    values: ['localhost:8000'],
  },
  'content-type': {
    values: ['application/json'],
  },
};

const App = (props) => (
  <RequestResponseDetails title="Request Headers" textToCopy={requestHeaders} {...props}>
    <SyntaxHighlighter language="json">{JSON.stringify(requestHeaders, null, 2)}</SyntaxHighlighter>
  </RequestResponseDetails>
);

describe('RequestResponse Accordion component', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
  });

  test('request/response sections opens on title click', async () => {
    render(<App />, {});

    expect(screen.queryByTestId('request-response-data')).not.toBeInTheDocument();
    const title = screen.getByText('Request Headers');
    await userEvent.click(title);
    expect(screen.getByTestId('request-response-data')).toBeInTheDocument();
  });

  test('onOpen functions is called on title click', async () => {
    const onOpen = jest.fn();
    render(<App onOpen={onOpen} />, {});

    const title = screen.getByText('Request Headers');
    await userEvent.click(title);
    expect(onOpen).toBeCalledTimes(1);
  });

  test('request/response body is copied on copy button click', async () => {
    render(<App />, {});

    const title = screen.getByText('Request Headers');
    await userEvent.click(title);
    const copyButton = screen.getByTestId('request-response-copy-button');
    await userEvent.click(copyButton);
    expect(document.execCommand).toBeCalledTimes(1);
  });
});
