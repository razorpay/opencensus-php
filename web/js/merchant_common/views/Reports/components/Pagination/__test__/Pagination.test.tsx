import React, { useState } from 'react';
import { render, screen, userEvent } from 'test-utils';
import { Pagination } from 'merchant_common/views/Reports/components';

const App = () => {
  const [page, setPage] = useState(1);
  return (
    <Pagination
      totalCount={200}
      pageSize={20}
      currentPage={page}
      onPageChange={(_page) => setPage(_page)}
    />
  );
};

describe('Pagination', () => {
  test('should render component without error', () => {
    render(<App />);
    expect(screen.getByLabelText(`Page no is ${1}`)).toBeInTheDocument();
    expect(screen.getByLabelText(`Page no is ${5}`)).toBeInTheDocument();
    expect(screen.queryByLabelText(`Page no is ${6}`)).not.toBeInTheDocument();
    expect(screen.getByLabelText(`Page no is ${10}`)).toBeInTheDocument();
    expect(screen.queryByLabelText(`Page no is ${11}`)).not.toBeInTheDocument();
    expect(screen.queryByLabelText(`Previous Page`)).toBeInTheDocument();
    expect(screen.queryByLabelText(`Next Page`)).toBeInTheDocument();
  });

  test('should show next/prev page', async () => {
    render(<App />);
    await userEvent.click(screen.getByLabelText('Next Page'));
    await userEvent.click(screen.getByLabelText('Next Page'));
    await userEvent.click(screen.getByLabelText('Next Page'));
    await userEvent.click(screen.getByLabelText('Next Page'));
    expect(screen.queryByLabelText(`Page no is ${6}`)).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Previous Page'));
    expect(screen.queryByLabelText(`Page no is ${6}`)).not.toBeInTheDocument();
  });

  test('should be able to click on a particular page next page', async () => {
    render(<App />);
    await userEvent.click(screen.getByLabelText(`Page no is ${5}`));
    expect(screen.queryByLabelText(`Page no is ${6}`)).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText(`Page no is ${1}`));
    expect(screen.queryByLabelText(`Page no is ${6}`)).not.toBeInTheDocument();
  });

  test('should be able to click on a particular page next page', () => {
    // when pages are less then 5, all pages are shown
    render(<Pagination totalCount={20} pageSize={10} currentPage={1} onPageChange={() => {}} />);
    expect(screen.queryByLabelText(`Page no is ${1}`)).toBeInTheDocument();
    expect(screen.queryByLabelText(`Page no is ${2}`)).toBeInTheDocument();
    expect(screen.queryByLabelText(`Page no is ${3}`)).not.toBeInTheDocument();
  });
});
