import { render, delay } from 'test-utils';

import TextHighlighter from 'common/ui/TextHighlighter';

const renderApp = ({ props, initialEntries }) => {
  return render(<TextHighlighter {...props}>Text Highlight</TextHighlighter>, { initialEntries });
};

describe('TextHighlighter', () => {
  test('children should not have background color applied', () => {
    const props = { hashedWith: 'randomHash' };
    const initialEntries = [{ pathname: '/', hash: '#test' }];

    const { container } = renderApp({ props, initialEntries });

    expect(container).toMatchSnapshot();
  });

  test('children should have background color applied and removed after 5 sec', async () => {
    const props = { hashedWith: 'test' };
    const initialEntries = [{ pathname: '/', hash: '#test' }];

    const { container } = renderApp({ props, initialEntries });

    expect(container).toMatchSnapshot();

    // class name will be removed after 5 sec.
    await delay(5000);

    expect(container).toMatchSnapshot();
  }, 7000);
});
