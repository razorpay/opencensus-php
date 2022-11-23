import React from 'react';
import ComponentListFilter from 'merchant/views/Settlements/v2/components/ComponentListFilter';
import { fireEvent, render, screen } from 'test-utils';

const submit = jest.fn((e) => e.preventDefault());
const clear = jest.fn((e) => e.preventDefault());

function App() {
  return (
    <ComponentListFilter
      type="debit"
      submit={submit}
      count={10}
      clear={clear}
      activeTab="adjustment"
    />
  );
}

test('should load all input fields', () => {
  render(<App />, {});

  expect(screen.getByText(/adjustment id/i)).toBeInTheDocument();
  expect(screen.getByText(/count/i)).toBeInTheDocument();
  expect(screen.getByText(/search/i)).toBeInTheDocument();
  expect(screen.getByText(/clear/i)).toBeInTheDocument();
});

test('should call search and clear functions on click', () => {
  render(<App />, {});

  // TODO: Should be replaced with getByRole/getByLabelText once inputs are accessible
  (document.getElementsByName('id')[0] as HTMLInputElement).value = 'test123';
  (document.getElementsByName('count')[0] as HTMLInputElement).value = '2';

  fireEvent.click(screen.getByText(/search/i));
  expect(submit).toBeCalledTimes(1);

  fireEvent.click(screen.getByText(/clear/i));
  expect(clear).toBeCalledTimes(1);
});
