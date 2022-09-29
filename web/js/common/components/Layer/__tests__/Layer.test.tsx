import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import Layer from 'common/components/Layer/Layer';
import { render } from 'test-utils';

test('Layers rendering', () => {
  const content = 'Layer';
  const App = () => (
    <>
      <div> App element</div>
      <Layer>{content} 1</Layer>
      <Layer>{content} 2</Layer>
    </>
  );
  const { container } = render(<App />, {});
  const hostNode = container.childNodes[1];
  expect(hostNode.childNodes.length).toEqual(2);
  expect(hostNode.childNodes[0].textContent).toEqual(`${content} 1`);
  expect(hostNode.childNodes[1].textContent).toEqual(`${content} 2`);
});
