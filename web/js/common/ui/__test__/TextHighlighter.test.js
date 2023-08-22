import { render, delay } from 'test-utils';

import TextHighlighter from 'common/ui/TextHighlighter';

const renderApp = ({ props, historyOptions }) => {
  return render(<TextHighlighter {...props}>Text Highlight</TextHighlighter>, { historyOptions });
};

describe('TextHighlighter', () => {
  test('children should not have background color applied', () => {
    const props = { hashedWith: 'randomHash' };
    const historyOptions = { initialEntries: [{ pathname: '/', hash: '#test' }] };

    const { container } = renderApp({ props, historyOptions });

    expect(container).toMatchSnapshot();
  });

  test('children should have background color applied and removed after 5 sec', async () => {
    const props = { hashedWith: 'test' };
    const historyOptions = { initialEntries: [{ pathname: '/', hash: '#test' }] };

    const { container } = renderApp({ props, historyOptions });

    expect(container).toMatchSnapshot();

    // class name will be removed after 5 sec.
    await delay(5000);

    expect(container).toMatchSnapshot();
  }, 7000);
});
